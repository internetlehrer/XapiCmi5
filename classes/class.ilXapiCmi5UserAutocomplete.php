<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilXapiCmi5UserAutocomplete
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 *
 */
class ilXapiCmi5UserAutocomplete extends ilUserAutoComplete
{
    /**
     * @var int
     */
    protected $objId;
    
    /**
     * @param int $objId
     */
    public function __construct($objId)
    {
        parent::__construct();
        $this->objId = $objId;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getFromPart()
    {
        global $DIC;
        
        $fromPart = parent::getFromPart();
        //$DIC->logger()->root()->log($fromPart);
        $fromPart .= "
			INNER JOIN xxcf_users
			ON xxcf_users.obj_id = {$DIC->database()->quote($this->objId, 'integer')}
			AND xxcf_users.usr_id = ud.usr_id
		";
        //$DIC->logger()->root()->log($fromPart);
        return $fromPart;
    }
}
