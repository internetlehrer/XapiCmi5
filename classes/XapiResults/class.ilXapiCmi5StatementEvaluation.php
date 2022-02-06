<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */
if ((int)ILIAS_VERSION_NUMERIC < 6) { // only in plugin
    require_once __DIR__.'/../XapiProxy/vendor/autoload.php';
}
include_once('./Services/Tracking/classes/class.ilLPStatusWrapper.php');
include_once('./Services/Object/classes/class.ilObjectLP.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5User.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5VerbList.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5Result.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/XapiReport/class.ilXapiCmi5AbstractRequest.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/exceptions/class.ilXapiCmi5Exception.php');


/**
 * Class ilXapiCmi5StatementEvaluation
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 *
 */
class ilXapiCmi5StatementEvaluation
{
    /**
     * @var array
     * http://adlnet.gov/expapi/verbs/satisfied: should never be sent by AU
     * https://github.com/AICC/CMI-5_Spec_Current/blob/quartz/cmi5_spec.md#939-satisfied
     */
    protected $resultStatusByXapiVerbMap = array(
        ilXapiCmi5VerbList::COMPLETED => "completed",
        ilXapiCmi5VerbList::PASSED => "passed",
        ilXapiCmi5VerbList::FAILED => "failed",
        ilXapiCmi5VerbList::SATISFIED => "passed"
    );

    protected $resultProgressByXapiVerbMap = array(
        ilXapiCmi5VerbList::PROGRESSED => "progressed",
        ilXapiCmi5VerbList::EXPERIENCED => "experienced"
    );
    
    /**
     * @var ilObjXapiCmi5
     */
    protected $object;
    
    /**
     * @var ilLogger
     */
    protected $log;
    
    /**
     * ilXapiStatementEvaluation constructor.
     * @param ilLogger $log
     * @param ilObjXapiCmi5 $object
     */
    public function __construct(ilLogger $log, ilObjXapiCmi5 $object)
    {
        $this->log = $log;
        $this->object = $object;
//        $objLP = ilObjectLP::getInstance($this->object->getId());
//        $this->lpMode = $objLP->getCurrentMode();
        $this->lpMode = $object->getLPMode();
    }
    
    public function evaluateReport(ilXapiCmi5StatementsReport $report)
    {
        foreach ($report->getStatements() as $xapiStatement) {
            #$this->log->debug(
            #	"handle statement:\n".json_encode($xapiStatement, JSON_PRETTY_PRINT)
            #);
            
            // ensure json decoded non assoc
            $xapiStatement = json_decode(json_encode($xapiStatement));
            $cmixUser = $this->getCmixUser($xapiStatement);

            $this->evaluateStatement($xapiStatement, $cmixUser->getUsrId());

            $this->log->debug('update lp for object (' . $this->object->getId() . ')');
            ilLPStatusWrapper::_updateStatus($this->object->getId(), $cmixUser->getUsrId());
        }
    }
    
    public function getCmixUser($xapiStatement)
    {
        $cmixUser = null;
        if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5)
        {
            $cmixUser = ilXapiCmi5User::getInstanceByObjectIdAndUsrIdent(
                $this->object->getId(),
                $xapiStatement->actor->account->name
            );
        }
        else
        {
            $cmixUser = ilXapiCmi5User::getInstanceByObjectIdAndUsrIdent(
                $this->object->getId(),
                str_replace('mailto:', '', $xapiStatement->actor->mbox)
            );
        }
        return $cmixUser;
    }

    public function evaluateStatement($xapiStatement, $usrId)
    {
        global $DIC;
        $xapiVerb = $this->getXapiVerb($xapiStatement);
        $updateStatus = false;
        
        if ($this->isValidXapiStatement($xapiStatement))
        {
            // result status and if exists scaled score
            if ($this->hasResultStatusRelevantXapiVerb($xapiVerb))
            {
                if (!$this->isValidObject($xapiStatement))
                {
                    return false;
                }
                $userResult = $this->getUserResult($usrId);
                
                $oldResultStatus = $userResult->getStatus();
                $newResultStatus = $this->getResultStatusForXapiVerb($xapiVerb);

                // this is for both xapi and cmi5
                if ($this->isResultStatusToBeReplaced($oldResultStatus, $newResultStatus)) {
                    $this->log->debug("isResultStatusToBeReplaced: true");
                    $userResult->setStatus($newResultStatus);
                    $updateStatus = true;
                }

                if ($this->hasXapiScore($xapiStatement)) {
                    $xapiScore = $this->getXapiScore($xapiStatement);
                    $this->log->debug("Score: " . $xapiScore);
                    $userResult->setScore((float) $xapiScore);
                }

                $userResult->save();

                // only cmi5
                if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) 
                {
                    if (($xapiVerb == ilXapiCmi5VerbList::COMPLETED || $xapiVerb == ilXapiCmi5VerbList::PASSED) && $this->isLpModeInterestedInResultStatus($newResultStatus,false)) 
                    {
                        // it is possible to check against authToken usrId!
                        $cmixUser = $this->getCmixUser($xapiStatement);
                        $cmixUser->setSatisfied(true);
                        $cmixUser->save();
                        $this->sendSatisfiedStatement($cmixUser);
                    }
                }
            }
            // result progress (i think only cmi5 relevant)
            if ($this->hasResultProgressRelevantXapiVerb($xapiVerb))
            {
                $userResult = $this->getUserResult($usrId);
                $progressedScore = $this->getProgressedScore($xapiStatement);
                if ($progressedScore !== false && (float) $progressedScore > 0)
                {
                    $userResult->setScore((float) ($progressedScore / 100));
                    $userResult->save();
                }
            }
        }
        return $updateStatus;
    }
    
    protected function isValidXapiStatement($xapiStatement)
    {
        if (!isset($xapiStatement->actor)) {
            return false;
        }
        
        if (!isset($xapiStatement->verb) || !isset($xapiStatement->verb->id)) {
            return false;
        }
        
        if (!isset($xapiStatement->object) || !isset($xapiStatement->object->id)) {
            return false;
        }
        
        return true;
    }

    /**
     * 
     */
    protected function isValidObject($xapiStatement)
    {
        if ($xapiStatement->object->id != $this->object->getActivityId())
        {
            $this->log->debug($xapiStatement->object->id . " != " . $this->object->getActivityId());
            return false;
        }
        return true;
    }

    
    protected function getXapiVerb($xapiStatement)
    {
        return $xapiStatement->verb->id;
    }
    
    protected function getResultStatusForXapiVerb($xapiVerb)
    {
        return $this->resultStatusByXapiVerbMap[$xapiVerb];
    }
    
    protected function hasResultStatusRelevantXapiVerb($xapiVerb)
    {
        return isset($this->resultStatusByXapiVerbMap[$xapiVerb]);
    }
    
    protected function getResultProgressForXapiVerb($xapiVerb)
    {
        return $this->resultProgressByXapiVerbMap[$xapiVerb];
    }

    protected function hasResultProgressRelevantXapiVerb($xapiVerb)
    {
        return isset($this->resultProgressByXapiVerbMap[$xapiVerb]);
    }

    protected function hasXapiScore($xapiStatement)
    {
        if (!isset($xapiStatement->result)) {
            return false;
        }
        
        if (!isset($xapiStatement->result->score)) {
            return false;
        }
        
        if (!isset($xapiStatement->result->score->scaled)) {
            return false;
        }
        
        return true;
    } 

    protected function getXapiScore($xapiStatement)
    {
        return $xapiStatement->result->score->scaled;
    }
    
    protected function getProgressedScore($xapiStatement)
    {
        if (!isset($xapiStatement->result)) {
            return false;
        }
        
        if (!isset($xapiStatement->result->extensions)) {
            return false;
        }
        
        if (!isset($xapiStatement->result->extensions->{'https://w3id.org/xapi/cmi5/result/extensions/progress'})) {
            return false;
        }
        return $xapiStatement->result->extensions->{'https://w3id.org/xapi/cmi5/result/extensions/progress'};
    }

    protected function getUserResult($usrId)
    {
        try {
            $result = ilXapiCmi5Result::getInstanceByObjIdAndUsrId($this->object->getId(), $usrId);
        } catch (ilXapiCmi5Exception $e) {
            $result = ilXapiCmi5Result::getEmptyInstance();
            $result->setObjId($this->object->getId());
            $result->setUsrId($usrId);
        }
        
        return $result;
    }
    
    protected function isResultStatusToBeReplaced($oldResultStatus, $newResultStatus)
    {
        if (!$this->isLpModeInterestedInResultStatus($newResultStatus)) {
            $this->log->debug("isLpModeInterestedInResultStatus: false");
            return false;
        }
        
        if (!$this->doesNewResultStatusDominateOldOne($oldResultStatus, $newResultStatus)) {
            $this->log->debug("doesNewResultStatusDominateOldOne: false");
            return false;
        }
        
        if ($this->needsAvoidFailedEvaluation($oldResultStatus, $newResultStatus)) {
            $this->log->debug("needsAvoidFailedEvaluation: false");
            return false;
        }
        
        return true;
    }
    
    protected function isLpModeInterestedInResultStatus($resultStatus, $deactivated=true)
    {
        if ($this->lpMode == $mode = ilObjXapiCmi5::LP_INACTIVE) {
            return $deactivated;
        }
        switch ($resultStatus) {
            case 'failed':
            case 'passed':
            case 'completed':
                return in_array($this->lpMode, [
                    ilObjXapiCmi5::LP_Completed,
                    ilObjXapiCmi5::LP_Passed,
                    ilObjXapiCmi5::LP_CompletedOrPassed
                ]);
        }
        return false;
    }

    protected function doesNewResultStatusDominateOldOne($oldResultStatus, $newResultStatus)
    {
        if ($oldResultStatus == '' ) {
            return true;
        }
        
        if (in_array($newResultStatus, ['passed', 'failed'])) {
            return true;
        }
        
        if (!in_array($oldResultStatus, ['passed', 'failed'])) {
            return true;
        }
        
        return false;
    }
    
    protected function needsAvoidFailedEvaluation($oldResultStatus, $newResultStatus)
    {
        if (!$this->object->isKeepLpStatusEnabled()) {
            return false;
        }
        
        if ($newResultStatus != 'failed') {
            return false;
        }
        
        return $oldResultStatus == 'completed' || $oldResultStatus == 'passed';
    }

    // ToDo : needs to be tested
    protected function sendSatisfiedStatement($cmixUser)
    {
        global $DIC;
        
        $lrsType = $this->object->getLrsType();
        $defaultLrs = $lrsType->getLrsEndpoint();
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
        
        $satisfiedStatement = $this->object->getSatisfiedStatement($cmixUser);
        $satisfiedStatementParams = [];
        $satisfiedStatementParams['statementId'] = $satisfiedStatement['id'];
        $defaultStatementsUrl = $defaultLrs . "/statements";
        $fallbackStatementsUrl = ($hasFallback) ? $fallbackLrs . "/statements" : "";
        $satisfiedStatementQuery = ilXapiCmi5AbstractRequest::buildQuery($satisfiedStatementParams);
        $defaultSatisfiedStatementUrl = $defaultStatementsUrl . '?' . $satisfiedStatementQuery;
        $fallbackSatisfiedStatementUrl = ($hasFallback) ? $fallbackStatementsUrl . '?' . $satisfiedStatementQuery : "";
        
        $client = new GuzzleHttp\Client();
        $req_opts = array(
            GuzzleHttp\RequestOptions::VERIFY => true,
            GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 10,
            GuzzleHttp\RequestOptions::HTTP_ERRORS => false
        );
        
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
        $promises = array();
        $promises['defaultSatisfiedStatement'] = $client->sendAsync($defaultSatisfiedStatementRequest, $req_opts);
        if ($hasFallback) {
            $promises['fallbackSatisfiedStatement'] = $client->sendAsync($fallbackSatisfiedStatementRequest, $req_opts);          
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
            $this->log->error('error:' . $e->getMessage());
        }
    }
}
