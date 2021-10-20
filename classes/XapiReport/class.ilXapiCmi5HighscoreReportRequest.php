<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilXapiCmi5HighscoreReportRequest
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 */
class ilXapiCmi5HighscoreReportRequest extends ilXapiCmi5AbstractRequest
{
    /**
     * @var ilXapiCmi5Type
     */
    protected $lrsType;
    
    /**
     * @var ilXapiCmi5StatementsReportLinkBuilder
     */
    protected $linkBuilder;
    
    /**
     * ilXapiCmi5HighscoreReportRequest constructor.
     * @param string $basicAuth
     * @param ilXapiCmi5HighscoreReportLinkBuilder $linkBuilder
     */
    public function __construct(string $basicAuth, ilXapiCmi5StatementsReportLinkBuilder $linkBuilder)
    {
        parent::__construct($basicAuth);
        $this->linkBuilder = $linkBuilder;
    }
    
    /**
     * @return ilXapiCmi5HighscoreReport
     */
    public function queryReport($objId)
    {
        $reportResponse = $this->sendRequest($this->linkBuilder->getUrl());
        
        $report = new ilXapiCmi5HighscoreReport($reportResponse, $objId);
        
        return $report;
    }
}
