<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once __DIR__.'/class.ilXapiCmi5AbstractReportLinkBuilder.php';
require_once __DIR__.'/../class.ilXapiCmi5User.php';
/**
 * Class ilXapiCmi5StatementsReportLinkBuilder
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 */
class ilXapiCmi5StatementsReportLinkBuilder extends ilXapiCmi5AbstractReportLinkBuilder
{
    /**
     * @return array
     */
    protected function buildPipeline() : array
    {
        $pipeline = array();
        
        $pipeline[] = $this->buildFilterStage();
        $pipeline[] = $this->buildOrderingStage();
        
        $pipeline[] = array('$facet' => array(
            'stage1' => array(
                array('$group' => array('_id' => null, 'count' => array('$sum' => 1) ))
            ),
            'stage2' => $this->buildLimitStage()
        ));
        
        $pipeline[] = array('$unwind' => '$stage1');
        
        $pipeline[] = array('$project' => array(
                'maxcount' => '$stage1.count',
                'statements' => '$stage2.statement'
        ));
        
        //$log = ilLoggerFactory::getLogger('cmix');
        //$log->debug("aggregation pipeline:\n" . json_encode($pipeline, JSON_PRETTY_PRINT));
        
        //echo '<pre>'.json_encode($pipeline, JSON_PRETTY_PRINT).'</pre>'; exit;
        
        return $pipeline;
    }
    
    protected function buildLimitStage()
    {
        $stage = array(
            array('$skip' => (int) $this->filter->getOffset())
        );
        
        if ($this->filter->getLimit()) {
            $stage[] = array('$limit' => (int) $this->filter->getLimit());
        }
        
        return $stage;
    }
    
    protected function buildFilterStage()
    {
        $stage = array();
        
        $stage['statement.object.objectType'] = 'Activity';
        $stage['statement.object.id'] = [
            '$regex' => '^' . preg_quote($this->filter->getActivityId()) . ''
        ];
        
        $stage['statement.actor.objectType'] = 'Agent';
        
        // require_once 'Services/User/classes/class.ilObjUser.php';
        if ($this->filter->getActor()) {
            // $stage['statement.actor.mbox'] = "mailto:".ilXapiCmi5User::getUsrIdentPlugin($this->filter->getActor()->getUsrId(),$this->getObjId());
            $stage['$or'] = [];
            foreach (ilXapiCmi5User::getUsersForObject($this->getObjId()) as $cmixUser) {
				if ($cmixUser->getUsrId() == $this->filter->getActor()->getUsrId()) {
					$stage['$or'][] = ['statement.actor.mbox' => "mailto:{$cmixUser->getUsrIdent()}"];
				}
            }
        } else {
            $stage['$or'] = [];
            foreach (ilXapiCmi5User::getUsersForObject($this->getObjId()) as $cmixUser) {
                $stage['$or'][] = ['statement.actor.mbox' => "mailto:{$cmixUser->getUsrIdent()}"];
            // foreach (ilXapiCmi5User::getUsersForObjectPlugin($this->getObjId()) as $usr_id) {
                // $stage['$or'][] = ['statement.actor.mbox' => "mailto:".ilXapiCmi5User::getUsrIdentPlugin($usr_id,$this->getObjId())];
            }
        }
        //$GLOBALS['DIC']->logger()->root()->log($stage);
        if ($this->filter->getVerb()) {
            $stage['statement.verb.id'] = $this->filter->getVerb();
        }
        
        if ($this->filter->getStartDate() || $this->filter->getEndDate()) {
            $stage['statement.timestamp'] = array();
            
            if ($this->filter->getStartDate()) {
                $stage['statement.timestamp']['$gt'] = $this->filter->getStartDate()->toXapiTimestamp();
            }
            
            if ($this->filter->getEndDate()) {
                $stage['statement.timestamp']['$lt'] = $this->filter->getEndDate()->toXapiTimestamp();
            }
        }

        return array('$match' => $stage);
    }
    
    protected function buildOrderingStage()
    {
        switch ($this->filter->getOrderField()) {
            case 'object':
                $field = 'statement.object.id';
                break;
                
            case 'verb':
                $field = 'statement.verb.id';
                break;
                
            case 'actor':
                $field = 'statement.actor.name';
                break;
                
            case 'date':
            default:
                $field = 'statement.timestamp';
                break;
        }
        
        $orderingFields = array(
            $field => $this->filter->getOrderDirection() == 'desc' ? -1 : 1
        );
        
        return array('$sort' => $orderingFields);
    }
}
