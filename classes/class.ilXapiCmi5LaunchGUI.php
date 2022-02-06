<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilXapiCmi5LaunchGUI
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 *
 */
if ((int)ILIAS_VERSION_NUMERIC < 6) { // only in plugin
    require_once __DIR__.'/XapiProxy/vendor/autoload.php';
}
require_once __DIR__.'/class.ilXapiCmi5User.php';
require_once __DIR__.'/class.ilObjXapiCmi5.php';
require_once __DIR__.'/class.ilXapiCmi5AuthToken.php';
require_once __DIR__.'/class.ilXapiCmi5DateTime.php';
require_once __DIR__.'/class.ilXapiCmi5ContentUploadImporter.php';
require_once __DIR__.'/XapiReport/class.ilXapiCmi5AbstractRequest.php';

require_once('./Services/Repository/classes/class.ilObjectPlugin.php');

class ilXapiCmi5LaunchGUI
{
    const XAPI_PROXY_ENDPOINT = 'Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/xapiproxy.php';
     
    /**
     * @var ilObjXapiCmi5
     */
    protected $object;
    
    /**
     * @var ilXapiCmi5User
     */
    protected $cmixUser;

    /**
     * @var plugin
     */
    protected $plugin = true;
    
    /**
     * @param ilObjXapiCmi5 $object
     */
    public function __construct(ilObjXapiCmi5 $object)
    {
        $this->object = $object;
    }
    
    public function executeCommand()
    {
        $this->launchCmd();
    }
    
    protected function launchCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $this->initCmixUser();
        $token = $this->getValidToken();
        if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) {
            $ret = $this->CMI5preLaunch($token);
            $token = $ret['token'];
        }
        $launchLink = $this->buildLaunchLink($token);
        $DIC->ctrl()->redirectToURL($launchLink);
    }
    
    protected function buildLaunchLink($token)
    {
        if ($this->object->getSourceType() == ilObjXapiCmi5::SRC_TYPE_REMOTE) {
            $launchLink = $this->object->getLaunchUrl();
        } elseif ($this->object->getSourceType() == ilObjXapiCmi5::SRC_TYPE_LOCAL) {
            if (preg_match("/^(https?:\/\/)/",$this->object->getLaunchUrl()) == 1) {
                $launchLink = $this->object->getLaunchUrl();
            } else {
                $launchLink = implode('/', [
                    ILIAS_HTTP_PATH, ilUtil::getWebspaceDir(),
                    ilXapiCmi5ContentUploadImporter::RELATIVE_CONTENT_DIRECTORY_NAMEBASE . $this->object->getId()
                ]);

                $launchLink .= DIRECTORY_SEPARATOR . $this->object->getLaunchUrl();
            }
        }
        
        foreach ($this->getLaunchParameters($token) as $paramName => $paramValue) {
            $launchLink = ilUtil::appendUrlParameterString($launchLink, "{$paramName}={$paramValue}");
        }
        
        return $launchLink;
    }
    
    protected function getLaunchParameters($token)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $params = [];
        
        if ($this->object->isBypassProxyEnabled()) {
            // ToDo : polyfill or better Core new function getDefaultLrsEndpoint -> getLrsEndpoint
            $params['endpoint'] = urlencode(rtrim($this->object->getLrsType()->getDefaultLrsEndpoint(), '/') . '/');
        } else {
            $params['endpoint'] = urlencode(rtrim(ILIAS_HTTP_PATH . '/' . self::XAPI_PROXY_ENDPOINT, '/') . '/');
        }
        
        if ($this->object->isAuthFetchUrlEnabled()) {
            $params['fetch'] = urlencode($this->getAuthTokenFetchLink());
        } else {
            if ($this->object->isBypassProxyEnabled()) {
                $params['auth'] = urlencode($this->object->getLrsType()->getBasicAuth());
            } else {
                $params['auth'] = urlencode('Basic ' . base64_encode(
                    CLIENT_ID . ':' . $token
                ));
            }
        }
        
        $params['activity_id'] = urlencode($this->object->getActivityId());
        $params['activityId'] = urlencode($this->object->getActivityId());
        $params['actor'] = urlencode(json_encode($this->object->getStatementActor($this->cmixUser)));
        if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5)
        {
            $registration = $this->cmixUser->getRegistration();
            // for old CMI5 Content after switch commit but before cmi5 bugfix
            if ($registration == '')
            {
                $registration = ilXapiCmi5User::generateRegistration($this->object, $DIC->user());
            }
            $params['registration'] = $registration;
        }
        else
        {
            $params['registration'] = urlencode(ilXapiCmi5User::generateRegistration($this->object, $DIC->user()));
        }
        return $params;
    }
    
    protected function getAuthTokenFetchLink()
    {
        $link = implode('/', [
            ILIAS_HTTP_PATH, 'Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/xapitoken.php'
        ]);
        
        $param = $this->buildAuthTokenFetchParam();
        $link = iLUtil::appendUrlParameterString($link, "param={$param}");
        
        return $link;
    }
    
    /**
     * @return string
     */
    protected function buildAuthTokenFetchParam()
    {
        $params = [
            session_name() => session_id(),
            'obj_id' => $this->object->getId(),
            'ref_id' => $this->object->getRefId(),
            'ilClientId' => CLIENT_ID
        ];
        
        $encryptionKey = ilXapiCmi5AuthToken::getWacSalt();
        
        $param = urlencode(base64_encode(openssl_encrypt(
            json_encode($params),
            ilXapiCmi5AuthToken::OPENSSL_ENCRYPTION_METHOD,
            $encryptionKey,
            0,
            ilXapiCmi5AuthToken::OPENSSL_IV
        )));
        return $param;
    }
    
    protected function getValidToken()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $token = ilXapiCmi5AuthToken::fillToken(
            $DIC->user()->getId(),
            $this->object->getRefId(),
            $this->object->getId(),
            $this->object->getLrsType()->getTypeId()
        );
        return $token;
    }
    
    protected function initCmixUser()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $doLpUpdate = false;
        
        // if (!ilXapiCmi5User::exists($this->object->getId(), $DIC->user()->getId())) {
            // $doLpUpdate = true;
        // }
        
        $this->cmixUser = new ilXapiCmi5User($this->object->getId(), $DIC->user()->getId(), $this->object->getPrivacyIdent());
        $user_ident = $this->cmixUser->getUsrIdent();
        if ($user_ident == '' || $user_ident == null) {
			$user_ident = ilXapiCmi5User::getIdent($this->object->getPrivacyIdent(), $DIC->user());
            $this->cmixUser->setUsrIdent($user_ident);

            if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5)
            {
                $this->cmixUser->setRegistration(ilXapiCmi5User::generateCMI5Registration($this->object->getId(), $DIC->user()->getId()));
            }
            $this->cmixUser->save();
            // ToDo
            //ilLPStatusWrapper::_updateStatus($this->object->getId(), $DIC->user()->getId());
        }
        // if ($doLpUpdate) {
            // ilLPStatusWrapper::_updateStatus($this->object->getId(), $DIC->user()->getId());
        // }
    }

    protected function getCmi5LearnerPreferences() 
    {
        global $DIC;
        $language = $DIC->user()->getLanguage();
        $audio = "on";
        $prefs = [
            "languagePreference" => "{$language}",
            "audioPreference" => "{$audio}"
        ];
        return $prefs;
    }

    /**
     * Prelaunch
     * post cmi5LearnerPreference (agent profile)
     * post LMS.LaunchData
     */
    protected function CMI5preLaunch($token)
    {
        global $DIC;
        
        $lrsType = $this->object->getLrsType();
        $defaultLrs = $lrsType->getDefaultLrsEndpoint();
        $fallbackLrs = ($this->plugin) ? $lrsType->getFallbackLrsEndpoint() : "";
        $hasFallback = ($fallbackLrs === "") ? FALSE : TRUE;
        
        if ($hasFallback) {
            $fallbackAuth = $lrsType->getFallbackBasicAuth();
            $fallbackHeaders = [
                'X-Experience-API-Version' => '1.0.3',
                'Authorization' => $fallbackAuth,
                'Content-Type' => 'application/json;charset=utf-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate'
            ];
        }
        $defaultAuth = $lrsType->getBasicAuth();
        $defaultHeaders = [
            'X-Experience-API-Version' => '1.0.3',
            'Authorization' => $defaultAuth,
            'Content-Type' => 'application/json;charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate'
        ];
        
        $registration = $this->cmixUser->getRegistration();
        // for old CMI5 Content after switch commit but before cmi5 bugfix
        if ($registration == '') 
        {
            $registration = ilXapiCmi5User::generateRegistration($this->object, $DIC->user());
        }

        $activityId = $this->object->getActivityId();
        
        // profile
        $profileParams = [];
        $defaultAgentProfileUrl = $defaultLrs . "/agents/profile";
        $fallbackAgentProfileUrl = ($hasFallback) ? $fallbackLrs . "/agents/profile" : "";
        $profileParams['agent'] = json_encode($this->object->getStatementActor($this->cmixUser));
        $profileParams['profileId'] = 'cmi5LearnerPreferences';
        $profileParamsQuery = ilXapiCmi5AbstractRequest::buildQuery($profileParams);
        $defaultProfileUrl = $defaultAgentProfileUrl . '?' . $profileParamsQuery;
        $fallbackProfileUrl = ($hasFallback) ? $fallbackAgentProfileUrl . '?' . $profileParamsQuery : "";
        
        // launchData
        $launchDataParams = [];
        $defaultStateUrl = $defaultLrs . "/activities/state";
        $fallbackStateUrl = ($hasFallback) ? $fallbackLrs . "/activities/state" : "";
        //$launchDataParams['agent'] = $this->buildCmi5ActorParameter();
        $launchDataParams['agent'] = json_encode($this->object->getStatementActor($this->cmixUser));
        $launchDataParams['activityId'] = $activityId;
        $launchDataParams['activity_id'] = $activityId;
        $launchDataParams['registration'] = $registration;
        $launchDataParams['stateId'] = 'LMS.LaunchData';
        $launchDataQuery = ilXapiCmi5AbstractRequest::buildQuery($launchDataParams);
        $defaultLaunchDataUrl = $defaultStateUrl . '?' . $launchDataQuery;
        $fallbackLaunchDataUrl = ($hasFallback) ? $fallbackStateUrl . '?' . $launchDataQuery : "";
        $cmi5LearnerPreferencesObj = $this->getCmi5LearnerPreferences();
        $cmi5LearnerPreferences = json_encode($cmi5LearnerPreferencesObj);
        $lang = $cmi5LearnerPreferencesObj['languagePreference'];
        $cmi5_session = ilObjXapiCmi5::guidv4();
        $tokenObject = ilXapiCmi5AuthToken::getInstanceByToken($token);
        $oldSession = $tokenObject->getCmi5Session();
        $oldSessionLaunchedTimestamp = '';
        $abandoned = false;
        // cmi5_session already exists?
        if (!empty($oldSession)) {
            $oldSessionData = json_decode($tokenObject->getCmi5SessionData());
            $oldSessionLaunchedTimestamp = $oldSessionData->launchedTimestamp;
            $tokenObject->delete();
            $token = $this->getValidToken();
            $tokenObject = ilXapiCmi5AuthToken::getInstanceByToken($token);
            $lastStatement = $this->object->getLastStatement($oldSession);
            // should never be 'terminated', because terminated statement is sniffed from proxy -> token delete
            if ($lastStatement[0]['statement']['verb']['id'] != ilXapiCmi5VerbList::getInstance()->getVerbUri('terminated'))
            {
                $abandoned = true;
                $start = new DateTime($oldSessionLaunchedTimestamp);
                $end = new DateTime($lastStatement[0]['statement']['timestamp']);
                $diff = $end->diff($start);
                $duration = ilXapiCmi5DateTime::dateIntervalToISO860Duration($diff);
            }
        }
        // satisfied on launch?
        // see: https://github.com/AICC/CMI-5_Spec_Current/blob/quartz/cmi5_spec.md#moveon
        // https://aicc.github.io/CMI-5_Spec_Current/samples/
        // Session that includes the absolute minimum data, and is associated with a NotApplicable Move On criteria 
        // which results in immediate satisfaction of the course upon registration creation. Includes Satisfied Statement.
        $satisfied = false;
        $lpMode = $this->object->getLPMode();
        // only do this, if we decide to map the moveOn NotApplicable to ilLPObjSettings::LP_MODE_DEACTIVATED on import and settings editing
        // and what about user result status?
        if ($lpMode === ilLPObjSettings::LP_MODE_DEACTIVATED) {
            $satisfied = true;
        }

        $tokenObject->setCmi5Session($cmi5_session);
        $sessionData = array();
        $sessionData['cmi5LearnerPreferences'] = $cmi5LearnerPreferencesObj;
        //https://www.php.net/manual/de/class.dateinterval.php
        $now = new ilXapiCmi5DateTime(time(), IL_CAL_UNIX);
        $sessionData['launchedTimestamp'] = $now->toXapiTimestamp(); // required for abandoned statement duration, don't want another roundtrip to lrs ...puhhh
        $tokenObject->setCmi5SessionData(json_encode($sessionData));
        $tokenObject->update();
        $defaultStatementsUrl = $defaultLrs . "/statements";
        $fallbackStatementsUrl = ($hasFallback) ? $fallbackLrs . "/statements" : "";
        
        // launchedStatement
        $launchData = json_encode($this->object->getLaunchData($this->cmixUser,$lang));
        $launchedStatement = $this->object->getLaunchedStatement($this->cmixUser);
        $launchedStatementParams = [];
        $launchedStatementParams['statementId'] = $launchedStatement['id'];
        $launchedStatmentQuery = ilXapiCmi5AbstractRequest::buildQuery($launchedStatementParams);
        $defaultLaunchedStatementUrl = $defaultStatementsUrl . '?' .  $launchedStatmentQuery;
        $fallbackLaunchedStatementUrl = ($hasFallback) ? $fallbackStatementsUrl . '?' .  $launchedStatmentQuery : "";
        
        // abandonedStatement
        if ($abandoned) {
            $abandonedStatement = $this->object->getAbandonedStatement($oldSession, $duration, $this->cmixUser);
            $abandonedStatementParams = [];
            $abandonedStatementParams['statementId'] = $abandonedStatement['id'];
            $abandonedStatementQuery = ilXapiCmi5AbstractRequest::buildQuery($abandonedStatementParams);
            $defaultAbandonedStatementUrl = $defaultStatementsUrl . '?' . $abandonedStatementQuery;
            $fallbackAbandonedStatementUrl = ($hasFallback) ? $fallbackStatementsUrl . '?' . $abandonedStatementQuery  : "";
        }
        // satisfiedStatement
        if ($satisfied) {
            $satisfiedStatement = $this->object->getSatisfiedStatement($this->cmixUser);
            $satisfiedStatementParams = [];
            $satisfiedStatementParams['statementId'] = $satisfiedStatement['id'];
            $satisfiedStatementQuery = ilXapiCmi5AbstractRequest::buildQuery($satisfiedStatementParams);
            $defaultSatisfiedStatementUrl = $defaultStatementsUrl . '?' . $satisfiedStatementQuery;
            $fallbackSatisfiedStatementUrl = ($hasFallback) ? $fallbackStatementsUrl . '?' . $satisfiedStatementQuery : "";
        }
        $client = new GuzzleHttp\Client();
        $req_opts = array(
            GuzzleHttp\RequestOptions::VERIFY => true,
            GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 10,
            GuzzleHttp\RequestOptions::HTTP_ERRORS => false
        );
        $defaultProfileRequest = new GuzzleHttp\Psr7\Request(
            'POST',
            $defaultProfileUrl,
            $defaultHeaders,
            $cmi5LearnerPreferences
        );
        $fallbackProfileRequest = ($hasFallback) ? new GuzzleHttp\Psr7\Request(
            'POST',
            $fallbackProfileUrl,
            $fallbackHeaders,
            $cmi5LearnerPreferences
        ) : NULL;
        $defaultLaunchDataRequest = new GuzzleHttp\Psr7\Request(
            'PUT',
            $defaultLaunchDataUrl,
            $defaultHeaders,
            $launchData
        );
        $fallbackLaunchDataRequest = ($hasFallback) ? new GuzzleHttp\Psr7\Request(
            'PUT',
            $fallbackLaunchDataUrl,
            $fallbackHeaders,
            $launchData
        ) : NULL;
        $defaultLaunchedStatementRequest = new GuzzleHttp\Psr7\Request(
            'PUT',
            $defaultLaunchedStatementUrl,
            $defaultHeaders,
            json_encode($launchedStatement)
        );
        $fallbackLaunchedStatementRequest = ($hasFallback) ? new GuzzleHttp\Psr7\Request(
            'PUT',
            $fallbackLaunchedStatementUrl,
            $fallbackHeaders,
            json_encode($launchedStatement)
        ) : NULL;
        if ($abandoned) {
            $defaultAbandonedStatementRequest = new GuzzleHttp\Psr7\Request(
                'PUT',
                $defaultAbandonedStatementUrl,
                $defaultHeaders,
                json_encode($abandonedStatement)
            );
            $fallbackAbandonedStatementRequest = ($hasFallback) ? new GuzzleHttp\Psr7\Request(
                'PUT',
                $fallbackAbandonedStatementUrl,
                $fallbackHeaders,
                json_encode($abandonedStatement)
            ) : NULL;
        }
        if ($satisfied) {
            $defaultSatisfiedStatementRequest = new GuzzleHttp\Psr7\Request(
                'PUT',
                $defaultSatisfiedStatementUrl,
                $defaultHeaders,
                json_encode($satisfiedStatement)
            );
            $fallbackSatisfiedStatementRequest = ($hasFallback) ? new GuzzleHttp\Psr7\Request(
                'PUT',
                $fallbackSatisfiedStatementUrl,
                $fallbackHeaders,
                json_encode($satisfiedStatement)
            ) : NULL;
        }
        $promises = array();

        // default
        $promises['defaultProfile'] = $client->sendAsync($defaultProfileRequest, $req_opts);
        $promises['defaultLaunchData'] = $client->sendAsync($defaultLaunchDataRequest, $req_opts);
        $promises['defaultLaunchedStatement'] = $client->sendAsync($defaultLaunchedStatementRequest, $req_opts);
        if ($abandoned) {
            $promises['defaultAbandonedStatement'] = $client->sendAsync($defaultAbandonedStatementRequest, $req_opts);
        }
        if ($satisfied) {
            $promises['defaultSatisfiedStatement'] = $client->sendAsync($defaultSatisfiedStatementRequest, $req_opts);
        }

        // fallback
        if ($hasFallback) {
            $promises['fallbackProfile'] = $client->sendAsync($fallbackProfileRequest, $req_opts);
            $promises['fallbackLaunchData'] = $client->sendAsync($fallbackLaunchDataRequest, $req_opts);
            $promises['fallbackLaunchedStatement'] = $client->sendAsync($fallbackLaunchedStatementRequest, $req_opts);
            if ($abandoned) {
                $promises['fallbackAbandonedStatement'] = $client->sendAsync($fallbackAbandonedStatementRequest, $req_opts);
            }
            if ($satisfied) {
                $promises['fallbackSatisfiedStatement'] = $client->sendAsync($fallbackSatisfiedStatementRequest, $req_opts);
            }
        }
        try
        {
            $responses = GuzzleHttp\Promise\settle($promises)->wait();
            $body = '';
            foreach ($responses as $response) {
                ilXapiCmi5AbstractRequest::checkResponse($response,$body,[204]);
            }
        }
        catch(Exception $e)
        {
            $this->log()->error('error:' . $e->getMessage());
        }
        return array('cmi5_session' => $cmi5_session, 'token' => $token);
    }
    
    private function log() {
        global $log;
        if ($this->plugin) {
            return $log;
        }
        else {
            return \ilLoggerFactory::getLogger('cmix');
        }
    }
}
