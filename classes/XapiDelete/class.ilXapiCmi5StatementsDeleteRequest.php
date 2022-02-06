<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */
if ((int)ILIAS_VERSION_NUMERIC < 6) { // only in plugin
    require_once __DIR__.'/../XapiProxy/vendor/autoload.php';
}
require_once __DIR__.'/../class.ilObjXapiCmi5.php';
require_once __DIR__.'/../XapiReport/class.ilXapiCmi5StatementsReportFilter.php';
require_once __DIR__.'/../class.ilXapiCmi5User.php';
require_once __DIR__.'/../class.ilXapiCmi5LrsType.php';
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/XapiReport/class.ilXapiCmi5AbstractRequest.php');
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
     * @var bool
     */
    protected $plugin = true;

    /**
     * @var bool
     */
    protected $cmi5_extensions_query = false;

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
     * @var int
     */
    protected $refId;

    /**
     * @var ilXapiCmi5LrsType
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
     * @param int $ref_id
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
        $this->objId = $obj_id;
        $this->lrsType = new ilXapiCmi5LrsType($type_id);
        $this->activityId = $activity_id;
        $this->usrId = $usr_id;
        $this->scope = $scope;
        $this->filter = $filter;
        
        $this->endpointDefault = $this->lrsType->getDefaultLrsEndpoint();
        $this->endpointFallback = ($this->plugin) ? $this->lrsType->getFallbackLrsEndpoint() : '';
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
        $hasFallback = isset($resStatements['fallback']);
        $resStates = $allResponses['states'];

        // required?
        $defaultRejected = isset($resStatements['default']) && isset($resStatements['default']['state']) && $resStatements['default']['state'] === 'rejected';
        $fallbackRejected = isset($resStatements['fallback']) && isset($resStatements['fallback']['state']) && $resStatements['fallback']['state'] === 'rejected';

        $resArr = array();

        // statements
        $defaultStatementsBody = '';
        ilXapiCmi5AbstractRequest::checkResponse($resStatements['default'],$defaultStatementsBody,[200]);
        $defaultStatementResBody = json_decode($defaultStatementsBody,true);
        $resArr[] = $defaultStatementResBody['_id'];

        if ($hasFallback)
        {
            $fallbackStatementsBody = '';
            ilXapiCmi5AbstractRequest::checkResponse($resStatements['fallback'],$fallbackStatementsBody,[200]);
            $fallbackStatementResBody = json_decode($fallbackStatementsBody,true);
            $resArr[] = $fallbackStatementResBody['_id'];
        }
        if (count($resArr) == 0) {
            $DIC->logger()->root()->log("No data deleted");
            return !$defaultRejected && !$fallbackRejected;
        }

        // states
        $stateBody = '';
        foreach ($resStates as $resState) {
            ilXapiCmi5AbstractRequest::checkResponse($resState,$stateBody,[204]);
        }
        
        if (count($resArr) == 0) {
            $DIC->logger()->root()->log("No data deleted");
            return !$defaultRejected && !$fallbackRejected;
        }

        $maxtime = 30; // x 3 seconds = 90 secs - should be some minutes
        $t = 0;
        $done = false;
        while ($t < $maxtime) {
            // get batch done
            sleep(3);
            $t++;
            $response = $this->queryBatch($resArr);

            $defaultBatchBody = '';
            ilXapiCmi5AbstractRequest::checkResponse($response['default'],$defaultBatchBody,[200]);
            $defaultBatchResBody = json_decode($defaultBatchBody,true);
            if ($defaultBatchResBody && $defaultBatchResBody['edges'] && count($defaultBatchResBody['edges']) == 1) {
                $doneDefault = $defaultBatchResBody['edges'][0]['node']['done'];
            }

            if ($hasFallback) 
            {
                $fallbackBatchBody = '';
                ilXapiCmi5AbstractRequest::checkResponse($response['fallback'],$fallbackBatchBody,[200]);
                $fallbackBatchResBody = json_decode($fallbackBatchBody,true);
                if ($fallbackBatchResBody && $fallbackBatchResBody['edges'] && count($fallbackBatchResBody['edges']) == 1) {
                    $doneFallback = $fallbackBatchResBody['edges'][0]['node']['done'];
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
        $stage = array();
        $stage['statement.object.objectType'] = 'Activity';
        $stage['statement.actor.objectType'] = 'Agent';
        $obj = $this->getObj();
        $activityId = array();

        if ($this->cmi5_extensions_query == true && $obj->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5 && !$obj->isMixedContentType())
        {
            // https://github.com/AICC/CMI-5_Spec_Current/blob/quartz/cmi5_spec.md#963-extensions
            $activityId['statement.context.extensions.https://ilias&46;de/cmi5/activityid'] = $obj->getActivityId();
        }
        else
        {
            // for case-insensive: '$regex' => '(?i)^' . preg_quote($this->filter->getActivityId()) . ''
            $activityQuery = [
                '$regex' => '^' . preg_quote($this->activityId) . ''
            ];
            $activityId['$or'] = [];
            // ToDo : restriction to exact activityId?
            // query existing activityId in grouping? we have not enough control over acticityId in xapi statements  
            // another way put the obj_id into a generated registration, but we are not sure that content will put this into statement context 
            // $activityId['$or'][] = ['statement.object.id' => "{$this->filter->getActivityId()}"];
            $activityId['$or'][] = ['statement.object.id' => $activityQuery];
            $activityId['$or'][] = ['statement.context.contextActivities.parent.id' => $activityQuery];
        }
        $actor = array();
        
        if ($obj->isMixedContentType()) 
        {
            foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) 
            {
                $usrIdent = $cmixUser->getUsrIdent();
                $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"];
                $actor['$or'][] = ['statement.actor.account.name' => "{$usrIdent}"];
            }
        }
        elseif ($obj->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) 
        {
            // required ? not just delete all data for activityId (=unique map to ILIAS obj) 
            foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) 
            {
                 $actor['$or'][] = ['statement.context.registration' => "{$cmixUser->getRegistration()}"];
            }
        }
        else // xAPI
        {
            foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) 
            {
                $usrIdent = $cmixUser->getUsrIdent();
                $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"];
            }
        }
       
        $stage['$and'][] = $activityId;
        if (count($actor) > 0) {
            $stage['$and'][] = $actor;
        }
        return $stage;
    }

    /**
     * @return array
     */
    private function buildDeleteFiltered() : array
    {
        global $DIC;
        $stage = array();
        $stage['statement.object.objectType'] = 'Activity';
        $stage['statement.actor.objectType'] = 'Agent';
        $obj = $this->getObj();
        $activityId = array();

        if ($this->cmi5_extensions_query == true && $obj->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5 && !$obj->isMixedContentType())
        {
            // https://github.com/AICC/CMI-5_Spec_Current/blob/quartz/cmi5_spec.md#963-extensions
            $activityId['statement.context.extensions.https://ilias&46;de/cmi5/activityid'] = $obj->getActivityId();
        }
        else
        {
            // for case-insensive: '$regex' => '(?i)^' . preg_quote($this->filter->getActivityId()) . ''
            $activityQuery = [
                '$regex' => '^' . preg_quote($this->activityId) . ''
            ];
            $activityId['$or'] = [];
            // ToDo : restriction to exact activityId?
            // query existing activityId in grouping? we have not enough control over acticityId in xapi statements  
            // another way put the obj_id into a generated registration, but we are not sure that content will put this into statement context 
            // $activityId['$or'][] = ['statement.object.id' => "{$this->filter->getActivityId()}"];
            $activityId['$or'][] = ['statement.object.id' => $activityQuery];
            $activityId['$or'][] = ['statement.context.contextActivities.parent.id' => $activityQuery];
        }

        $actor = array();
        
        if ($obj->isMixedContentType()) 
        {
            if ($this->filter->getActor()) {
                foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser)
                {
                    if ($cmixUser->getUsrId() == $this->filter->getActor()->getUsrId()) {
                        $usrIdent = $cmixUser->getUsrIdent();
                        $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"];
                        $actor['$or'][] = ['statement.actor.account.name' => "{$usrIdent}"];
                    }
                }
            }
            else {
                foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
                    $usrIdent = $cmixUser->getUsrIdent();
                    $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"];
                    $actor['$or'][] = ['statement.actor.account.name' => "{$usrIdent}"];
                }
            }
        }
        elseif ($obj->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) 
        {
            if ($this->filter->getActor()) {
                foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser)
                {
                    if ($cmixUser->getUsrId() == $this->filter->getActor()->getUsrId()) {
                        $actor['$or'][] = ['statement.context.registration' => "{$cmixUser->getRegistration()}"];
                    }
                }
            }
            else {
                foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
                    $actor['$or'][] = ['statement.context.registration' => "{$cmixUser->getRegistration()}"];
                }
            }
        }
        else // xAPI
        {
            if ($this->filter->getActor()) {
                foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser)
                {
                    if ($cmixUser->getUsrId() == $this->filter->getActor()->getUsrId()) {
                        $usrIdent = $cmixUser->getUsrIdent();
                        $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"];
                    }
                }
            }
            else {
                foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
                    $usrIdent = $cmixUser->getUsrIdent();
                    $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"];
                }
            }
        }

        $f = array();
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
        
        $stage['$and'][] = $activityId;
        if (count($actor) > 0) {
            $stage['$and'][] = $actor;
        }
        if (count($f) > 0) {
            $stage['$and'][] = $f;
        }
        return $stage;
    }

    /**
     * @return array
     */
    private function buildDeleteOwn() : array
    {
       global $DIC;
        $stage = array();
        $stage['statement.object.objectType'] = 'Activity';
        $stage['statement.actor.objectType'] = 'Agent';
        $obj = $this->getObj();
        $activityId = array();
        $activityId = array();

        if ($this->cmi5_extensions_query == true && $obj->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5 && !$obj->isMixedContentType())
        {
            // https://github.com/AICC/CMI-5_Spec_Current/blob/quartz/cmi5_spec.md#963-extensions
            $activityId['statement.context.extensions.https://ilias&46;de/cmi5/activityid'] = $obj->getActivityId();
        }
        else
        {
            // for case-insensive: '$regex' => '(?i)^' . preg_quote($this->filter->getActivityId()) . ''
            $activityQuery = [
                '$regex' => '^' . preg_quote($this->activityId) . ''
            ];
            $activityId['$or'] = [];
            // ToDo : restriction to exact activityId?
            // query existing activityId in grouping? we have not enough control over acticityId in xapi statements  
            // another way put the obj_id into a generated registration, but we are not sure that content will put this into statement context 
            // $activityId['$or'][] = ['statement.object.id' => "{$this->filter->getActivityId()}"];
            $activityId['$or'][] = ['statement.object.id' => $activityQuery];
            $activityId['$or'][] = ['statement.context.contextActivities.parent.id' => $activityQuery];
        }
        
        $actor = array();

        $usrId = ($this->usrId !== NULL) ? $this->usrId : $DIC->user()->getId();
        $cmixUsers = ilXapiCmi5User::getInstancesByObjectIdAndUsrId($this->objId,$usrId);
        
        if ($obj->isMixedContentType()) 
        {
            foreach ($cmixUsers as $cmixUser) 
            {
                $usrIdent = $cmixUser->getUsrIdent();
                $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"];
                $actor['$or'][] = ['statement.actor.account.name' => "{$usrIdent}"];
            }
        }
        elseif ($obj->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) 
        {
            // required ? not just delete all data for activityId (=unique map to ILIAS obj) 
            foreach ($cmixUsers as $cmixUser) 
            {
                 $actor['$or'][] = ['statement.context.registration' => "{$cmixUser->getRegistration()}"];
            }
        }
        else // xAPI
        {
            foreach ($cmixUsers as $cmixUser) 
            {
                $usrIdent = $cmixUser->getUsrIdent();
                $actor['$or'][] = ['statement.actor.mbox' => "mailto:{$usrIdent}"];
            }
        }

        $stage['$and'][] = $activityId;
        if (count($actor) > 0) {
            $stage['$and'][] = $actor;
        }
        return $stage;
    }

    /**
     * @return array
     */
    private function buildDeleteStates() : array
    {
        global $DIC;
        $ret = array();
        $obj = $this->getObj();
        /*
        $launchDataParams['stateId'] = 'LMS.LaunchData';
        */
        $launchDataParams = [];
        $launchDataParams['activityId'] = $this->activityId;
        $launchDataParams['activity_id'] = $this->activityId;

        if ($this->scope === self::DELETE_SCOPE_FILTERED && $this->filter->getActor()) {
            foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
                if ($cmixUser->getUsrId() == $this->filter->getActor()->getUsrId()) {
                    $actor = $obj->getStatementActor($cmixUser);
                    $launchDataParams['agent'] = json_encode($actor);
                    $registration = $cmixUser->getRegistration();
                    if ($registration != '') {
                        $launchDataParams['registration'] = $registration;
                    }
                    ilXapiCmi5AbstractRequest::buildQuery($launchDataParams);
                    $ret[] = ilXapiCmi5AbstractRequest::buildQuery($launchDataParams);
                }
            }
        }
        if ($this->scope === self::DELETE_SCOPE_OWN) {
            $usrId = ($this->usrId !== NULL) ? $this->usrId : $DIC->user()->getId();
            foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
                if ((int) $cmixUser->getUsrId() === $usrId) {
                    $actor = $obj->getStatementActor($cmixUser);
                    $launchDataParams['agent'] = json_encode($actor);
                    $registration = $cmixUser->getRegistration();
                    if ($registration != '') {
                        $launchDataParams['registration'] = $registration;
                    }
                    ilXapiCmi5AbstractRequest::buildQuery($launchDataParams);
                    $ret[] = ilXapiCmi5AbstractRequest::buildQuery($launchDataParams);
                }
            }
        }
        if ($this->scope === self::DELETE_SCOPE_ALL) {
            foreach (ilXapiCmi5User::getUsersForObject($this->objId) as $cmixUser) {
                $actor = $obj->getStatementActor($cmixUser);
                $launchDataParams['agent'] = json_encode($actor);
                $registration = $cmixUser->getRegistration();
                if ($registration != '') {
                    $launchDataParams['registration'] = $registration;
                }
                ilXapiCmi5AbstractRequest::buildQuery($launchDataParams);
                $ret[] = ilXapiCmi5AbstractRequest::buildQuery($launchDataParams);
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

    /**
     * @return int
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * @return ilObjXapiCmi5
     */
    public function getObj()
    {
        // !!! it is a new cloned data object without parent ilObjPlugin / Object2
        // getId() will not work!
        // getRefId() will not work!
        // .....
        $obj = new ilObjXapiCmi5();
        $obj->load($this->objId);
        return $obj;
    }
}
