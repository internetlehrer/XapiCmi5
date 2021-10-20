<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once __DIR__.'/class.ilXapiCmi5AbstractRequest.php';
require_once __DIR__.'/class.ilXapiCmi5StatementsReport.php';

/**
 * Class ilXapiCmi5StatementsReportRequest
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 */
class ilXapiCmi5StatementsReportRequest extends ilXapiCmi5AbstractRequest
{
    /**
     * @var ilXapiCmi5StatementsReportLinkBuilder
     */
    protected $linkBuilder;
    
    /**
     * ilXapiCmi5StatementsReportRequest constructor.
     * @param string $basicAuth
     * @param ilXapiCmi5StatementsReportLinkBuilder $linkBuilder
     */
    public function __construct(string $basicAuth, ilXapiCmi5StatementsReportLinkBuilder $linkBuilder)
    {
        parent::__construct($basicAuth);
        $this->linkBuilder = $linkBuilder;
    }
    
    /**
     * @return ilXapiCmi5StatementsReport $report
     */
    public function queryReport($objId)
    {
        $reportResponse = $this->sendRequest($this->linkBuilder->getUrl());

        //$GLOBALS['DIC']->logger()->root()->log(var_export($reportResponse,TRUE));
        
        $report = new ilXapiCmi5StatementsReport($reportResponse, $objId);

        //$GLOBALS['DIC']->logger()->root()->log(var_export($report,TRUE));
        
        return $report;
    }
}
