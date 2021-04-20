<?php
require_once __DIR__.'/vendor/autoload.php';
chdir("../../../../../../../../");

// require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5.php';
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5Type.php');
require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/xapi/DataService.php';
require_once dirname(__FILE__) . '/../classes/class.ilXapiCmi5Type.php';

use ILIAS\DI\Container;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
use \GuzzleHttp\Client;
use \GuzzleHttp\Promise;
use \GuzzleHttp\RequestOptions;
use \GuzzleHttp\Exception\ConnectException;
use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Psr7\Uri;
use \Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

// check options requests
if (strtoupper($_SERVER["REQUEST_METHOD"]) == "OPTIONS") {
	header('HTTP/1.1 204 No Content');
	header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
	header('Access-Control-Allow-Headers: X-Experience-API-Version,Accept,Authorization,Etag,Cache-Control,Content-Type,DNT,If-Modified-Since,Keep-Alive,Origin,User-Agent,X-Mx-ReqToken,X-Requested-With');
	header('Access-Control-Max-Age: 600');
	exit;
}
$sniffVerbs = array (
	"http://adlnet.gov/expapi/verbs/completed" => "completed",
	"http://adlnet.gov/expapi/verbs/passed" => "passed",
	"http://adlnet.gov/expapi/verbs/failed" => "failed",
	"http://adlnet.gov/expapi/verbs/satisfied" => "passed"
);
/** @var ?string $token */
$token = null;

/** @var ?ilXapiCmi5Type $lrsType */
$lrsType = null;

if( !empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW']) )
{
	$client = $_SERVER['PHP_AUTH_USER'];
	$token = $_SERVER['PHP_AUTH_PW'];
}
elseif( !empty($_SERVER['HTTP_AUTHORIZATION']) )
{
	$basicAuth = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
	$client = $basicAuth[0];
	$token = $basicAuth[1];
}
else
{
	header('HTTP/1.1 401 Authorization Required');
	header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
	header('Access-Control-Allow-Headers: X-Experience-API-Version,Accept,Authorization,Etag,Cache-Control,Content-Type,DNT,If-Modified-Since,Keep-Alive,Origin,User-Agent,X-Mx-ReqToken,X-Requested-With');
	header('Access-Control-Max-Age: 600');
	exit;
}


\XapiProxy\DataService::initIlias($client,$token);

$dic = $GLOBALS['DIC']; /** @var Container $dic */

# testen, 7.3, bugs

// ToDo: json_decode(obj,true) as assoc array might be faster?
$replacedValues = NULL;
$specificAllowedStatements = NULL;
$blockSubStatements = false;

try {
	$lrsType = getLrsTypeAndMoreByToken($token);
	if ($lrsType == null) {
		//ilLoggerFactory::getLogger('root')->write("XapiCmi5Plugin: 401 Unauthorized for token");
		$dic->logger()->root()->log("XapiCmi5Plugin: 401 Unauthorized for token");
		header('HTTP/1.1 401 Unauthorized');
		header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: X-Experience-API-Version,Accept,Authorization,Etag,Cache-Control,Content-Type,DNT,If-Modified-Since,Keep-Alive,Origin,User-Agent,X-Mx-ReqToken,X-Requested-With');
        	header('Access-Control-Max-Age: 600');
		exit;
	}
}
catch(Exception $e)
{
	//ilLoggerFactory::getLogger('root')->write("XapiCmi5Plugin: " . $e->getMessage());
	$dic->logger()->root()->log("XapiCmi5Plugin: " . $e->getMessage());
	header('HTTP/1.1 401 Unauthorized');
        header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: X-Experience-API-Version,Accept,Authorization,Etag,Cache-Control,Content-Type,DNT,If-Modified-Since,Keep-Alive,Origin,User-Agent,X-Mx-ReqToken,X-Requested-With');
        header('Access-Control-Max-Age: 600');
	exit;
}

$request = $dic->http()->request();

/*
 * async
 */

handleRequest($request);

/**
 * handle main request
 */
function handleRequest(ServerRequestInterface $request) {
	global $log, $lrsType;
	$method = strtolower($request->getMethod());
	$log->debug($lrsType->getLogMessage("Request Method: " . $method));
	$path = $request->getUri()->getPath();
	switch($method) {
		case "post" :
		case "put" :
			if (preg_match('/\/statements$/',$path)) {
				$log->debug("handle Post-Put statements " . $path);
				handlePostPutRequest($request);
			}
			else {
				handleProxy($request);
			}
			break;
		default :
			handleProxy($request);
	}
}

/**
 * handle request for body sniffing, only post put requests
 * @throws \GuzzleHttp\Exception\GuzzleException
 */
function handlePostPutRequest(ServerRequestInterface $request) {
	global $log, $lrsType;
	$body = $request->getBody()->getContents();
	$fakePostBody = NULL;
	if (empty($body)) {
		$log->warning($lrsType->getLogMessage("empty body in handlePostPutRequest"));
		handleProxy($request);
	}
	else {
		try {
			$log->debug($lrsType->getLogMessage("handle statements"));
			$ret = handleStatements($request, $body);
			if (is_array($ret)) {
				$body = json_encode($ret[0]); // new body with allowed statements
				$fakePostBody = $ret[1]; // fake post php array of ALL statments as if all statements were processed
			}
		}
		catch(Exception $e) {
			$log->error($lrsType->getLogMessage($e->getMessage()));
			exitProxyError();
		}
		try {
			$body = modifyBody($body);
			$log->debug($lrsType->getLogMessage($body));
			$changes = array (
				"body" => $body
			);
			$req = \GuzzleHttp\Psr7\modify_request($request, $changes);
			handleProxy($req, $fakePostBody);
		}
		catch(Exception $e) {
			$log->error($lrsType->getLogMessage($e->getMessage()));
			handleProxy($request, $fakePostBody);
		}
	}
}

/**
 * handle blocked request
 * @param ServerRequestInterface $request
 */

function handleStatements(ServerRequestInterface $request, $body) {
	global $log, $lrsType, $specificAllowedStatements, $blockSubStatements;
	// everything is allowed
	if (!is_array($specificAllowedStatements) && !$blockSubStatements) {
		$log->debug($lrsType->getLogMessage("all statement are allowed"));
		return NULL;
	}
	$obj = json_decode($body, false);
	// single statement object
	if (is_object($obj) && isset($obj->verb)) {
		$log->debug($lrsType->getLogMessage("json is object"));
		$isSubStatement = isSubStatement($obj);
		$verb = $obj->verb->id;
		if ($blockSubStatements && $isSubStatement) {
			$log->debug($lrsType->getLogMessage("sub-statement is not allowed, fake response..."));
			fakeResponseBlocked(NULL);
		}
		if (is_array($specificAllowedStatements) && in_array($verb,$specificAllowedStatements)) {
			$log->debug($lrsType->getLogMessage("statement is allowed, do nothing..."));
			return NULL;
		}
		else {
			$log->debug($lrsType->getLogMessage("statement is not allowed, fake response..."));
			fakeResponseBlocked(NULL);
		}
	}
	// array of statement objects
	if (is_array($obj) && count($obj) > 0 && isset($obj[0]->verb)) {
		$log->debug($lrsType->getLogMessage("json is array"));
		$ret = array();
		$up = array();
		for ($i=0; $i<count($obj); $i++) {
			array_push($ret,$obj[$i]->id); // push every statementid for fakePostResponse
			$isSubStatement = isSubStatement($obj[$i]);
			$verb = $obj[$i]->verb->id;
			if ($blockSubStatements && $isSubStatement) {
				$log->debug($lrsType->getLogMessage("statement is NOT allowed."));
			}
			else {
				if (!is_array($specificAllowedStatements) || (is_array($specificAllowedStatements) && in_array($verb,$specificAllowedStatements))) {
					$log->debug($lrsType->getLogMessage("statement is allowed: " . $verb));
					array_push($up,$obj[$i]);
				}
			}
		}
		// mixed request with allowed and not allowed statements
		if (count($up) !== count($ret)) {
			$log->debug($lrsType->getLogMessage("some statements are allowed, some not..."));
			return array($up,$ret);
		}
		// just return nothing
		return NULL;
	}
}

function fakeResponseBlocked($post=NULL) {
	global $log, $lrsType;
	$log->debug($lrsType->getLogMessage("fakeResponseFromBlockedRequest"));
	if ($post===NULL) {
		$log->debug($lrsType->getLogMessage("post === NULL"));
		header('HTTP/1.1 204 No Content');
		header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
		header('Access-Control-Allow-Credentials: true');
		header('X-Experience-API-Version: 1.0.3');
		exit;
	}
	else {
		$ids = json_encode($post);
		$log->debug($lrsType->getLogMessage("post: " . $ids));
		header('HTTP/1.1 200 Ok');
		header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
		header('Access-Control-Allow-Credentials: true');
		header('X-Experience-API-Version: 1.0.3');
		header('Content-Length: ' . strlen($ids));
		header('Content-Type: application/json; charset=utf-8');
		echo $ids;
		exit;
	}
}

/**
 * handle proxy request
 * @param ServerRequestInterface $request
 * @throws \GuzzleHttp\Exception\GuzzleException
 */
function handleProxy(ServerRequestInterface $request, $fakePostBody = NULL) {
	global $log, $lrsType;
	
	$endpointDefault = $lrsType->getDefaultLrsEndpoint();
	$endpointFallback = $lrsType->getFallbackLrsEndpoint();

	$log->debug($lrsType->getLogMessage("endpointDefault: " . $endpointDefault));
	$log->debug($lrsType->getLogMessage("endpointFallback: " . $endpointFallback));
	
	$keyDefault =  $lrsType->getDefaultLrsKey();
	$secretDefault =  $lrsType->getDefaultLrsSecret();
	$authDefault = 'Basic ' . base64_encode($keyDefault . ':' . $secretDefault);

	$hasFallback = ($endpointFallback === "") ? FALSE : TRUE;

	if ($hasFallback) {
		$keyFallback = $lrsType->getFallbackLrsKey();
		$secretFallback = $lrsType->getFallbackLrsSecret();
		$authFallback = 'Basic ' . base64_encode($keyFallback . ':' . $secretFallback);
	}
	
	$req_opts = array(
		RequestOptions::VERIFY => false,
		RequestOptions::CONNECT_TIMEOUT => 5
	);
	$full_uri = $request->getUri();
	$serverParams = $request->getServerParams();
	$queryParams = $request->getQueryParams();
	$parts_reg = '/^(.*?xapiproxy\.php)(.+)/'; // ToDo: replace hard coded regex?
	preg_match($parts_reg,$full_uri,$cmd_parts);
	
	if (count($cmd_parts) === 3) { // should always
		$cmd = $cmd_parts[2];
		$upstreamDefault = $endpointDefault.$cmd;
		$uriDefault = new Uri($upstreamDefault);
		$changesDefault = array(
			'uri' => $uriDefault,
			'set_headers' => array('Cache-Control' => 'no-cache, no-store, must-revalidate', 'Authorization' => $authDefault)
		);
		$reqDefault = \GuzzleHttp\Psr7\modify_request($request, $changesDefault);
		if ($hasFallback) {
			$upstreamFallback = $endpointFallback.$cmd;
			$uriFallback = new Uri($upstreamFallback);
			$changesFallback = array(
				'uri' => $uriFallback,
				'set_headers' => array('Cache-Control' => 'no-cache, no-store, must-revalidate', 'Authorization' => $authFallback)
			);
			$reqFallback = \GuzzleHttp\Psr7\modify_request($request, $changesFallback);
		}
		$httpclient = new Client();
		if ($hasFallback) {
			$promises = [
				'default' 	=> $httpclient->sendAsync($reqDefault, $req_opts),
				'fallback'	=> $httpclient->sendAsync($reqFallback, $req_opts)
			];
			
			// this would throw first ConnectionException
			// $responses = Promise\unwrap($promises);
			
			$responses = Promise\settle($promises)->wait();
			$defaultOk = checkResponse($responses['default'], $endpointDefault);
			$fallbackOk = checkResponse($responses['fallback'], $endpointFallback);

			if ($defaultOk) {
				try {
					handleResponse($reqDefault, $responses['default']['value'], $fakePostBody);
				}
				catch (Exception $e) {
					$log->error($lrsType->getLogMessage("XAPI exception from Default LRS: " . $endpointDefault . " (sent HTTP 500 to client): " . $e->getMessage()));
					exitProxyError();
				}
			}
			elseif ($fallbackOk) {
				try {
					handleResponse($reqFallback, $responses['fallback']['value'], $fakePostBody);
				}
				catch (Exception $e) {
					$log->error($lrsType->getLogMessage("XAPI exception from Default LRS: " . $endpointDefault . " (sent HTTP 500 to client): " . $e->getMessage()));
					exitProxyError();
				}
			}
			else {
				exitResponseError();
			}
		}
		else {
			$promises = [
				'default' => $httpclient->sendAsync($reqDefault, $req_opts)
			];

			// this would throw first ConnectionException
			// $responses = Promise\unwrap($promises);

			$responses = Promise\settle($promises)->wait();
			if (checkResponse($responses['default'], $endpointDefault)) {
				try {
					handleResponse($reqDefault, $responses['default']['value'], $fakePostBody);
				}
				catch(Exception $e) {
					$log->error($lrsType->getLogMessage("XAPI exception from Default LRS: " . $endpointDefault . " (sent HTTP 500 to client): " . $e->getMessage()));
					exitProxyError();
				}
			}
			else {
				exitResponseError();
			}
		}
	}
	else {
		$log->warning($lrsType->getLogMessage("Wrong command parts!"));
		header("HTTP/1.1 412 Wrong Request Parameter");
		echo "HTTP/1.1 412 Wrong Request Parameter";
		exit;
	}
}

function handleResponse(ServerRequestInterface $request, ResponseInterface $response, $fakePostBody = NULL) {
	global $log, $lrsType;
	// check transfer encoding bug
	if ($fakePostBody !== NULL) {
		$origBody = $response->getBody();
		$log->debug($lrsType->getLogMessage("orig body: " . $origBody));
		$log->debug($lrsType->getLogMessage("fake body: " . json_encode($fakePostBody)));
		// because there is a real response object, it should also be possible to override the response stream...
		// but this does the job as well:
		fakeResponseBlocked($fakePostBody);
	} 
	$status = $response->getStatusCode();
	$headers = $response->getHeaders();
	if (array_key_exists('Transfer-Encoding', $headers) && $headers['Transfer-Encoding'][0] == "chunked") {
		$log->debug($lrsType->getLogMessage("sniff response transfer-encoding for unallowed Content-length"));
		$body = $response->getBody();
		unset($headers['Transfer-Encoding']);
		$headers['Content-Length'] = array(strlen($body));
		$response2 = new \GuzzleHttp\Psr7\Response($status,$headers,$body);
		(new SapiEmitter())->emit($response2);
	}
	else {
		(new SapiEmitter())->emit($response);
	}
}

function modifyBody($body) {
	global $replacedValues;
	$obj = json_decode($body, false);
	if (is_object($obj)) {
		if (is_array($replacedValues)) {
			foreach ($replacedValues as $key => $value) {
				setValue($obj,$key,$value);
			}
		}
		setStatus($obj);
	}
	if (is_array($obj)) {
		for ($i=0; $i<count($obj); $i++) {
			if (is_array($replacedValues)) {
				foreach ($replacedValues as $key => $value) {
					setValue($obj[$i],$key,$value);
				}
			}
			setStatus($obj[$i]);
		} 
	}
	return json_encode($obj);
}

function setStatus($obj) {
	global $client, $token, $sniffVerbs, $lrsType, $log;

	require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5.php';
	if (isset($obj->verb) && isset($obj->actor) && isset($obj->object)) {
		$verb = $obj->verb->id;
		$score = 'NOT_SET';
		if (array_key_exists($verb, $sniffVerbs)) {
			// check context
			if (isSubStatement($obj)) {
				$log->debug($lrsType->getLogMessage("statement is sub-statement, ignore status verb " . $verb));
				return;
			}
			if (isset($obj->result) && isset($obj->result->score) && isset($obj->result->score->scaled)) {
				$score = $obj->result->score->scaled;
			}
			$log->debug($lrsType->getLogMessage("handleLPStatus: " . $sniffVerbs[$verb] . " : " . $score));
			\ilObjXapiCmi5::handleLPStatusFromProxy($client, $token, $sniffVerbs[$verb], $score);
		}
	} 
}

function setValue(&$obj, $path, $value) {
	$path_components = explode('.', $path);
    if (count($path_components) == 1) {
    	if (property_exists($obj,$path_components[0])) {
        	$obj->{$path_components[0]} = $value;
      	}
    }
    else {
    	if (property_exists($obj, $path_components[0])) {
        	setValue($obj->{array_shift($path_components)}, implode('.', $path_components), $value);
      	}
    }
}

function isSubStatement($obj) {
	global $log, $lrsType;
	if (
		isset($obj->context) &&
		isset($obj->context->contextActivities) &&
		is_array($obj->context->contextActivities->parent)
	) {
		$log->debug($lrsType->getLogMessage("is Substatement"));
		return true;
	}
	else {
		$log->debug($lrsType->getLogMessage("is not Substatement"));
		return false;
	}
}

function checkResponse($response, $endpoint) {
	global $log, $lrsType;
	if ($response['state'] === 'fulfilled') {
		$status = $response['value']->getStatusCode();
		if ($status === 200 || $status === 204) {
			return true;
		}
		else {
			$log->error($lrsType->getLogMessage("Could not get valid response status_code: " . $status .  " from " . $endpoint));
			return false;
		}
	}
	else {
		$log->error($lrsType->getLogMessage("Could not fulfill request to " . $endpoint));
		return false;
	}
	return false;
}

function exitResponseError() {
	header("HTTP/1.1 412 Wrong Response");
	echo "HTTP/1.1 412 Wrong Response";
	exit;
}

function exitProxyError() {
	header("HTTP/1.1 500 XapiProxy Error (Ask For Logs)");
	echo "HTTP/1.1 500 XapiProxy Error (Ask For Logs)";
	exit;
}

// use only for debugging states before ILIAS Init
function _log($txt) {
	if( DEVMODE ) file_put_contents("xapilog.txt",$txt."\n",FILE_APPEND);
}

function getLrsTypeAndMoreByToken($token)
{
	global $replacedValues, $specificAllowedStatements, $blockSubStatements, $DIC; /** @var Container $DIC */
	$type_id = null;
	$lrs = null;

	$db = $DIC->database();
	$query ="SELECT xxcf_data_settings.type_id,
					xxcf_data_settings.only_moveon, 
					xxcf_data_settings.achieved, 
					xxcf_data_settings.answered, 
					xxcf_data_settings.completed, 
					xxcf_data_settings.failed, 
					xxcf_data_settings.initialized, 
					xxcf_data_settings.passed, 
					xxcf_data_settings.progressed, 
					xxcf_data_settings.satisfied, 
					xxcf_data_settings.c_terminated, 
					xxcf_data_settings.hide_data, 
					xxcf_data_settings.c_timestamp, 
					xxcf_data_settings.duration, 
					xxcf_data_settings.no_substatements 
			FROM xxcf_data_settings, xxcf_data_token 
			WHERE xxcf_data_settings.obj_id = xxcf_data_token.obj_id AND xxcf_data_token.token = " . $db->quote($token, 'text');
	$res = $db->query($query);
	while ($row = $db->fetchObject($res)) 
	{
		$type_id = $row->type_id;
		if ($type_id) {
			$lrs = new ilXapiCmi5Type($type_id);
		}
		if ((bool)$row->only_moveon) {
			if ((bool)$row->achieved) {
				$specificAllowedStatements[] = "https://w3id.org/xapi/dod-isd/verbs/achieved";
			}
			if ((bool)$row->answered) {
				$specificAllowedStatements[] = "http://adlnet.gov/expapi/verbs/answered";
				$specificAllowedStatements[] = "https://w3id.org/xapi/dod-isd/verbs/answered";
			}
			if ((bool)$row->completed) {
				$specificAllowedStatements[] = "http://adlnet.gov/expapi/verbs/completed";
				$specificAllowedStatements[] = "https://w3id.org/xapi/dod-isd/verbs/completed";
			}
			if ((bool)$row->failed) {
				$specificAllowedStatements[] = "http://adlnet.gov/expapi/verbs/failed";
			}
			if ((bool)$row->initialized) {
				$specificAllowedStatements[] = "http://adlnet.gov/expapi/verbs/initialized";
				$specificAllowedStatements[] = "https://w3id.org/xapi/dod-isd/verbs/initialized";
			}
			if ((bool)$row->passed) {
				$specificAllowedStatements[] = "http://adlnet.gov/expapi/verbs/passed";
			}
			if ((bool)$row->progressed) {
				$specificAllowedStatements[] = "http://adlnet.gov/expapi/verbs/progressed";
			}
			if ((bool)$row->satisfied) {
				$specificAllowedStatements[] = "https://w3id.org/xapi/adl/verbs/satisfied";
			}
			if ((bool)$row->c_terminated) {
				$specificAllowedStatements[] = "http://adlnet.gov/expapi/verbs/terminated";
			}
		}
		if ((bool)$row->hide_data) {
			if ((bool)$row->c_timestamp) $replacedValues['timestamp'] = '1970-01-01T00:00:00.000Z';
			if ((bool)$row->duration) $replacedValues['result.duration'] = 'PT00.000S';
		}
		if ((bool)$row->no_substatements) {
			$blockSubStatements = true;
		}
	}
	return $lrs;
}
?>
