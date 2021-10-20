<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilXapiCmi5StatementsReportFilter
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 */
class ilXapiCmi5StatementsReportFilter
{
    /**
     * @var string
     */
    protected $activityId;
    
    /**
     * @var int
     */
    protected $limit;
    
    /**
     * @var int
     */
    protected $offset;
    
    /**
     * @var string
     */
    protected $orderField;
    
    /**
     * @var string
     */
    protected $orderDirection;
    
    /**
     * @var ilXapiCmi5User
     */
    protected $actor;
    
    /**
     * @var string
     */
    protected $verb;
    
    /**
     * @var ilXapiCmi5DateTime
     */
    protected $startDate;
    
    /**
     * @var ilXapiCmi5DateTime
     */
    protected $endDate;
    
    /**
     * @return string
     */
    public function getActivityId()
    {
        return $this->activityId;
    }
    
    /**
     * @param string $activityId
     */
    public function setActivityId($activityId)
    {
        $this->activityId = $activityId;
    }
    
    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }
    
    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }
    
    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }
    
    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }
    
    /**
     * @return string
     */
    public function getOrderField()
    {
        return $this->orderField;
    }
    
    /**
     * @param string $orderField
     */
    public function setOrderField($orderField)
    {
        $this->orderField = $orderField;
    }
    
    /**
     * @return string
     */
    public function getOrderDirection()
    {
        return $this->orderDirection;
    }
    
    /**
     * @param string $orderDirection
     */
    public function setOrderDirection($orderDirection)
    {
        $this->orderDirection = $orderDirection;
    }
    
    /**
     * @return ilXapiCmi5User
     */
    public function getActor()
    {
        return $this->actor;
    }
    
    /**
     * @param ilXapiCmi5User $actor
     */
    public function setActor($actor)
    {
        $this->actor = $actor;
    }
    
    /**
     * @return string
     */
    public function getVerb()
    {
        return $this->verb;
    }
    
    /**
     * @param string $verb
     */
    public function setVerb($verb)
    {
        $this->verb = $verb;
    }
    
    /**
     * @return ilXapiCmi5DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }
    
    /**
     * @param ilXapiCmi5DateTime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }
    
    /**
     * @return ilXapiCmi5DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }
    
    /**
     * @param ilXapiCmi5DateTime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }
}
