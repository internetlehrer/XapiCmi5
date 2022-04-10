<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

use ILIAS\DI\Container;


/**
 * xApi plugin: type definition
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 * 
 */ 
class ilXapiCmi5LrsType
{
	const DB_TABLE_NAME = 'xxcf_lrs_types';
    public static function getDbTableName()
    {
        return self::DB_TABLE_NAME;
	}

	const AVAILABILITY_NONE = 0;  // Type is not longer available (error message)
	const AVAILABILITY_EXISTING = 1; // Existing objects of the can be used, but no new created
	const AVAILABILITY_CREATE = 2;  // New objects of this type can be created
	
	const LAUNCH_TYPE_PAGE = "page";
	const LAUNCH_TYPE_LINK = "link";
	const LAUNCH_TYPE_EMBED = "embed";

    const PRIVACY_IDENT_IL_UUID_USER_ID = 0;
    const PRIVACY_IDENT_IL_UUID_EXT_ACCOUNT = 1;
    const PRIVACY_IDENT_IL_UUID_LOGIN = 2;
    const PRIVACY_IDENT_REAL_EMAIL = 3;
    const PRIVACY_IDENT_IL_UUID_RANDOM = 4;
    
    const PRIVACY_NAME_NONE = 0;
    const PRIVACY_NAME_FIRSTNAME = 1;
    const PRIVACY_NAME_LASTNAME = 2;
    const PRIVACY_NAME_FULLNAME = 3;

    const ENDPOINT_STATEMENTS_SUFFIX = 'statements';
	const ENDPOINT_AGGREGATE_SUFFIX = 'statements/aggregate';

	protected $type_id;

	protected $title;
	protected $description;
	protected $availability = self::AVAILABILITY_CREATE;
	protected $lrs_endpoint;
    protected $lrs_key;
    protected $lrs_secret;
	protected $privacy_ident;
	protected $privacy_name;
	protected $force_privacy_settings;
	protected $privacy_comment_default;
	protected $external_lrs;
	
	protected $time_to_delete;
	protected $launch_type = self::LAUNCH_TYPE_EMBED;

	protected $remarks;

	// Only Plugin	
	const ENDPOINT_USE_1 = '1only';
	const ENDPOINT_USE_1DEFAULT_2FALLBACK = '1default_2fallback';
	const ENDPOINT_USE_1FALLBACK_2DEFAULT = '1fallback_2default';
	const ENDPOINT_USE_2 = '2only';
    const ENDPOINT_DELETE_SUFFIX = 'v2/batchdelete/initialise';
    const ENDPOINT_BATCH_SUFFIX ='connection/batchdelete';
    const ENDPOINT_STATE_SUFFIX = 'state';

	const LOG_COMPONENT = "XapiCmi5Plugin";
	protected $lrs_type_id; // ?
	protected $lrs_endpoint_2;
	protected $lrs_key_2;
	protected $lrs_secret_2;
	protected $endpoint_use;
	protected $log;
	
	/**
     * @var bool
     */
    protected $bypassProxyEnabled = false;

	/** @var bool $only_moveon */
	protected $only_moveon = false;

	/** @var bool $achieved */
	protected $achieved = true;

	/** @var bool $answered */
	protected $answered = true;

	/** @var bool $completed */
	protected $completed = true;

	/** @var bool $failed */
	protected $failed = true;

	/** @var bool $initialized */
	protected $initialized = true;

	/** @var bool $passed */
	protected $passed = true;

	/** @var bool $progressed */
	protected $progressed = true;

	/** @var bool $satisfied */
	protected $satisfied = true;

	/** @var bool $terminated */
	protected $terminated = true;

	/** @var bool $hide_data */
	protected $hide_data = false;

	/** @var bool $timestamp */
	protected $timestamp = false;

	/** @var bool $duration */
	protected $duration = true;

	/** @var bool $no_substatements */
	protected $no_substatements = false;

    /** @var bool $noUnallocatableStatements */
    protected $noUnallocatableStatements = false;

    /**
	 * Constructor
	 */
	public function __construct($a_type_id = 0)
	{
		// this uses the cached plugin object
		$this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'XapiCmi5');
		$this->endpoint_use = self::ENDPOINT_USE_1;
		// ToDo
		$this->log = ilLoggerFactory::getLogger('root');
		//$this->log = ilLoggerFactory::getInstance()->getComponentLogger(self::LOG_COMPONENT);

		if ($a_type_id)
		{
			$this->type_id = $a_type_id;
			$this->read();
		}
	}

	/**
	 * @param int id
	 */
	public function setTypeId($a_type_id)
	{
		$this->type_id = $a_type_id;
	}

	/**
	 * @return int id
	 */
	public function getTypeId()
	{
		return $this->type_id;
	}

	/**
	 * @param string title
	 */
	public function setTitle($a_title)
	{
		$this->title = $a_title;
	}

	/**
	 * @return string title
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string description
	 */
	public function setDescription($a_description)
	{
		$this->description = $a_description;
	}

	/**
	 * @return string description
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param integer availability
	 */
	public function setAvailability($a_availability)
	{
		$this->availability = $a_availability;
	}

	/**
	 * @return integer availability
	 */
	public function getAvailability()
	{
		return $this->availability;
	}
	
	/**
     * @return bool
     */
    public function isAvailable()
    {
        if ($this->getAvailability() == self::AVAILABILITY_CREATE) {
            return true;
        }
        
        if ($this->getAvailability() == self::AVAILABILITY_EXISTING) {
            return true;
        }
        
        return false;
    }
    
	/**
	 * @param string time_to_delete
	 */
	public function setTimeToDelete($a_time_to_delete)
	{
		$this->time_to_delete = $a_time_to_delete;
	}

	/**
	 * @return string time_to_delete
	 */
	public function getTimeToDelete()
	{
		return $this->time_to_delete;
	}
	
	public function setLrsEndpoint($a_endpoint)
	{
		$this->lrs_endpoint = $a_endpoint;
	}
	
	public function getLrsEndpoint()
	{
		return $this->lrs_endpoint;
	}

	public function setLrsKey($a_lrs_key)
	{
		$this->lrs_key = $a_lrs_key;
	}

	public function getLrsKey()
	{
		return $this->lrs_key;
	}

	public function setLrsSecret($a_lrs_secret)
	{
		$this->lrs_secret = $a_lrs_secret;
	}

	public function getLrsSecret()
	{
		return $this->lrs_secret;
	}

	// Only Plugin
	public function setLrsEndpoint2(?string $endpoint)
	{
		$this->lrs_endpoint_2 = $endpoint;
	}

	public function getLrsEndpoint2(): ?string
	{
		return $this->lrs_endpoint_2;
	}

	public function setLrsSecret2(?string $a_lrs_secret)
	{
		$this->lrs_secret_2 = $a_lrs_secret;
	}

	public function getLrsSecret2(): ?string
	{
		return $this->lrs_secret_2;
	}

	public function setEndpointUse(string $usage)
	{
		$this->endpoint_use = $usage;
	}

	public function setLrsKey2(?string $lrsKey)
	{
		$this->lrs_key_2 = $lrsKey;
	}

	public function getLrsKey2(): ?string
	{
		return $this->lrs_key_2;
	}

	public function setLrsTypeId($a_option)
	{
		$this->lrs_type_id = $a_option;
	}
	
	public function getLrsTypeId()
	{
		return $this->lrs_type_id;
	}

	/**
	 * @param string $usage
	 * @return string|bool
	 */
	public function getEndpointUse(string $usage = '')
	{
		if( (bool)strlen($usage) ) {
			return $usage === $this->endpoint_use;
		}
		return $this->endpoint_use;
	}

	public function getDefaultLrsEndpoint() {
		switch($this->getEndpointUse()) {
			case self::ENDPOINT_USE_1 :
			case self::ENDPOINT_USE_1DEFAULT_2FALLBACK :
				return $this->getLrsEndpoint(); 
				break;
			case self::ENDPOINT_USE_2 :
			case self::ENDPOINT_USE_1FALLBACK_2DEFAULT :
				return $this->getLrsEndpoint2();
				break; 
		}
	}

	public function getDefaultLrsKey() {
		switch($this->getEndpointUse()) {
			case self::ENDPOINT_USE_1 :
			case self::ENDPOINT_USE_1DEFAULT_2FALLBACK :
				return $this->getLrsKey(); 
				break;
			case self::ENDPOINT_USE_2 :
			case self::ENDPOINT_USE_1FALLBACK_2DEFAULT :
				return $this->getLrsKey2();
				break; 
		}
	}

	public function getDefaultLrsSecret() {
		switch($this->getEndpointUse()) {
			case self::ENDPOINT_USE_1 :
			case self::ENDPOINT_USE_1DEFAULT_2FALLBACK :
				return $this->getLrsSecret(); 
				break;
			case self::ENDPOINT_USE_2 :
			case self::ENDPOINT_USE_1FALLBACK_2DEFAULT :
				return $this->getLrsSecret2();
				break; 
		}
	}

	public function getFallbackLrsEndpoint() {
		switch($this->getEndpointUse()) {
			case self::ENDPOINT_USE_1DEFAULT_2FALLBACK :
				return $this->getLrsEndpoint2(); 
				break;
			case self::ENDPOINT_USE_1FALLBACK_2DEFAULT :
				return $this->getLrsEndpoint();
				break; 
			case self::ENDPOINT_USE_1 : 
			case self::ENDPOINT_USE_2 :
				return "";
				break;
		}
	}

	public function getFallbackLrsKey() {
		switch($this->getEndpointUse()) {
			case self::ENDPOINT_USE_1DEFAULT_2FALLBACK :
				return $this->getLrsKey2(); 
				break;
			case self::ENDPOINT_USE_1FALLBACK_2DEFAULT :
				return $this->getLrsKey();
				break; 
			case self::ENDPOINT_USE_1 : 
			case self::ENDPOINT_USE_2 :
				return "";
				break;
		}
	}

	public function getFallbackLrsSecret() {
		switch($this->getEndpointUse()) {
			case self::ENDPOINT_USE_1DEFAULT_2FALLBACK :
				return $this->getLrsSecret2(); 
				break;
			case self::ENDPOINT_USE_1FALLBACK_2DEFAULT :
				return $this->getLrsSecret();
				break; 
			case self::ENDPOINT_USE_1 : 
			case self::ENDPOINT_USE_2 :
				return "";
				break;
		}
	}

    public function getDefaultBasicAuth() {
        return 'Basic ' . base64_encode($this->getDefaultLrsKey() . ':' . $this->getDefaultLrsSecret());
    }

    public function getFallbackBasicAuth() {
        return 'Basic ' . base64_encode($this->getFallbackLrsKey() . ':' . $this->getFallbackLrsSecret());
    }

	public function setPrivacyIdent($a_option)
	{
		$this->privacy_ident = $a_option;
	}
	
	public function getPrivacyIdent()
	{
		return $this->privacy_ident;
	}

	public function setPrivacyName($a_option)
	{
		$this->privacy_name = $a_option;
	}
	
	public function getPrivacyName()
	{
		return $this->privacy_name;
	}

    /**
     * @return bool
     */
    public function getOnlyMoveon(): bool
    {
        return $this->only_moveon;
    }

    /**
     * @param bool $only_moveon
     */
    public function setOnlyMoveon(bool $only_moveon)
    {
        $this->only_moveon = $only_moveon;
    }

    /**
     * @return bool
     */
    public function getAchieved(): bool
    {
        return $this->achieved;
    }

    /**
     * @param bool $achieved
     */
    public function setAchieved(bool $achieved)
    {
        $this->achieved = $achieved;
    }

    /**
     * @return bool
     */
    public function getAnswered(): bool
    {
        return $this->answered;
    }

    /**
     * @param bool $answered
     */
    public function setAnswered(bool $answered)
    {
        $this->answered = $answered;
    }

    /**
     * @return bool
     */
    public function getCompleted(): bool
    {
        return $this->completed;
    }

    /**
     * @param bool $completed
     */
    public function setCompleted(bool $completed)
    {
        $this->completed = $completed;
    }

    /**
     * @return bool
     */
    public function getFailed(): bool
    {
        return $this->failed;
    }

    /**
     * @param bool $failed
     */
    public function setFailed(bool $failed)
    {
        $this->failed = $failed;
    }

    /**
     * @return bool
     */
    public function getInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * @param bool $initialized
     */
    public function setInitialized(bool $initialized)
    {
        $this->initialized = $initialized;
    }

    /**
     * @return bool
     */
    public function getPassed(): bool
    {
        return $this->passed;
    }

    /**
     * @param bool $passed
     */
    public function setPassed(bool $passed)
    {
        $this->passed = $passed;
    }

    /**
     * @return bool
     */
    public function getProgressed(): bool
    {
        return $this->progressed;
    }

    /**
     * @param bool $progressed
     */
    public function setProgressed(bool $progressed)
    {
        $this->progressed = $progressed;
    }

    /**
     * @return bool
     */
    public function getSatisfied(): bool
    {
        return $this->satisfied;
    }

    /**
     * @param bool $satisfied
     */
    public function setSatisfied(bool $satisfied)
    {
        $this->satisfied = $satisfied;
    }

    /**
     * @return bool
     */
    public function getTerminated(): bool
    {
        return $this->terminated;
    }

    /**
     * @param bool $terminated
     */
    public function setTerminated(bool $terminated)
    {
        $this->terminated = $terminated;
    }

    /**
     * @return bool
     */
    public function getHideData(): bool
    {
        return $this->hide_data;
    }

    /**
     * @param bool $hide_data
     */
    public function setHideData(bool $hide_data)
    {
        $this->hide_data = $hide_data;
    }

    /**
     * @return bool
     */
    public function getTimestamp(): bool
    {
        return $this->timestamp;
    }

    /**
     * @param bool $timestamp
     */
    public function setTimestamp(bool $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return bool
     */
    public function getDuration(): bool
    {
        return $this->duration;
    }

    /**
     * @param bool $duration
     */
    public function setDuration(bool $duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return bool
     */
    public function getNoSubstatements(): bool
    {
        return $this->no_substatements;
    }

    /**
     * @param bool $no_substatements
     */
    public function setNoSubstatements(bool $no_substatements)
    {
        $this->no_substatements = $no_substatements;
    }

    /**
     * @return bool
     */
    public function getNoUnallocatableStatements(): bool
    {
        return $this->noUnallocatableStatements;
    }

    /**
     * @param bool $noUnallocatable
     */
    public function setNoUnallocatableStatements(bool $noUnallocatable)
    {
        $this->noUnallocatableStatements = $noUnallocatable;
    }

    /**
     * @return bool
     */
    public function getForcePrivacySettings()
    {
        return $this->force_privacy_settings;
    }
    
    /**
     * @param bool $force_privacy_settings
     */
    public function setForcePrivacySettings($force_privacy_settings)
    {
        $this->force_privacy_settings = $force_privacy_settings;
	}
	
	public function setPrivacyCommentDefault($a_option)
	{
		$this->privacy_comment_default = $a_option;
	}
	
	public function getPrivacyCommentDefault()
	{
		return $this->privacy_comment_default;
	}

	public function setExternalLrs($a_option)
	{
		$this->external_lrs = $a_option;
	}
	
	public function getExternalLrs()
	{
		return $this->external_lrs;
	}

	/**
	 * @return string launch_type
	 */
	public function getLaunchType()
	{
		return $this->launch_type;
	}

	/**
	 * @param string remarks
	 */
	public function setRemarks($a_remarks)
	{
		$this->remarks = $a_remarks;
	}

	/**
	 * @return string remarks
	 */
	public function getRemarks()
	{
		return $this->remarks;
	}

	/**
     * @return bool
     */
    public function isBypassProxyEnabled() : bool
    {
        return $this->bypassProxyEnabled;
    }

	/**
     * @param bool $bypassProxyEnabled
     */
    public function setBypassProxyEnabled(bool $bypassProxyEnabled)
    {
        $this->bypassProxyEnabled = $bypassProxyEnabled;
    }
    
	/**
	 * @access public
	 */
	public function read()
	{
		global $ilDB, $ilErr;

		$query = "SELECT * FROM " . self::DB_TABLE_NAME . " WHERE type_id = %s";

		$res = $ilDB->queryF($query, ['integer'], [$this->getTypeId()]);
        $row = $ilDB->fetchObject($res);
		if ($row) 
		{
			$this->setTypeId($row->type_id);			
			$this->setTitle($row->title);
			$this->setDescription($row->description);
			$this->setAvailability($row->availability);
			$this->setLrsEndpoint($row->lrs_endpoint);
			$this->setLrsKey($row->lrs_key);
			$this->setLrsSecret($row->lrs_secret);
			$this->setPrivacyIdent($row->privacy_ident);
			$this->setPrivacyName($row->privacy_name);
			$this->setForcePrivacySettings((bool) $row->force_privacy_settings);
			$this->setPrivacyCommentDefault($row->privacy_comment_default);
			$this->setExternalLrs($row->external_lrs);			
			$this->setTimeToDelete($row->time_to_delete);
			$this->setRemarks($row->remarks);
			$this->setBypassProxyEnabled((bool) $row->bypass_proxy);
            $this->setOnlyMoveon((bool)$row->only_moveon);
            $this->setAchieved((bool)$row->achieved);
            $this->setAnswered((bool)$row->answered);
            $this->setCompleted((bool)$row->completed);
            $this->setFailed((bool)$row->failed);
            $this->setInitialized((bool)$row->initialized);
            $this->setPassed((bool)$row->passed);
            $this->setProgressed((bool)$row->progressed);
            $this->setSatisfied((bool)$row->satisfied);
            $this->setTerminated((bool)$row->c_terminated);
            $this->setHideData((bool)$row->hide_data);
            $this->setTimestamp((bool)$row->c_timestamp);
            $this->setDuration((bool)$row->duration);
			$this->setNoSubstatements((bool)$row->no_substatements);
            $this->setNoUnallocatableStatements((bool)$row->no_unallocatable_statements);
			$this->setLrsTypeId($row->lrs_type_id);
			$this->setLrsEndpoint2($row->lrs_endpoint_2);
			$this->setLrsKey2($row->lrs_key_2);
			$this->setLrsSecret2($row->lrs_secret_2);
			$this->setEndpointUse($row->endpoint_use);

			return true;
		}

		return false;
	}

	public function save()
    {
        if ($this->getTypeId()) {
            $this->update();
        } else {
            $this->create();
        }
	}
	
	/**
	 * @access public
	 */
	public function create() 
	{
		global $DIC; /* @var \ILIAS\DI\Container $DIC */
		$this->setTypeId($DIC->database()->nextId(self::DB_TABLE_NAME) );
		$this->update();
	}

	/**
	 * @access public
	 */
	public function update() 
	{
		global $DIC; /* @var \ILIAS\DI\Container $DIC */

		$DIC->database()->replace(
            self::DB_TABLE_NAME,
            array(
                'type_id' => array('integer', $this->getTypeId())
            ),
			 array(
				'title' => array('text', $this->getTitle()),
				'description' => array('clob', $this->getDescription()),
				'availability' => array('integer', $this->getAvailability()),
				'remarks' => array('clob', $this->getRemarks()),
				'time_to_delete' => array('integer', $this->getTimeToDelete()),
				'lrs_endpoint' => array('text', $this->getLrsEndpoint()),
				'lrs_key' => array('text', $this->getLrsKey()),
				'lrs_secret' => array('text', $this->getLrsSecret()),
				'privacy_ident' => array('integer', $this->getPrivacyIdent()),
				'privacy_name' => array('integer', $this->getPrivacyName()),
				'force_privacy_settings' => array('integer', (int) $this->getForcePrivacySettings()),
				'privacy_comment_default' => array('text', $this->getPrivacyCommentDefault()),
				'external_lrs' => array('integer', $this->getExternalLrs()),
				'bypass_proxy' => array('integer', (int) $this->isBypassProxyEnabled()),
                'only_moveon' => array('integer', (int)$this->getOnlyMoveon()),
                'achieved' => array('integer', (int)$this->getAchieved()),
                'answered' => array('integer', (int)$this->getAnswered()),
                'completed' => array('integer', (int)$this->getCompleted()),
                'failed' => array('integer', (int)$this->getFailed()),
                'initialized' => array('integer', (int)$this->getInitialized()),
                'passed' => array('integer', (int)$this->getPassed()),
                'progressed' => array('integer', (int)$this->getProgressed()),
                'satisfied' => array('integer', (int)$this->getSatisfied()),
                'c_terminated' => array('integer', (int)$this->getTerminated()),
                'hide_data' => array('integer', (int)$this->getHideData()),
                'c_timestamp' => array('integer', (int)$this->getTimestamp()),
                'duration' => array('integer', (int)$this->getDuration()),
				'no_substatements' => array('integer', (int)$this->getNoSubstatements()),
                'no_unallocatable_statements' => array('integer', (int)$this->getNoUnallocatableStatements()),
				'lrs_type_id' => array('integer', $this->getLrsTypeId()),
				'lrs_endpoint_2' => array('text', $this->getLrsEndpoint2()),
				'lrs_key_2' => array('text', $this->getLrsKey2()),
				'lrs_secret_2' => array('text', $this->getLrsSecret2()),
				'endpoint_use' => array('text',
					(!$this->getLrsEndpoint2() || !$this->getLrsKey2() || !$this->getLrsSecret2())
						? self::ENDPOINT_USE_1
						: $this->getEndpointUse()
				)
			 )
		);

		return true;
	}

	/**
	 * @access public
	 */
	public function delete()
	{
		global $DIC; /* @var \ILIAS\DI\Container $DIC */
		
		$query = "DELETE FROM " . self::DB_TABLE_NAME . " WHERE type_id = %s";
        $DIC->database()->manipulateF($query, ['integer'], [$this->getTypeId()]);
        
		return true;
	}

	public function getLrsEndpointStatementsAggregationLink()
    {
        return $this->getDefaultLrsEndpointStatementsAggregationLink();
	}
	
	public function getDefaultLrsEndpointStatementsAggregationLink()
    {
        return dirname(dirname($this->getDefaultLrsEndpoint())) . '/api/' . self::ENDPOINT_AGGREGATE_SUFFIX;
    }

    public function getFallbackLrsEndpointStatementsAggregationLink()
    {
        return dirname(dirname($this->getFallbackLrsEndpoint())) . '/api/' . self::ENDPOINT_AGGREGATE_SUFFIX;
    }

	public function getLrsEndpointDeleteLink() 
	{
		return $this->getDefaultLrsEndpointDeleteLink();
	}

    public function getDefaultLrsEndpointDeleteLink()
    {
        return dirname(dirname($this->getDefaultLrsEndpoint())) . '/api/' . self::ENDPOINT_DELETE_SUFFIX;
    }

    public function getFallbackLrsEndpointDeleteLink()
    {
        return dirname(dirname($this->getFallbackLrsEndpoint())) . '/api/' . self::ENDPOINT_DELETE_SUFFIX;
    }

	public function getLrsEndpointBatchLink()
    {
        return $this->getDefaultLrsEndpointBatchLink();
	}
	
    public function getDefaultLrsEndpointBatchLink()
    {
        return dirname(dirname($this->getDefaultLrsEndpoint())) . '/api/' . self::ENDPOINT_BATCH_SUFFIX;
    }

    public function getFallbackLrsEndpointBatchLink()
    {
        return dirname(dirname($this->getFallbackLrsEndpoint())) . '/api/' . self::ENDPOINT_BATCH_SUFFIX;
    }

    public function getDefaultLrsEndpointStateLink()
    {
        return $this->getDefaultLrsEndpoint() . '/activities/' . self::ENDPOINT_STATE_SUFFIX;
    }

    public function getFallbackLrsEndpointStateLink()
    {
        return $this->getFallbackLrsEndpoint() . '/activities/' . self::ENDPOINT_STATE_SUFFIX;
	}
	
	public function getBasicAuth()
    {
        return $this->getDefaultBasicAuth();
    }
    
    public static function buildBasicAuth($lrsKey, $lrsSecret)
    {
        return 'Basic ' . base64_encode("{$lrsKey}:{$lrsSecret}");
    }

    public function getBasicAuthWithoutBasic()
    {
        return self::buildBasicAuthWithoutBasic($this->getDefaultLrsKey(), $this->getDefaultLrsSecret());
    }
    
    public static function buildBasicAuthWithoutBasic($lrsKey, $lrsSecret)
    {
        return base64_encode("{$lrsKey}:{$lrsSecret}");
	}
	
	/**
	 * get a language text
	 *
	 * @param 	string		language variable
	 * @return 	string		interface text
	 */
	function txt($a_langvar)
	{
		return $this->plugin_object->txt($a_langvar);
	}
	
	/**
	 * Get array of options for selecting the type
	 * 
	 * @param	mixed		required availability or null
	 * @return	array		id => title
	 */
	static function _getTypeOptions($a_availability = null) //WEG UK
	{
		global $ilDB;

		$query = "SELECT * FROM xxcf_lrs_types"; //*
		if (isset($a_availability)) {
			$query .= " WHERE availability=" . $ilDB->quote($a_availability, 'integer');
		}
		$res = $ilDB->query($query);

		$options = array();
		while ($row = $ilDB->fetchObject($res)) 
		{
			$options[$row->type_id] = $row->title;
		}
		return $options;
	}

	/**
	 * Get basic data array of all types (without field definitions)
	 * 
	 * @param	boolean		get extended data ('usages')
	 * @param	mixed		required availability or null
	 * @return	array		array of assoc data arrays
	 */
	static function _getTypesData($a_extended = false, $a_availability = null) 
	{
		global $ilDB;

		$query = "SELECT * FROM xxcf_lrs_types";
		if (isset($a_availability)) {
			$query .= " WHERE availability=" . $ilDB->quote($a_availability, 'integer');
		}
		$query .= " ORDER BY type_name";
		$res = $ilDB->query($query);

		$data = array();
		while ($row = $ilDB->fetchAssoc($res)) 
		{
			if ($a_extended) 
			{
				$row['usages'] = self::_countUntrashedUsages($row['type_id']);
			}
			$data[] = $row;
		}
		return $data;
	}

	/**
	 * Count the number of untrashed usages of a type
	 * 
	 * @var		integer		type_id
	 * @return	integer		number of references
	 */
	static function _countUntrashedUsages($a_type_id) {
		global $ilDB;

		$query = "SELECT COUNT(*) untrashed FROM xxcf_settings s"
				. " INNER JOIN object_reference r ON s.obj_id = r.obj_id"
				. " WHERE r.deleted IS NULL "
				. " AND s.lrs_type_id = " . $ilDB->quote($a_type_id, 'integer');

		$res = $ilDB->query($query);
		$row = $ilDB->fetchObject($res);
		return $row->untrashed;
	}
	
	// static function getTypesStruct() {
		// $a_s = array (
			  // 'type_name' 		=> array('type'=>'text', 'maxlength'=>32)
			// , 'title'			=> array('type'=>'text', 'maxlength'=>255)
			// , 'description'		=> array('type'=>'text', 'maxlength'=>4000)
			// , 'availability'	=> array('type'=>'a_integer', 'maxlength'=>1,'options'=>array(2,1,0)) //AVAILABILITY_CREATE,AVAILABILITY_EXISTING,AVAILABILITY_NONE
			// , 'log_level'		=> array('type'=>'a_integer', 'maxlength'=>1, 'options'=>array(0,1,2))
			// // , 'lrs'				=> array('type'=>'headline')
			// , 'lrs_type_id'		=> array('type'=>'a_integer', 'maxlength'=>1, 'options'=>array(0))
			// , 'lrs_endpoint'	=> array('type'=>'text', 'maxlength'=>64, 'required'=>true)
			// , 'lrs_key'			=> array('type'=>'text', 'maxlength'=>64, 'required'=>true)
			// , 'lrs_secret'		=> array('type'=>'text', 'maxlength'=>64, 'required'=>true)
			// , 'external_lrs'	=> array('type'=>'bool')
			// , 'privacy_ident'	=> array('type'=>'a_integer', 'maxlength'=>1, 'options'=>array(0,1,2,3))
			// , 'privacy_name'	=> array('type'=>'a_integer', 'maxlength'=>1, 'options'=>array(0,1,2,3))
			// , 'privacy_comment_default' => array('type'=>'text', 'maxlength'=>2000)
			// , 'remarks'			=> array('type'=>'text', 'maxlength'=>4000)
		// );
		// return $a_s;
	// }
	
	static function getCountTypesForCreate() {
		global $ilDB;
		$query = "SELECT COUNT(*) counter FROM xxcf_lrs_types WHERE availability = " . $ilDB->quote(self::AVAILABILITY_CREATE, 'integer');
		$res = $ilDB->query($query);
		$row = $ilDB->fetchObject($res);
		return $row->counter;
	}

	public function getLog() {
		return $this->log;
	}

	public function getLogMessage($msg) {
		return self::LOG_COMPONENT . ": " . $msg;
	}
	
	// ToDo !!
	public function updatePrivacySettingsFromLrsType()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $query = "
			UPDATE xxcf_settings
			SET privacy_ident = %s, 
                privacy_name = %s, 
                only_moveon = %s, 
                achieved = %s, 
                answered = %s, 
                completed = %s, 
                failed = %s, 
                initialized = %s, 
                passed = %s, 
                progressed = %s, 
                satisfied = %s, 
                c_terminated = %s, 
                hide_data = %s, 
                c_timestamp = %s, 
                duration = %s, 
                no_substatements = %s,
			    no_unallocatable_statements = %s
            WHERE lrs_type_id = %s
		";
        
        $DIC->database()->manipulateF(
            $query,
            ['text',
             'text',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer',
             'integer'
            ],
            [$this->getPrivacyIdent(),
             $this->getPrivacyName(),
             $this->getOnlyMoveon(),
             $this->getAchieved(),
             $this->getAnswered(),
             $this->getCompleted(),
             $this->getFailed(),
             $this->getInitialized(),
             $this->getPassed(),
             $this->getProgressed(),
             $this->getSatisfied(),
             $this->getTerminated(),
             $this->getHideData(),
             $this->getTimestamp(),
             $this->getDuration(),
             $this->getNoSubstatements(),
             $this->getNoUnallocatableStatements(),
             $this->getTypeId()
            ]
        );
    }
}
?>