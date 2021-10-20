<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once __DIR__.'/XapiReport/class.ilXapiCmi5InvalidStatementsFilterException.php';
require_once __DIR__.'/XapiReport/class.ilXapiCmi5StatementsReportFilter.php';
require_once __DIR__.'/XapiReport/class.ilXapiCmi5StatementsReportLinkBuilder.php';
require_once __DIR__.'/XapiReport/class.ilXapiCmi5StatementsReportRequest.php';
require_once __DIR__.'/XapiDelete/class.ilXapiCmi5StatementsDeleteRequest.php';
require_once __DIR__.'/class.ilXapiCmi5VerbList.php';
require_once __DIR__.'/class.ilXapiCmi5User.php';
require_once __DIR__.'/class.ilXapiCmi5UserAutocomplete.php';
require_once __DIR__.'/class.ilXapiCmi5StatementsTableGUI.php';
require_once __DIR__.'/class.ilObjXapiCmi5GUI.php';
require_once __DIR__.'/class.ilObjXapiCmi5.php';
require_once __DIR__.'/class.ilObjXapiCmi5Access.php';


/**
 * Class ilXapiCmi5ContentGUI
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 * 
 */
class ilXapiCmi5StatementsGUI
{    
    /**
     * @var ilObjXapiCmi5
     */
    protected $object;

    /**
     * @var ilObjXapiCmi5GUI
     */
    protected $gui;
    
    /** ToDo
     * @var ilXapiCmi5Access
     */
    //protected $access;
    
    /**
     * @param ilObjXapiCmi5 $object
     */
    public function __construct(ilObjXapiCmi5GUI $gui)
    {
        $this->gui = $gui;
        $this->object = $gui->object;
    }

    public function executeCommand()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        /*
        if (!$this->gui->hasStatementsAccess()) {
            throw new ilXapiCmi5Exception('access denied!');
        }
        */
        //ToDo: cleaning!!!!!!

        $cmd = $DIC->ctrl()->getCmd('show') . 'Cmd';
        if ($cmd === "statementsCmd") {
            return $this->showCmd();
        }
        elseif ($cmd === "asyncUserAutocompleteCmd") {
            $this->asyncUserAutocompleteCmd();
        }
        elseif ($cmd === "asyncDeleteCmd") {
            $this->asyncDeleteCmd();
        }
        else { // ToDo
            return $this->{$cmd}();
        }
    }

    protected function showCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        //ToDo: check rights
        $table = $this->buildTableGUI();
        
        try {
            if (isset($_GET['error'])) {
                ilUtil::sendFailure($_GET['error']); // ToDo
            }
            if (isset($_GET['success'])) {
                $scope = $_SESSION['xxcf_delete_scope'];
                $txt = $this->gui->getText("deleted_${scope}_data");
                ilUtil::sendSuccess($txt);
            }
            if (isset($_GET['reset'])) {
                $table->resetFilter();
                $table->resetOffset();
                $table = $this->buildTableGUI();
            }
            $statementsFilter = $this->initFilter($table);
            $this->initTableData($table, $statementsFilter);
        } catch (Exception $e) {
            ilUtil::sendFailure($e->getMessage());
            $table->setData(array());
            $table->setMaxCount(0);
            $table->resetOffset();
        }
        
        return  $this->getPage($table);
    }

    protected function asyncDeleteCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();
        $subcmd = $_GET['subcmd'];
        $batchid = $_GET['batchid'];
        if ($subcmd == 'delete') {
            $scope = $_GET['scope'];
            $_SESSION['xxcf_delete_scope'] = $scope;
            $DIC->logger()->root()->log("SCOPE: " . $scope);
            $data = $this->asyncDeleteDataCmd($scope);
            header('Content-type: application/json');
            echo json_encode($data);
            exit;
        }
    }

    protected function asyncDeleteDataCmd(string $scope) {
        global $DIC;
        
        //ToDo: Check if user has rights to see table and filter
        $table = $this->buildTableGUI();
        $ret = array();
        try {
            $statementsFilter = $this->initFilter($table);
            $deleteRequest = new ilXapiCmi5StatementsDeleteRequest(
                $this->object->getId(),
                $this->object->getLrsType()->getTypeId(),
                $this->object->getActivityId(),
                NULL,
                $scope,
                $statementsFilter
            );
            $done = $deleteRequest->delete();
            $ret['done'] = $done;
        } catch (Exception $e) { 
            $ret['done'] = false;
        }
        return $ret;
    }

    protected function getDeleteModal($scope, $table)
    {
        // ToDo: Cancel Action -> terminate batchDelete?
        global $DIC, $lng; /* @var \ILIAS\DI\Container $DIC */
        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();
        $message = $this->gui->getText("confirm_delete_${scope}_data");
        $messageTitle = $this->gui->getText("title_delete_${scope}_data");
        $deleteUrl = preg_replace('/amp\;/','',$DIC->ctrl()->getLinkTarget($this->gui, 'asyncDelete'))."&subcmd=delete&scope=${scope}";
        $statementsUrl = preg_replace('/amp\;/','',$DIC->ctrl()->getLinkTarget($this->gui, 'statements'))."&reset=true";
        $dataCount = 0;
        $messageDetails = $this->gui->getText("statements_count") . ": ";
        $filter = $this->initFilter($table);
        $deleteRequest = new ilXapiCmi5StatementsDeleteRequest(
            $this->object->getId(),
            $this->object->getLrsType()->getTypeId(),
            $this->object->getActivityId(),
            NULL,
            NULL,
            $filter
        );
        if ($scope === ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_FILTERED)
        {
            $dataCount = $deleteRequest->_lookUpDataCount(ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_FILTERED);
            $messageDetails .= $dataCount;
            $start = $filter->getStartDate();
            $end = $filter->getEndDate();
            $verb = $filter->getVerb();
            $messageDetails .= '</br></br>' . $this->gui->getText('filtered_by').':';
            if ($filter->getActor() && ilObjXapiCmi5Access::hasOutcomesAccess($this->object)) {
                $messageDetails .= '</br>User: ' . $table->getFilterItemByPostVar('actor')->getValue();
            }
            if ($verb) {
                $messageDetails .= "</br>Verb: ${verb}";
            }
            if ($start) {
                $messageDetails .= "</br>Period: ${start}        ${end}";
            }
        }
        if ($scope === ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_ALL)
        {
            $dataCount = $deleteRequest->_lookUpDataCount(ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_ALL);
            $messageDetails .= $dataCount;
        }
        if ($scope === ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_OWN)
        {
            $dataCount = $deleteRequest->_lookUpDataCount(ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_OWN);
            $messageDetails .= $dataCount;
        }
        
        $modal = $factory->modal()->roundtrip(
            $messageTitle,
            $factory->legacy("<pre>${message}</pre><pre>${messageDetails}</pre>")
        );
        $buttons = [
                        $factory->button()->primary($lng->txt('delete'), '#')
                        ->withLoadingAnimationOnClick(true)
                        ->withAdditionalOnLoadCode(function ($id) use ($deleteUrl, $statementsUrl) {
                            return
                                "$('#$id').click(function(e) {
                                        $.get('$deleteUrl', function(data) {
                                            if (data['done'] === true) {
                                                location.assign('$statementsUrl'+'&success=deletion');
                                            }
                                            else {
                                                location.assign('$statementsUrl'+'&error=deletiontimeout');
                                            }
                                        });
                                });";
                        })
                    ];
        $modal = $modal
        ->withCancelButtonLabel('close')
        ->withActionButtons($buttons);
        return $modal;
    }

    protected function getDeleteButton($table, $scope) {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();
        $isAvailable = true;
        if ($scope === ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_FILTERED) {
            $isAvailable = $this->checkFilter($table) && ($table->dataExists());
        }
        if ($scope === ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_ALL) {
            $isAvailable = $table->dataExists();
        }
        if ($scope === ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_OWN) {
            $isAvailable = $this->checkOwnData();
        }
        $modal = $this->getDeleteModal($scope, $table);
        if ($isAvailable) {
            $btnDelete = $DIC->ui()->factory()->button()->standard($this->gui->getText("delete_${scope}_data"), "#")
            ->withOnClick($modal->getShowSignal());
        }
        else {
            $btnDelete = $DIC->ui()->factory()->button()->standard($this->gui->getText("delete_${scope}_data"), "#")
            ->withUnavailableAction();
        }
        $out = [];
        $out[] = $btnDelete;
        $out[] = $modal;
        return $renderer->render($out);
    }

    protected function getPage($table) {
        unset($_SESSION['xxcf_delete_scope']);
        $html = '';
        if (ilObjXapiCmi5Access::hasDeleteXapiDataAccess($this->object)) {
            $html = $this->getDeleteButton($table,ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_FILTERED) .
                    $this->getDeleteButton($table,ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_OWN);
        }
        if (ilObjXapiCmi5Access::hasOutcomesAccess($this->object) && ilObjXapiCmi5Access::hasDeleteXapiDataAccess($this->object)) {
            $html .= $this->getDeleteButton($table,ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_ALL);
        }
        $html .= $table->getHTML();
        return $html;
    }

    private function checkDeleteState($scope, $filter) {
        global $DIC;
        if ($scope === ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_ALL || $scope === ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_OWN) {
            return true;
        }
        if ($filter->getActor()) { // ToDo: only in Multicactor Mode?
            if ($filter->getVerb() || $filter->getStartDate() || $filter->getEndDate()) {
                return false;
            }
            else {
                return true;
            }
        }
        return false;
    }

    private function checkFilteredData($filter) {
        if ($filter->getActor() || $filter->getVerb() || $filter->getStartDate() || $filter->getEndDate()) {
            return true;
        }
        else {
            return false;
        }
    }

    private function checkOwnData() {
        global $DIC;
        if (!ilXapiCmi5User::userExists($this->object->getId(),$DIC->user()->getId())) {
            return false;
        }
        $deleteRequest = new ilXapiCmi5StatementsDeleteRequest(
            $this->object->getId(),
            $this->object->getLrsType()->getTypeId(),
            $this->object->getActivityId(),
            NULL,
            ilXapiCmi5StatementsDeleteRequest::DELETE_SCOPE_OWN
        );
        $dataCount = $deleteRequest->_lookUpDataCount();
        if ($dataCount > 0) {
            return true;
        }
        else {
            return false;
        }
    }

    
    protected function resetFilterCmd()
    {
        $table = $this->buildTableGUI();
        $table->resetFilter();
        $table->resetOffset();
        return $this->showCmd();
    }
    
    protected function applyFilterCmd()
    {
        $table = $this->buildTableGUI();
        $table->writeFilterToSession();
        $table->resetOffset();
        return $this->showCmd();
    }

    protected function checkFilter($table) {
        global $DIC;
        foreach ($table->filter as $item) {
            if ($item && $item !== "") {
                return true;
            }
        }
        return false;
    }
    
    protected function initFilter(ilXapiCmi5StatementsTableGUI $table ) {
        $statementsFilter = new ilXapiCmi5StatementsReportFilter();    
        $statementsFilter->setActivityId($this->object->getActivityId());
        $this->initLimitingAndOrdering($statementsFilter, $table);
        $this->initActorFilter($statementsFilter, $table);
        $this->initVerbFilter($statementsFilter, $table);
        $this->initPeriodFilter($statementsFilter, $table);
        return $statementsFilter;
    }

    protected function initLimitingAndOrdering(ilXapiCmi5StatementsReportFilter $filter, ilXapiCmi5StatementsTableGUI $table)
    {
        $table->determineOffsetAndOrder();
        
        $filter->setLimit($table->getLimit());
        $filter->setOffset($table->getOffset());
        
        $filter->setOrderField($table->getOrderField());
        $filter->setOrderDirection($table->getOrderDirection());
    }
    
    protected function initActorFilter(ilXapiCmi5StatementsReportFilter $filter, ilXapiCmi5StatementsTableGUI $table)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        if (ilObjXapiCmi5Access::hasOutcomesAccess($this->object)) {
            $actor = $table->getFilterItemByPostVar('actor')->getValue();
            if (strlen($actor)) {
                $usrId = ilObjUser::getUserIdByLogin($actor);
                if ($usrId) {
                    $filter->setActor(new ilXapiCmi5User($this->object->getId(), $usrId, $this->object->getPrivacyIdent()));
                } else {
					ilUtil::sendFailure("given actor ({$actor}) is not a valid actor for object ({$this->object->getId()})");
                }
            }
        } else {
            $filter->setActor(new ilXapiCmi5User($this->object->getId(), $DIC->user()->getId()));
        }
    }
    
    protected function initVerbFilter(ilXapiCmi5StatementsReportFilter $filter, ilXapiCmi5StatementsTableGUI $table)
    {
        $verb = urldecode($table->getFilterItemByPostVar('verb')->getValue());
        
        if (ilXapiCmi5VerbList::getInstance()->isValidVerb($verb)) {
            $filter->setVerb($verb);
        }
    }
    
    protected function initPeriodFilter(ilXapiCmi5StatementsReportFilter $filter, ilXapiCmi5StatementsTableGUI $table)
    {
        $period = $table->getFilterItemByPostVar('period');
        
        if ($period->getStartXapiDateTime()) {
            $filter->setStartDate($period->getStartXapiDateTime());
        }
        
        if ($period->getEndXapiDateTime()) {
            $filter->setEndDate($period->getEndXapiDateTime());
        }
    }
    
    protected function initTable() {
        $table = $this->buildTableGUI();

    }

    public function asyncUserAutocompleteCmd()
    {
        $auto = new ilXapiCmi5UserAutocomplete($this->object->getId());
        $auto->setSearchFields(array('login','firstname','lastname','email'));
        $auto->setResultField('login');
        $auto->enableFieldSearchableCheck(true);
        $auto->setMoreLinkAvailable(true);
        
        //$auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
        
        $result = json_decode($auto->getList(ilUtil::stripSlashes($_REQUEST['term'])), true);
        
        echo json_encode($result);
        exit();
    }
    
    /**
     * @param ilXapiCmi5StatementsTableGUI $table
     * @param ilXapiCmi5StatementsReportFilter $filter
     */
    protected function initTableData(ilXapiCmi5StatementsTableGUI $table, ilXapiCmi5StatementsReportFilter $filter)
    {
        global $DIC;
        if (ilObjXapiCmi5Access::hasOutcomesAccess($this->object)) {
            if (!ilXapiCmi5User::userExists($this->object->getId())) {
                $table->setData(array());
                $table->setMaxCount(0);
                $table->resetOffset();
                return;
            }
        }
        else {
            $usrId = $DIC->user()->getId();
            if (!ilXapiCmi5User::userExists($this->object->getId(),$usrId)) {
                $table->setData(array());
                $table->setMaxCount(0);
                $table->resetOffset();
                return;
            }
        }
        
        $linkBuilder = new ilXapiCmi5StatementsReportLinkBuilder(
            $this->object->getId(),
            $this->object->getLrsType()->getDefaultLrsEndpointStatementsAggregationLink(),
            $filter
        );
        
        $request = new ilXapiCmi5StatementsReportRequest(
            $this->object->getLrsType()->getDefaultBasicAuth(),
            $linkBuilder
        );
        $statementsReport = $request->queryReport($this->object->getId());
        $data = $statementsReport->getTableData();
        $table->setData($data);
        $table->setMaxCount($statementsReport->getMaxCount());
    }

    /**
     * @return ilXapiCmi5StatementsTableGUI
     */
    protected function buildTableGUI() : ilXapiCmi5StatementsTableGUI
    {
        $isMultiActorReport = ilObjXapiCmi5Access::hasOutcomesAccess($this->object);        
        $table = new ilXapiCmi5StatementsTableGUI($this->gui, 'show', $isMultiActorReport);
        $table->setFilterCommand('applyFilter');
        $table->setResetCommand('resetFilter');
        return $table;
    }
}
