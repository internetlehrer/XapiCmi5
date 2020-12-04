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

	const LOG_COMPONENT = "XapiCmi5Plugin";
	const LOG_LEVEL_OFF = 0;
	const LOG_LEVEL_ERROR_AND_WARNING = 1;
	const LOG_LEVEL_DEBUG = 2;
	
    // const FIELDTYPE_ILIAS = 0;
    // const FIELDTYPE_CALCULATED = 1;
    // const FIELDTYPE_TEMPLATE = 2;

	private $type_id;
	private $name;
	// private $xml = '';

	/**
	 * These data are also in the interface XML
	 * 
	 * title and description can be set with methods
	 * the others are set by xml
	 */
	private $title;
	private $description;
	private $template;
	private $launch_type = self::LAUNCH_TYPE_EMBED;
	// private $meta_data_url;
	
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
	private $privacy_comment_default;
	private $external_lrs;
	private $log;
	// /**
	 // * Array of fields
	 // *   
	 // * @var array 	list of field objects with properties
	 // */
	// private $fields = array();

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


	// /**
	 // * Get Template
	 // * @return string template
	 // */
	public function getTemplate()
	{
		return $this->template;
	}


	// /**
	 // * Get Launch Tape
	 // * @return string launch_type
	 // */
	public function getLaunchType()
	{
		return $this->launch_type;
	}

	// /**
	 // * get Mata DataURL
	 // * 
	 // * @param string url
	 // */
	// public function getMetaDataUrl()
	// {
		// return $this->meta_data_url;
	// }

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
				'external_lrs' => array('integer', $this->getExternalLrs())
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
	
	static function getTypesStruct() {
		$a_s = array (
			  'type_name' 		=> array('type'=>'text', 'maxlength'=>32)
			, 'title'			=> array('type'=>'text', 'maxlength'=>255)
			, 'description'		=> array('type'=>'text', 'maxlength'=>4000)
			, 'availability'	=> array('type'=>'a_integer', 'maxlength'=>1,'options'=>array(2,1,0)) //AVAILABILITY_CREATE,AVAILABILITY_EXISTING,AVAILABILITY_NONE
			, 'log_level'		=> array('type'=>'a_integer', 'maxlength'=>1, 'options'=>array(0,1,2))
			// , 'lrs'				=> array('type'=>'headline')
			, 'lrs_type_id'		=> array('type'=>'a_integer', 'maxlength'=>1, 'options'=>array(0))
			, 'lrs_endpoint'	=> array('type'=>'text', 'maxlength'=>64, 'required'=>true)
			, 'lrs_key'			=> array('type'=>'text', 'maxlength'=>64, 'required'=>true)
			, 'lrs_secret'		=> array('type'=>'text', 'maxlength'=>64, 'required'=>true)
			, 'external_lrs'	=> array('type'=>'bool')
			, 'privacy_ident'	=> array('type'=>'a_integer', 'maxlength'=>1, 'options'=>array(0,1,2,3))
			, 'privacy_name'	=> array('type'=>'a_integer', 'maxlength'=>1, 'options'=>array(0,1,2,3))
			, 'privacy_comment_default' => array('type'=>'text', 'maxlength'=>2000)
			, 'remarks'			=> array('type'=>'text', 'maxlength'=>4000)
		);
		return $a_s;
	}
	
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
}
?>