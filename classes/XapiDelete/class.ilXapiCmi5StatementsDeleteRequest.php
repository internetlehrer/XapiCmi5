<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once __DIR__.'/../class.ilObjXapiCmi5.php';
require_once __DIR__.'/../XapiReport/class.ilXapiCmi5StatementsReportFilter.php';
require_once __DIR__.'/../class.ilXapiCmi5User.php';
require_once __DIR__.'/../class.ilXapiCmi5Type.php';
/**
 * Class ilXapiCmi5StatementsDeleteRequest
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 */
class ilXapiCmi5StatementsDeleteRequest
{   
    const DELETE_SCOPE_FILTERED = "filtered";
    const DELETE_SCOPE_ALL = "all";
    const DELETE_SCOPE_OWN = "own";
    
    /**
     * @var string
     */
    protected $scope;

    /**
     * @var ilXapiCmi5StatementsReportFilter
     */
    protected $filter;

    /**
     * @var int
     */
    protected $objId;

    /**
     * @var ilXapiCmi5Type
     */
    protected $lrsType;

    /**
     * @var string
     */
    protected $endpointDefault = '';
    
    /**
     * @var string
     */
    protected $endpointFallback = '';

    /**
     * @var bool
     */
    protected $hasFallback;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var array
     */
    protected $defaultHeaders;

    /**
     * @var array
     */
    protected $fallbackHeaders;

    /**
     * ilXapiCmi5StatementsDeleteRequest constructor.
     * @param int $obj_id
     * @param int $type_id
     * @param int $usr_id
     * @param string $activity_id
     * @param string $scope
     * @param ilXapiCmi5StatementsReportFilter $filter
     */
    public function __construct(
        int $obj_id,
        int $type_id,
        string $activity_id,
        int $usr_id = NULL,
        ?string $scope = self::DELETE_SCOPE_FILTERED,
        ?ilXapiCmi5StatementsReportFilter $filter = NULL
        )
    {
        if ((int)ILIAS_VERSION_NUMERIC < 6) { // only in plugin
            require_once __DIR__.'/../XapiProxy/vendor/autoload.php';
        }
        $this->objId = $obj_id;
        $this->lrsType = new ilXapiCmi5Type($type_id);
        $this->activityId = $activity_id;
        $this->usrId = $usr_id;
        $this->scope = $scope;
        $this->filter = $filter;
        
        $this->endpointDefault = $this->lrsType->getDefaultLrsEndpoint();
        $this->endpointFallback = $this->lrsType->getFallbackLrsEndpoint();
        $this->hasFallback = ($this->endpointFallback === "") ? FALSE : TRUE;
        $this->client = new GuzzleHttp\Client();
        $this->headers = [
            'X-Experience-API-Version' => '1.0.3'
        ];
        $this->defaultHeaders = $this->headers;
        $this->defaultHeaders['Authorization'] = $this->lrsType->getDefaultBasicAuth();
        if ($this->hasFallback) {
            $this->fallbackHeaders = $this->headers;
            $this->fallbackHeaders['Authorization'] = $this->lrsType->getFallbackBasicAuth();
        }
    }
    
    /**
     * @return bool
     */
    public function delete() : bool
    {
        global $DIC; /** @var \ILIAS\DI\Container $DIC */
        $allResponses = $this->deleteData();
        $resStatements = $allResponses['statements'];
        $resStates = $allResponses['states'];
        $defaultRejected = isset($resStatements['default']) && isset($resStatements['default']['state']) && $resStatements['default']['state'] === 'rejected';
        $fallbackRejected = isset($resStatements['fallback']) && isset($resStatements['fallback']['state']) && $resStatements['fallback']['state'] === 'rejected';
        $resArr = array();
        // ToDo: fullfilled and status code handling
        if (isset($resStatements['default']) && isset($resStatements['default']['value'])) {
            $res = $resStatements['default']['value'];
            $resBody = json_decode($res->getBody(),true);
            $resArr[] = $resBody['_id']; 
        }
        if (isset($resStatements['fallback']) && isset($resStatements['fallback']['value'])) {
            $res = $resStatements['fallback']['value'];
            $resBody = json_decode($res->getBody(),true);
            $resArr[] = $resBody['_id'];
        }
        
        if (count($resArr) == 0) {
            $DIC->logger()->root()->log("No data deleted");
            return !$defaultRejected && !$fallbackRejected;
        }

        $maxtime = 240; // should be some minutes!
        $t = 0;
        $done = false;
        while ($t < $maxtime) {
            // get batch done
            sleep(1);
            $response = $this->queryBatch($resArr);
            if (isset($response['default']) && isset($response['default']['value'])) {
                $res = $response['default']['value'];
                $resBody = json_decode($res->getBody(),true);
                if ($resBody && $resBody['edges'] && count($resBody['edges']) == 1) {
                    $doneDefault = $resBody['edges'][0]['node']['done'];
                    $DIC->logger()->root()->log("doneDefault: " . $doneDefault);
                }
            }
            if (isset($response['fallback']) && isset($response['fallback']['value'])) {
                $res = $response['fallback']['value'];
                $resBody = json_decode($res->getBody(),true);
                if ($resBody && $resBody['edges'] && count($resBody['edges']) == 1) {
                    $doneFallback = $resBody['edges'][0]['node']['done'];
                    $DIC->logger()->root()->log("doneFallback: " . $doneFallback);
                }
            }
            if ($this->hasFallback && $doneDefault && $doneFallback) {
                $done = true;
                break;
            }
            else {
                if ($doneDefault) {
                    $done = true;
                    break;
                }
            }
            $t++;
        }
        if ($done) {
            $this->checkDeleteUsersForObject();
        }
        return $done;
    }

    /**
     * @return array
     */
    public function deleteData() : array
    {
        global $DIC;

        $deleteState = true;

        $f = null;
        if ($this->scope === self::DELETE_SCOPE_FILTERED) {
            $deleteState = $this->checkDeleteState();
            $f = $this->buildDeleteFiltered();
        }
        if ($this->scope === self::DELETE_SCOPE_ALL) {
            $f = $this->buildDeleteAll();
        }
        if ($this->scope === self::DELETE_SCOPE_OWN) {
            $f = $this->buildDeleteOwn();
        }
        if ($f === false) {
            $DIC->logger()->root()->log('error: could not build filter');
            return array();
        }
        $cf = array('filter' => $f);
        $body = json_encode($cf);
        $this->defaultHeaders['Content-Type'] = 'application/json; charset=utf-8';
        $defaultUrl = $this->lrsType->getDefaultLrsEndpointDeleteLink();
        $defaultRequest = new GuzzleHttp\Psr7\Request('POST', $defaultUrl, $this->defaultHeaders, $body);
        $promisesStatements = [
            'default' => $this->client->sendAsync($defaultRequest)
        ];
        $promisesStates = array();
        if ($deleteState) {
            $urls = $this->getDeleteStateUrls($this->lrsType->getDefaultLrsEndpointStateLink());
            foreach ($urls as $i => $v) {
                $r = new GuzzleHttp\Psr7\Request('DELETE', $v, $this->defaultHeaders);
                $promisesStates['default'.$i] = $this->client->sendAsync($r);
            }
        }
        $response = array();
        $response['statements'] = array();
        $response['states'] = array();
        if ($this->hasFallback) {
            $this->fallbackHeaders['Content-Type'] = 'application/json; charset=utf-8';
            $fallbackUrl = $this->lrsType->getFallbackLrsEndpointDeleteLink();
            $fallbackRequest = new GuzzleHttp\Psr7\Request('POST', $fallbackUrl, $this->fallbackHeaders, $body);
            $promisesStatements['fallback'] = $this->client->sendAsync($fallbackRequest);
            if ($deleteState) {
                $urls = $this->getDeleteStateUrls($this->lrsType->getFallbackLrsEndpointStateLink());
                foreach ($urls as $i => $v) {
                    $r = new GuzzleHttp\Psr7\Request('DELETE', $v, $this->fallbackHeaders);
                    $promisesStates['fallback'.$i] = $this->client->sendAsync($r);
                }
            }
        }
        
        try { // maybe everything into one promise?
            $response['statements'] = GuzzleHttp\Promise\settle($promisesStatements)->wait();
            if ($deleteState && count($promisesStates) > 0) {
                $response['states'] = GuzzleHttp\Promise\settle($promisesStates)->wait();
            }
        }
        catch (Exception $e) {
            $DIC->logger()->root()->log('error:' . $e->getMessage());
        }
        return $response;
    }
    
    public function _lookUpDataCount($scope = NULL) {
        global $DIC;
        $pipeline = array();
        if (is_null($scope)) {
            $scope = $this->scope;
        }
        if ($scope === self::DELETE_SCOPE_OWN) {
            $f = $this->buildDeleteOwn();
            if (count($f) == 0) {
                return 0;
            }
        }
        if ($scope === self::DELETE_SCOPE_FILTERED) {
            $f = $this->buildDeleteFiltered();
        }
        if ($scope === self::DELETE_SCOPE_ALL) {
            $f = $this->buildDeleteAll();
        }
        $pipeline[] = array('$match' => $f);
        $pipeline[] = array('$count' => 'count');
        $pquery = urlencode(json_encode($pipeline));
        $query = "pipeline={$pquery}";
        $purl = $this->lrsType->getDefaultLrsEndpointStatementsAggregationLink();
        $url = ilUtil::appendUrlParameterString($purl, $query);
        $request = new GuzzleHttp\Psr7\Request('GET', $url, $this->defaultHeaders);
        try {
            $response = $this->client->sendAsync($request)->wait();
            $cnt = json_decode($response->getBody());
            return (int) $cnt[0]->count;
        }
        catch(Exception $e) {
            throw new Exception("LRS Connection Problems");
            return 0;
        }
    }

    /**
     * @param string $batchId
     * @return array 
     */
    public function queryBatch(array $batchId) : array
    {
        global $DIC;
        $defaultUrl = $this->getBatchUrl($this->lrsType->getDefaultLrsEndpointBatchLink(), $batchId[0]);
        $defaultRequest = new GuzzleHttp\Psr7\Request('GET', $defaultUrl, $this->defaultHeaders);
        $promises = [
            'default' => $this->client->sendAsync($defaultRequest)
        ];
        $response = [];
        if ($this->hasFallback && isset($batchId[1])) {
            $fallbackUrl = $this->getBatchUrl($this->lrsType->getFallbackLrsEndpointBatchLink(), $batchId[1]);
            $fallbackRequest = new GuzzleHttp\Psr7\Request('GET', $fallbackUrl, $this->fallbackHeaders);
            $promises['fallback'] = $this->client->sendAsync($fallbackRequest);
        }
        try {
            $response = GuzzleHttp\Promise\settle($promises)->wait();
        }
        catch (Exception $e) {
            $DIC->logger()->root()->log('error:' . $e->getMessage());
        }
        return $response;
    }

    /**
     * @param string $url
     * @param string $batchId
     * @return string
     */
    private function getBatchUrl(string $url,string $batchId) : string {
        $f = array();
        $f['_id'] = [
            '$oid' => $batchId
        ];
        $f = urlencode(json_encode($f));
        $f = "filter={$f}";
        return ilUtil::appendUrlParameterString($url, $f);
    }
    
    private function getDeleteStateUrls($url) : array {
        $ret = array();
        $states = $this->buildDeleteStates();
        foreach($states as $i => $v) {
            $ret[] = ilUtil::appendUrlParameterString($url, $v);
        }
        return $ret;
    }

    /**
     * @return array
     */
    private function buildDeleteAll() : array 
    {
        global $DIC;
        $f = array();
        
        $f['statement.object.objectType'] = 'Activity';
        $f['statement.object.id'] = [
            '$regex' => '^' . preg_quote($this->activityId) . ''
        ];
        
        $f['statement.actor.objectType'] = 'Agent';
        
        $f['$or'] = [];
        // foreach (ilXapiCmi5User::getUsersForObjectPlugin($this->getObjId()) as $usr_id) {
            // $f['$or'][] = ['statement.actor.mbox' => "mailto:".ilXapiCmi5User::getUsrIdentPlugin($usr_id,$this->getObjId())];
		foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
            $f['$or'][] = ['statement.actor.mbox' => "mailto:{$cmixUser->getUsrIdent()}"];
        }
        if (count($f['$or']) == 0) {
            // Exception Handling!
            return [];
        }
        else {
            return $f;
        }
    }

    /**
     * @return array
     */
    private function buildDeleteFiltered() : array
    {
        global $DIC;
        $f = array();
        
        $f['statement.object.objectType'] = 'Activity';
        $f['statement.object.id'] = [
            '$regex' => '^' . preg_quote($this->activityId) . ''
        ];
        
        $f['statement.actor.objectType'] = 'Agent';
        $f['$or'] = [];
        if ($this->filter->getActor()) {
			foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
				if ($cmixUser->getUsrId() == $this->filter->getActor()->getUsrId()) {
					$f['$or'][] = ['statement.actor.mbox' => "mailto:{$cmixUser->getUsrIdent()}"];
				}
            }
        } 
        else { // check hasOutcomes Access? 
			foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
				$f['$or'][] = ['statement.actor.mbox' => "mailto:{$cmixUser->getUsrIdent()}"];
            }
        }

        if ($this->filter->getVerb()) {
            $f['statement.verb.id'] = $this->filter->getVerb();
        }
        
        if ($this->filter->getStartDate() || $this->filter->getEndDate()) {
            $f['statement.timestamp'] = array();
            
            if ($this->filter->getStartDate()) {
                $f['statement.timestamp']['$gt'] = $this->filter->getStartDate()->toXapiTimestamp();
            }
            
            if ($this->filter->getEndDate()) {
                $f['statement.timestamp']['$lt'] = $this->filter->getEndDate()->toXapiTimestamp();
            }
        }
        
        if (count($f['$or']) == 0) {
            // Exception Handling!
            return [];
        }
        else {
            return $f;
        }
    }

    /**
     * @return array
     */
    private function buildDeleteOwn() : array
    {
        global $DIC;
        $f = array();
        $f['statement.object.objectType'] = 'Activity';
        $f['statement.object.id'] = [
            '$regex' => '^' . preg_quote($this->activityId) . ''
        ];
        $f['statement.actor.objectType'] = 'Agent';

        $usrId = ($this->usrId !== NULL) ? $this->usrId : $DIC->user()->getId();
        $cmixUsers = ilXapiCmi5User::getInstancesByObjectIdAndUsrId($this->objId,$usrId);
        $f['$or'] = [];
        foreach ($cmixUsers as $cmixUser) {
            $f['$or'][] = ['statement.actor.mbox' => "mailto:{$cmixUser->getUsrIdent()}"];
        }
        if (count($f['$or']) == 0) {
            return [];
        }
        else {
            return $f;
        }
    }

    /**
     * @return array
     */
    private function buildDeleteStates() : array
    {
        global $DIC;
        $ret = array();
        $user = "";
        if ($this->scope === self::DELETE_SCOPE_FILTERED && $this->filter->getActor()) {
            foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
                if ($cmixUser->getUsrId() == $this->filter->getActor()->getUsrId()) {
                    $user = $cmixUser->getUsrIdent();
                    $ret[] = 'activityId='.urlencode($this->activityId).'&agent='.urlencode('{"mbox":"mailto:'.$user.'"}');
                }
            }
        }

        if ($this->scope === self::DELETE_SCOPE_OWN) {
            $usrId = ($this->usrId !== NULL) ? $this->usrId : $DIC->user()->getId();
            foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
                if ((int) $cmixUser->getUsrId() === $usrId) {
                    $user = $cmixUser->getUsrIdent();
                    $ret[] = 'activityId='.urlencode($this->activityId).'&agent='.urlencode('{"mbox":"mailto:'.$user.'"}');
                }
            }
        }

        if ($this->scope === self::DELETE_SCOPE_ALL) {
            foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
                $user = $cmixUser->getUsrIdent();
                $ret[] = 'activityId='.urlencode($this->activityId).'&agent='.urlencode('{"mbox":"mailto:'.$user.'"}');
            }
        }
        return $ret;
    }

    /**
     * @param string $scope
     * @param ilXapiCmi5StatementsReportFilter $filter
     * @return bool
     */
    private function checkDeleteState() : bool {
        global $DIC;
        if ($this->scope === self::DELETE_SCOPE_ALL || $this->scope === self::DELETE_SCOPE_OWN) {
            return true;
        }
        if ($this->filter->getActor()) { // ToDo: only in Multicactor Mode?
            if ($this->filter->getVerb() || $this->filter->getStartDate() || $this->filter->getEndDate()) {
                return false;
            }
            else {
                return true;
            }
        }
        return false;
    }

    private function checkDeleteUsersForObject() {
        global $DIC;
        if ($this->scope === self::DELETE_SCOPE_ALL) {
            ilXapiCmi5User::deleteUsersForObject($this->objId);
        }
        if ($this->scope === self::DELETE_SCOPE_OWN) {
            $usrId = ($this->usrId !== NULL) ? [$this->usrId] : [$DIC->user()->getId()];
            ilXapiCmi5User::deleteUsersForObject($this->objId, $usrId);
        }
        if ($this->scope === self::DELETE_SCOPE_FILTERED) {
            if ($this->checkDeleteState() && $this->filter) {
                $usrId = [$this->filter->getActor()->getUsrId()];
                ilXapiCmi5User::deleteUsersForObject($this->objId, $usrId);
            }
        }
    }
}
