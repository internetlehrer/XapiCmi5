<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

use ILIAS\DI\Container;

/**
 * xApi plugin: type definition
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
class ilXapiCmi5Type
{

	const AVAILABILITY_NONE = 0;  // Type is not longer available (error message)
	const AVAILABILITY_EXISTING = 1; // Existing objects of the can be used, but no new created
	const AVAILABILITY_CREATE = 2;  // New objects of this type can be created
	
	const LAUNCH_TYPE_PAGE = "page";
	const LAUNCH_TYPE_LINK = "link";
	const LAUNCH_TYPE_EMBED = "embed";

    const PRIVACY_IDENT_CODE = 0;
    const PRIVACY_IDENT_NUMERIC = 1;
    const PRIVACY_IDENT_LOGIN = 2;
    const PRIVACY_IDENT_EMAIL = 3;
	const PRIVACY_IDENT_RANDOM = 4;
	const ENDPOINT_USE_1 = '1only';
	const ENDPOINT_USE_1DEFAULT_2FALLBACK = '1default_2fallback';
	const ENDPOINT_USE_1FALLBACK_2DEFAULT = '1fallback_2default';
	const ENDPOINT_USE_2 = '2only';

    const ENDPOINT_STATEMENTS_SUFFIX = 'statements';
    const ENDPOINT_AGGREGATE_SUFFIX = 'statements/aggregate';
    const ENDPOINT_DELETE_SUFFIX = 'v2/batchdelete/initialise';
    const ENDPOINT_BATCH_SUFFIX ='connection/batchdelete';
    const ENDPOINT_STATE_SUFFIX = 'state';

	const LOG_COMPONENT = "XapiCmi5Plugin";
	const LOG_LEVEL_OFF = 0;
	const LOG_LEVEL_ERROR_AND_WARNING = 1;
	const LOG_LEVEL_DEBUG = 2;

	private $type_id;
	private $name;
	private $title;
	private $description;
	private $template;
	private $launch_type = self::LAUNCH_TYPE_EMBED;
	
	private $availability = self::AVAILABILITY_CREATE;
	private $remarks;
	private $time_to_delete;
	private $log_level;
	private $lrs_type_id;
	private $lrs_endpoint_1;
	private $lrs_key_1;
	private $lrs_secret_1;
	private $lrs_endpoint_2;
	private $lrs_key_2;
	private $lrs_secret_2;
	private $endpoint_use;
	private $privacy_ident;
	private $privacy_name;
	private $log;
	
	/** @var bool $force_privacy_settings */
	private $force_privacy_settings = false;

	/** @var bool $only_moveon */
	private $only_moveon = false;

	/** @var bool $achieved */
	private $achieved = true;

	/** @var bool $answered */
	private $answered = true;

	/** @var bool $completed */
	private $completed = true;

	/** @var bool $failed */
	private $failed = true;

	/** @var bool $initialized */
	private $initialized = true;

	/** @var bool $passed */
	private $passed = true;

	/** @var bool $progressed */
	private $progressed = true;

	/** @var bool $satisfied */
	private $satisfied = true;

	/** @var bool $terminated */
	private $terminated = true;

	/** @var bool $hide_data */
	private $hide_data = false;

	/** @var bool $timestamp */
	private $timestamp = false;

	/** @var bool $duration */
	private $duration = true;

	/** @var bool $no_substatements */
	private $no_substatements = false;

	/** @var bool $external_lrs */
	private $external_lrs = false;

	/** @var string $privacy_comment_default */
	private $privacy_comment_default;


	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct($a_type_id = 0)
	{
		// this uses the cached plugin object
		$this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'XapiCmi5');

		$this->endpoint_use = self::ENDPOINT_USE_1;
		$this->log = ilLoggerFactory::getLogger('root');
		//$this->log = ilLoggerFactory::getInstance()->getComponentLogger(self::LOG_COMPONENT);
		if ($a_type_id)
		{
			$this->type_id = $a_type_id;
			$this->read();
		}
	}

	/**
	 * Set Type Id
	 * @param int id
	 */
	public function setTypeId($a_type_id)
	{
		$this->type_id = $a_type_id;
	}

	/**
	 * Get Type Id
	 * @return int id
	 */
	public function getTypeId()
	{
		return $this->type_id;
	}

	/**
	 * Set Name
	 * @param string name
	 */
	public function setName($a_name)
	{
		$this->name = $a_name;
	}

	/**
	 * Get Name
	 * @return string name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set Title
	 * @param string title
	 */
	public function setTitle($a_title)
	{
		$this->title = $a_title;
	}

	/**
	 * Get Title
	 * @return string title
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set Description
	 * @param string description
	 */
	public function setDescription($a_description)
	{
		$this->description = $a_description;
	}

	/**
	 * Get Description
	 * @return string description
	 */
	public function getDescription()
	{
		return $this->description;
	}



	/**
	 * Get Launch Tape
	 * @return string launch_type
	 */
	public function getLaunchType()
	{
		return $this->launch_type;
	}


	/**
	 * Set Availability
	 *
	 * @param integer availability
	 */
	public function setAvailability($a_availability)
	{
		$this->availability = $a_availability;
	}

	/**
	 * get Availability
	 *
	 * @return integer availability
	 */
	public function getAvailability()
	{
		return $this->availability;
	}

	/**
	 * Set Remarks
	 *
	 * @param string remarks
	 */
	public function setRemarks($a_remarks)
	{
		$this->remarks = $a_remarks;
	}

	/**
	 * Get Remarks
	 *
	 * @return string remarks
	 */
	public function getRemarks()
	{
		return $this->remarks;
	}
	
	/**
	 * Set time to delete
	 *
	 * @param string time_to_delete
	 */
	public function setTimeToDelete($a_time_to_delete)
	{
		$this->time_to_delete = $a_time_to_delete;
	}

	/**
	 * Get time to time_to_delete
	 *
	 * @return string time_to_delete
	 */
	public function getTimeToDelete()
	{
		return $this->time_to_delete;
	}
	
	/**
	 * Set use logs
	 *
	 * @param string $a_option
	 */
	public function setLogLevel($a_option)
	{
		$this->log_level = $a_option;
	}

	/**
	 * Get use logs
	 *
	 * @return string log_level
	 */
	public function getLogLevel()
	{
		return $this->log_level;
	}
	
	
	
	public function setLrsTypeId($a_option)
	{
		$this->lrs_type_id = $a_option;
	}
	
	public function getLrsTypeId()
	{
		return $this->lrs_type_id;
	}

	public function setLrsEndpoint1($a_endpoint)
	{
		$this->lrs_endpoint_1 = $a_endpoint;
	}
	
	public function getLrsEndpoint1()
	{
		return $this->lrs_endpoint_1;
	}

	public function setLrsEndpoint2(?string $endpoint)
	{
		$this->lrs_endpoint_2 = $endpoint;
	}

	public function getLrsEndpoint2(): ?string
	{
		return $this->lrs_endpoint_2;
	}

	public function setLrsKey1($a_lrs_key)
	{
		$this->lrs_key_1 = $a_lrs_key;
	}

	public function getLrsKey1()
	{
		return $this->lrs_key_1;
	}

	public function setLrsKey2(?string $lrsKey)
	{
		$this->lrs_key_2 = $lrsKey;
	}

	public function getLrsKey2(): ?string
	{
		return $this->lrs_key_2;
	}

	public function setLrsSecret1($a_lrs_secret)
	{
		$this->lrs_secret_1 = $a_lrs_secret;
	}

	public function getLrsSecret1()
	{
		return $this->lrs_secret_1;
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
				return $this->getLrsEndpoint1(); 
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
				return $this->getLrsKey1(); 
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
				return $this->getLrsSecret1(); 
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
				return $this->getLrsEndpoint1();
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
				return $this->getLrsKey1();
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
				return $this->getLrsSecret1();
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


	public function setExternalLrs($a_option)
	{
		$this->external_lrs = $a_option;
	}
	
	public function getExternalLrs()
	{
		return $this->external_lrs;
	}

	public function setPrivacyCommentDefault($a_option)
	{
		$this->privacy_comment_default = $a_option;
	}
	
	public function getPrivacyCommentDefault()
	{
		return $this->privacy_comment_default;
	}

	/**
	 * Read function
	 *
	 * @access public
	 */
	public function read()
	{
		global $ilDB, $ilErr;

		$query = 'SELECT * FROM xxcf_data_types WHERE type_id = '
				. $ilDB->quote($this->getTypeId(), 'integer');

		$res = $ilDB->query($query);
		$row = $ilDB->fetchObject($res);
		if ($row) 
		{
			$this->type_id = $row->type_id;
			$this->setName($row->type_name);
			$this->setTitle($row->title);
			$this->setDescription($row->description);
			$this->setAvailability($row->availability);
			$this->setRemarks($row->remarks);
			$this->setTimeToDelete($row->time_to_delete);
			$this->setLogLevel($row->log_level);
			$this->setLrsTypeId($row->lrs_type_id);
			$this->setLrsEndpoint1($row->lrs_endpoint_1);
			$this->setLrsKey1($row->lrs_key_1);
			$this->setLrsSecret1($row->lrs_secret_1);
			$this->setLrsEndpoint2($row->lrs_endpoint_2);
			$this->setLrsKey2($row->lrs_key_2);
			$this->setLrsSecret2($row->lrs_secret_2);
			$this->setEndpointUse($row->endpoint_use);
			$this->setPrivacyIdent($row->privacy_ident);
			$this->setPrivacyName($row->privacy_name);
			$this->setPrivacyCommentDefault($row->privacy_comment_default);
			$this->setExternalLrs($row->external_lrs);
            $this->setForcePrivacySettings((bool) $row->force_privacy_settings);
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
		}
		return false;
	}

	/**
	 * Create a new type
	 *
	 * @access public
	 */
	public function create() {
		global $ilDB;

		// $this->type_id = $ilDB->nextId('xxcf_data_types');
		$this->setTypeId( $ilDB->nextId('xxcf_data_types') );
		$this->update();
	}

	/**
	 * Update function
	 *
	 * @access public
	 */
	public function update() {
		global $ilDB;

		$ilDB->replace('xxcf_data_types', 
			 array(
				'type_id' => array('integer', $this->getTypeId())
			 ), 
			 array(
				'type_name' => array('text', $this->getName()),
				'title' => array('text', $this->getTitle()),
				'description' => array('clob', $this->getDescription()),
				'availability' => array('integer', $this->getAvailability()),
				'remarks' => array('clob', $this->getRemarks()),
				'time_to_delete' => array('integer', $this->getTimeToDelete()),
				//'log_level' => array('integer', $this->getLogLevel()),
				'lrs_type_id' => array('integer', $this->getLrsTypeId()),
				'lrs_endpoint_1' => array('text', $this->getLrsEndpoint1()),
				'lrs_key_1' => array('text', $this->getLrsKey1()),
				'lrs_secret_1' => array('text', $this->getLrsSecret1()),
				'lrs_endpoint_2' => array('text', $this->getLrsEndpoint2()),
				'lrs_key_2' => array('text', $this->getLrsKey2()),
				'lrs_secret_2' => array('text', $this->getLrsSecret2()),
				'endpoint_use' => array('text',
					(!$this->getLrsEndpoint2() || !$this->getLrsKey2() || !$this->getLrsSecret2())
						? self::ENDPOINT_USE_1
						: $this->getEndpointUse()
				 ),
				'privacy_ident' => array('integer', $this->getPrivacyIdent()),
				'privacy_name' => array('integer', $this->getPrivacyName()),
				'privacy_comment_default' => array('text', $this->getPrivacyCommentDefault()),
				'external_lrs' => array('integer', $this->getExternalLrs()),
				'force_privacy_settings' => array('integer', (int) $this->getForcePrivacySettings()),
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
                'no_substatements' => array('integer', (int)$this->getNoSubstatements())
			 )
		);
		return true;
	}

	/**
	 * Delete
	 *
	 * @access public
	 */
	public function delete() {
		global $ilDB;

		ilXapiCmi5Plugin::_deleteWebspaceDir("type", $this->getTypeId());
		
		$query = "DELETE FROM xxcf_data_types " .
				"WHERE type_id = " . $ilDB->quote($this->getTypeId(), 'integer');
		$ilDB->manipulate($query);

		return true;
	}

	public function getDefaultLrsEndpointStatementsAggregationLink()
    {
        return dirname(dirname($this->getDefaultLrsEndpoint())) . '/api/' . self::ENDPOINT_AGGREGATE_SUFFIX;
    }

    public function getFallbackLrsEndpointStatementsAggregationLink()
    {
        return dirname(dirname($this->getFallbackLrsEndpoint())) . '/api/' . self::ENDPOINT_AGGREGATE_SUFFIX;
    }

    public function getDefaultLrsEndpointDeleteLink()
    {
        return dirname(dirname($this->getDefaultLrsEndpoint())) . '/api/' . self::ENDPOINT_DELETE_SUFFIX;
    }

    public function getFallbackLrsEndpointDeleteLink()
    {
        return dirname(dirname($this->getFallbackLrsEndpoint())) . '/api/' . self::ENDPOINT_DELETE_SUFFIX;
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

		$query = "SELECT * FROM xxcf_data_types"; //*
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

		$query = "SELECT * FROM xxcf_data_types";
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

		$query = "SELECT COUNT(*) untrashed FROM xxcf_data_settings s"
				. " INNER JOIN object_reference r ON s.obj_id = r.obj_id"
				. " WHERE r.deleted IS NULL "
				. " AND s.type_id = " . $ilDB->quote($a_type_id, 'integer');

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
		$query = "SELECT COUNT(*) counter FROM xxcf_data_types WHERE availability = " . $ilDB->quote(self::AVAILABILITY_CREATE, 'integer');
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
	
		
	public function updatePrivacySettingsFromLrsType()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $query = "
			UPDATE xxcf_data_settings
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
                no_substatements = %s
            WHERE type_id = %s
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
             $this->getTypeId()
            ]
        );
    }

	
}
?>