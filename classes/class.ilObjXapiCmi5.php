<?php
/**
 * Copyright (c) 2018 internetlehrer GmbH
 * GPLv2, see LICENSE 
 */
require_once('./Services/Repository/classes/class.ilObjectPlugin.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5Type.php');

require_once 'Services/Tracking/interfaces/interface.ilLPStatusPlugin.php';

/**
 * xApi plugin: base class for repository object
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */
class ilObjXapiCmi5 extends ilObjectPlugin implements ilLPStatusPluginInterface
{

	const ACTIVATION_OFFLINE = 0;
	const ACTIVATION_UNLIMITED = 1;

	const LP_INACTIVE = 0;
	// const LP_InProgress = 2;
	const LP_Passed = 1;
	const LP_Completed = 2;
	const LP_CompletedAndPassed = 3;
	const LP_CompletedOrPassed = 4;
	const LP_UseScore = 8;
	const LP_NotApplicable = 99;
	
	// const LP_Failed = 3;
	
	
	// const LP_ACTIVE = 1;

	/**
	 * Content Type definition (object)
	 */
	var $typedef;

	/**
	 * Fields for filling template (list of field arrays)
	 */
	protected $fields;
	protected $availability_type;
	protected $type_id;
	protected $instructions;
	protected $meta_data_xml;
	protected $context = null;
	protected $lp_mode = self::LP_INACTIVE;
	protected $lp_threshold = 0.5;
	protected $show_debug = 0;
	protected $use_fetch = 0;
	protected $open_mode = 0;
	protected $privacy_ident = ilXapiCmi5Type::PRIVACY_IDENT_EMAIL;
	protected $privacy_name = 3;
	protected $user_object_uid;
	
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



	/**
	 * Constructor
	 *
	 * @access public
	 * 
	 */
	public function __construct($a_id = 0, $a_call_by_reference = true) {
		global $ilDB;

		parent::__construct($a_id, $a_call_by_reference);

		$this->db = $ilDB;
		$this->typedef = new ilXapiCmi5Type();
	}

	/**
	 * Get type.
	 * The initType() method must set the same ID as the plugin ID.
	 *
	 * @access	public
	 */
	final public function initType() {
		$this->setType('xxcf');
	}

	/**
	 * Set instructions
	 *
	 * @param string instructions
	 */
	public function setInstructions($a_instructions) {
		$this->instructions = $a_instructions;
	}

	/**
	 * Get instructions
	 */
	public function getInstructions() {
		return $this->instructions;
	}

	/**
	 * Set Type Id
	 *
	 * @param int type id
	 */
	public function setTypeId($a_type_id) {
		if ($this->type_id != $a_type_id) {
			$this->typedef = new ilXapiCmi5Type($a_type_id);
			$this->type_id = $a_type_id;
		}
	}

	/**
	 * Get Type Id
	 */
	public function getTypeId() {
		return $this->type_id;
	}
	
	public function setTypeName($a_type_name) {
		global $ilDB;
		$type_id = 0;
		$query = "SELECT type_id FROM xxcf_data_types where type_name = " . $ilDB->quote($a_type_name, 'text') . " ORDER BY type_id";
		$res = $ilDB->query($query);
		while ($row = $ilDB->fetchAssoc($res)) {
			$type_id = $row['type_id'];
		}
		if ($type_id != 0) {
			$this->setTypeId($type_id);
			$typedef = new ilXapiCmi5Type($this->getTypeId());
			$typedef->setName($a_type_name);
		}
	}
	/**
	 * Get Type Name for Export/Import
	 */
	public function getTypeName() {
		$typedef = new ilXapiCmi5Type($this->getTypeId());
		return $typedef->getName();
	}

	/**
	 * Set vailability type
	 *
	 * @param int availability type
	 */
	public function setAvailabilityType($a_type) {
		$this->availability_type = $a_type;
	}

	/**
	 * get availability type
	 */
	public function getAvailabilityType() {
		return $this->availability_type;
	}

	/**
	 * get a text telling the availability
	 */
	public function getAvailabilityText() {
		global $lng;

		switch ($this->availability_type) {
			case self::ACTIVATION_OFFLINE:
				return $lng->txt('offline');

			case self::ACTIVATION_UNLIMITED:
				return $lng->txt('online');
		}
		return '';
	}

	/**
	 * Set meta data as xml structure
	 *
	 * @param int availability type
	 */
	public function setMetaDataXML($a_xml) {
		$this->meta_data_xml = $a_xml;
	}

	/**
	 * get meta data as xml structure
	 */
	public function getMetaDataXML() {
		return $this->meta_data_xml;
	}


	/**
	 * Get online status
	 */
	public function getOnline() {
		switch ($this->availability_type) {
			case self::ACTIVATION_UNLIMITED:
				return true;

			case self::ACTIVATION_OFFLINE:
				return false;

			default:
				return false;
		}
	}

	public function setLaunchUrl($a_launch_url) {
		$this->launch_url = $a_launch_url;
	}
	
	public function getLaunchUrl() {
		return $this->launch_url;
	}


	public function setActivityId($a_activity_id) {
		$this->activity_id = $a_activity_id;
	}
	
	public function getActivityId() {
		return $this->activity_id;
	}


	public function setLaunchKey($a_launch_key) {
		$this->launch_key = $a_launch_key;
	}
	
	public function getLaunchKey() {
		return $this->launch_key;
	}


	public function setLaunchSecret($a_launch_secret) {
		$this->launch_secret = $a_launch_secret;
	}
	
	public function getLaunchSecret() {
		return $this->launch_secret;
	}

	public function setShowDebug($a_show_debug) {
		if ($a_show_debug == null) $a_show_debug = 0;
		$this->show_debug = $a_show_debug;
	}
	
	public function getShowDebug() {
		return $this->show_debug;
	}
	

	public function setUseFetch($a_use_fetch) {
		if ($a_use_fetch == null) $a_use_fetch = 0;
		$this->use_fetch = $a_use_fetch;
	}

	public function getUseFetch() {
		return $this->use_fetch;
	}


	public function setOpenMode($a_open_mode) {
		if ($a_open_mode == null) $a_open_mode = 0;
		$this->open_mode = $a_open_mode;
	}

	public function getOpenMode() {
		return $this->open_mode;
	}
	

	public function setPrivacyIdent($a_option)
	{
		if ($a_option != null) $this->privacy_ident = $a_option;
	}
	
	public function getPrivacyIdent()
	{
		return $this->privacy_ident;
	}


	public function setPrivacyName($a_option)
	{
		if ($a_option != null) $this->privacy_name = $a_option;
	}
	
	public function getPrivacyName()
	{
		return $this->privacy_name;
	}
	
	public function getLPUseScore()
	{
		if ($this->lp_mode != self::LP_NotApplicable && $this->lp_mode % self::LP_UseScore == 1) {
			return 1;
		}
	}

	####
	#### Statement Reducer Getter & Setter
	####

	/**
	 * @return bool
	 */
	public function getOnlyMoveon(): bool
	{
		return $this->only_moveon;
	}

	/**
	 * @param bool $only_moveon
	 * @return ilObjXapiCmi5
	 */
	public function setOnlyMoveon(bool $only_moveon): ilObjXapiCmi5
	{
		$this->only_moveon = $only_moveon;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setAchieved(bool $achieved): ilObjXapiCmi5
	{
		$this->achieved = $achieved;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setAnswered(bool $answered): ilObjXapiCmi5
	{
		$this->answered = $answered;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setCompleted(bool $completed): ilObjXapiCmi5
	{
		$this->completed = $completed;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setFailed(bool $failed): ilObjXapiCmi5
	{
		$this->failed = $failed;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setInitialized(bool $initialized): ilObjXapiCmi5
	{
		$this->initialized = $initialized;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setPassed(bool $passed): ilObjXapiCmi5
	{
		$this->passed = $passed;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setProgressed(bool $progressed): ilObjXapiCmi5
	{
		$this->progressed = $progressed;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setSatisfied(bool $satisfied): ilObjXapiCmi5
	{
		$this->satisfied = $satisfied;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setTerminated(bool $terminated): ilObjXapiCmi5
	{
		$this->terminated = $terminated;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setHideData(bool $hide_data): ilObjXapiCmi5
	{
		$this->hide_data = $hide_data;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setTimestamp(bool $timestamp): ilObjXapiCmi5
	{
		$this->timestamp = $timestamp;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setDuration(bool $duration): ilObjXapiCmi5
	{
		$this->duration = $duration;
		return $this;
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
	 * @return ilObjXapiCmi5
	 */
	public function setNoSubstatements(bool $no_substatements): ilObjXapiCmi5
	{
		$this->no_substatements = $no_substatements;
		return $this;
	}



	/**
	 * create an access token
	 * 
	 * @param $a_field
	 * @return unknown_type
	 */
	// private function fillToken($a_field) {
	public function fillToken() {
		$seconds = $this->getTimeToDelete();
		$result = $this->selectCurrentTimestamp();
		$time = new ilDateTime($result['CURRENT_TIMESTAMP'], IL_CAL_DATETIME);

		$timestamp = $time->get(IL_CAL_UNIX);
		$new_timestamp = $timestamp + $seconds;

		$value = $this->createToken($timestamp);

		$time_to_db = new ilDateTime($new_timestamp, IL_CAL_UNIX);

		//Insert new token in DB
		$this->insertToken($value, $time_to_db->get(IL_CAL_DATETIME));

		//delete old tokens
		$this->deleteToken($timestamp);

		return $value;
	}


	/**
	 * get info about the context in which the link is used
	 * 
	 * The most outer matching course or group is used
	 * If not found the most inner category or root node is used
	 * 
	 * @param	array	list of valid types
	 * @return 	array	context array ("ref_id", "title", "type")
	 */
	public function getContext($a_valid_types = array('crs', 'grp', 'cat', 'root')) {
		global $tree;

		if (!isset($this->context)) {

			$this->context = array();

			// check fromm inner to outer
			$path = array_reverse($tree->getPathFull($this->getRefId()));
			foreach ($path as $key => $row)
			{
				if (in_array($row['type'], $a_valid_types))
				{
					// take an existing inner context outside a course
					if (in_array($row['type'], array('cat', 'root')) && !empty($this->context))
					{
						break;
					}

					$this->context['id'] = $row['child'];
					$this->context['title'] = $row['title'];
					$this->context['type'] = $row['type'];

					// don't break to get the most outer course or group
				}
			}
		}

		return $this->context;
	}


	/**
	 * Update function
	 *
	 * @access public
	 */
	public function doUpdate() {
		global $ilDB;
		$ilDB->replace('xxcf_data_settings',
			array( 'obj_id' => array('integer', $this->getId())),
			array(
				'type_id' => array('integer', $this->getTypeId()),
				'instructions' => array('text', $this->getInstructions()),
				'availability_type' => array('integer', $this->getAvailabilityType()),
				'meta_data_xml' => array('text', $this->getMetaDataXML()),
				'lp_mode' => array('integer', $this->getLPMode()),
				'lp_threshold' => array('float', $this->getLPThreshold()),
				'launch_key' => array('text', $this->getLaunchKey()),
				'launch_secret' => array('text', $this->getLaunchSecret()),
				'launch_url' => array('text', $this->getLaunchUrl()),
				'activity_id' => array('text', $this->getActivityId()),
				'open_mode' => array('integer', $this->getOpenMode()),
				//'width' => array('integer', $this->getWidth()),
				'width' => array('integer', 950),
				'height' => array('integer', 650),
				'show_debug' => array('integer', $this->getShowDebug()),
				'privacy_comment' => array('text', null),
				'version' => array('integer', 1),
				'use_fetch' => array('integer', $this->getUseFetch()),
				'privacy_ident' => array('integer', $this->getPrivacyIdent()),
				'privacy_name' => array('integer', (int)$this->getPrivacyName()),
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

	public function insertToken($a_token, $a_time) {
		global $ilDB, $ilUser;
		$ilDB->insert('xxcf_data_token', array(
			'token' => array('text', $a_token),
			'time' => array('timestamp', $a_time),
			'obj_id' => array('integer', $this->getId()),
			'usr_id' => array('integer', $ilUser->getId())
			)
		);
		return true;
	}
	
	public function getToken() {
		global $ilDB, $ilUser;
		$token = '';
		$obj_id=$this->_lookupObjectId($_GET['ref_id']);
		$query = "SELECT token FROM xxcf_data_token WHERE obj_id=" . $ilDB->quote($obj_id, 'integer') 
			. " AND usr_id=" . $ilDB->quote($ilUser->getId(), 'integer');
			//.time
		$result = $ilDB->query($query);
		$row = $ilDB->fetchObject($result);
		if ($row) {
			$token = $row->token;
		}
		return $token;
	}


	public function deleteToken($times) {
		global $ilDB;

		$value = date('Y-m-d H:i:s', $times);
		$query = "DELETE FROM xxcf_data_token WHERE time < " . $ilDB->quote($value, 'timestamp');
		$ilDB->manipulate($query);
		return true;
	}


	/**
	 * Delete
	 *
	 * @access public
	 */
	public function doDelete() {
		global $ilDB;
		
		$query = "DELETE FROM xxcf_data_settings " .
				"WHERE obj_id = " . $ilDB->quote($this->getId(), 'integer') . " ";
		$ilDB->manipulate($query);

		$query = "DELETE FROM xxcf_results " .
				"WHERE obj_id = " . $ilDB->quote($this->getId(), 'integer') . " ";
		$ilDB->manipulate($query);

		$query = "DELETE FROM xxcf_user_mapping " .
				"WHERE obj_id = " . $ilDB->quote($this->getId(), 'integer') . " ";
		$ilDB->manipulate($query);
		return true;
	}
	
	/**
	 * read settings
	 *
	 * @access public
	 */
	public function doRead() {
		global $ilDB;
		
		$query = 'SELECT * FROM xxcf_data_settings WHERE obj_id = '
				. $ilDB->quote($this->getId(), 'integer');

		$res = $ilDB->query($query);
		$row = $ilDB->fetchObject($res);
		
		if ($row) {
			$this->setTypeId($row->type_id);
			$this->setInstructions($row->instructions);
			$this->setAvailabilityType($row->availability_type);
			$this->setMetaDataXML($row->meta_data_xml);
			$this->setLaunchUrl($row->launch_url);
			$this->setActivityId($row->activity_id);
			$this->setShowDebug($row->show_debug);
			$this->setUseFetch($row->use_fetch);
			$this->setOpenMode($row->open_mode);
			$this->setPrivacyIdent($row->privacy_ident);
			$this->setPrivacyName($row->privacy_name);
			$this->setLPMode($row->lp_mode);
			$this->setLPThreshold($row->lp_threshold);
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
			$this->user_object_uid = $this->readUserObjectUniqueId();
		}
	}

	/**
	 * Do Cloning
	 */
	function doCloneObject($new_obj, $a_target_id, $a_copy_id = null) { //TODO
		global $ilDB;
		
		$ilDB->insert('xxcf_data_settings', array(
			'obj_id' => array('integer', $new_obj->getId()),
			'type_id' => array('integer', $this->getTypeId()),
			'instructions' => array('text', $this->getInstructions()),
			'availability_type' => array('integer', $this->getAvailabilityType()),
			// 'meta_data_xml' => array('text', $this->getMetaDataXML()),
			'launch_url' => array('text', $this->getLaunchUrl()),
			'activity_id' => array('text', $this->getActivityId()),
			'show_debug' => array('integer', $this->getShowDebug()),
			'use_fetch' => array('integer', $this->getUseFetch()),
			'open_mode' => array('integer', $this->getOpenMode()),
			'privacy_ident' => array('integer', $this->getPrivacyIdent()),
			'privacy_name' => array('integer', $this->getPrivacyName()),
			'lp_mode' => array('integer', $this->getLPMode()),
			'lp_threshold' => array('float', $this->getLPThreshold()),
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
		 ));
	}

	function createToken($time) {
		$pre_token = rand(-100000, 100000);
		$token = $pre_token . $time;
		$token = md5($token);
		return $token;
	}

	function selectCurrentTimestamp() {
		global $ilDB;
		$query = "SELECT CURRENT_TIMESTAMP";
		$result = $ilDB->query($query);
		$row = $ilDB->fetchAssoc($result);
		return $row;
	}


	function checkToken() {
		global $ilDB;

		$token = $_GET['token'];
		$query = "SELECT token FROM xxcf_data_token WHERE token = " . $ilDB->quote($token, 'text');
		$result = $ilDB->query($query);
		$row = $ilDB->fetchAssoc($result);

		if ($row) {
			return "1";
		} else {
			return "0";
		}
	}


	function getTimeToDelete() {
		global $ilDB;
		$query = "SELECT time_to_delete FROM xxcf_data_types WHERE type_id = " . $ilDB->quote($this->getTypeId(), 'integer');
		$result = $ilDB->query($query);
		$row = $ilDB->fetchAssoc($result);
		return $row['time_to_delete'];
	}


	/**
	 * get the learning progress mode
	 */
	public function getLPMode() {
		return $this->lp_mode;
	}

	/**
	 * set the learning progress mode
	 */
	public function setLPMode($a_mode) {
		$this->lp_mode = $a_mode;
	}

	/**
	 * get the learning progress mode
	 */
	public function getLPThreshold() {
		return $this->lp_threshold;
	}

	/**
	 * set the learning progress mode
	 */
	public function setLPThreshold($a_threshold) {
		$this->lp_threshold = $a_threshold;
	}


	/**
	 * Get all user ids with LP status completed
	 *
	 * @return array
	 */
	public function getLPCompleted()
	{
		$this->plugin->includeClass('class.ilXapiCmi5LPStatus.php');
		return ilXapiCmi5LPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_COMPLETED_NUM);
	}

	/**
	 * Get all user ids with LP status not attempted
	 *
	 * @return array
	 */
	public function getLPNotAttempted()
	{
		$this->plugin->includeClass('class.ilXapiCmi5LPStatus.php');
		return ilXapiCmi5LPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
	}

	/**
	 * Get all user ids with LP status failed
	 *
	 * @return array
	 */
	public function getLPFailed()
	{
		$this->plugin->includeClass('class.ilXapiCmi5LPStatus.php');
		return ilXapiCmi5LPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_FAILED_NUM);
	}

	/**
	 * Get all user ids with LP status in progress
	 *
	 * @return array
	 */
	public function getLPInProgress()
	{
		$this->plugin->includeClass('class.ilXapiCmi5LPStatus.php');
		return ilXapiCmi5LPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_IN_PROGRESS_NUM);
	}

	/**
	 * Get current status for given user
	 *
	 * @param int $a_user_id
	 * @return int
	 */
	public function getLPStatusForUser($a_user_id)
	{
		$this->plugin->includeClass('class.ilXapiCmi5LPStatus.php');
		return ilXapiCmi5LPStatus::getLPDataForUserFromDb($this->getId(), $a_user_id);
	}

	/**
	 * Track access for learning progress
	 */
	public function trackAccess()
	{
		global $ilUser;

		// track access for learning progress
		if ($ilUser->getId() != ANONYMOUS_USER_ID && $this->getLPMode() >0)
		{
			$this->plugin->includeClass('class.ilXapiCmi5LPStatus.php');
			ilXapiCmi5LPStatus::trackAccess($ilUser->getId(),$this->getId(), $this->getRefId());
		}
	}

    public static function handleLPStatusFromProxy($client, $token, $status, $score) {
		$LP_status = 1;
		$LP_score = 0;
		if ($score != "NOT_SET") $LP_score = $score;
		global $ilDB;

		$query = "SELECT xxcf_data_token.usr_id, xxcf_data_token.obj_id, xxcf_data_token.time, xxcf_data_settings.lp_mode"
				." FROM xxcf_data_token, xxcf_data_settings WHERE token = " . $ilDB->quote($token, 'text')
				." AND xxcf_data_settings.obj_id = xxcf_data_token.obj_id";
		$result = $ilDB->query($query);
		$row = $ilDB->fetchAssoc($result);
		$usr_id = $row['usr_id'];
		$obj_id = $row['obj_id'];
		$lp_mode = $row['lp_mode'];
		if ($lp_mode > 0 && $usr_id != ANONYMOUS_USER_ID) {
			
			if ($status == "completed" && ($lp_mode == self::LP_Completed || $lp_mode == self::LP_CompletedOrPassed) ) {
				$LP_status = 2;
			}
			else if ($status == "passed" && ($lp_mode == self::LP_Passed || $lp_mode == self::LP_CompletedOrPassed) ) {
				$LP_status = 2;
			}
			else if ($status == "failed" && $lp_mode != self::LP_Completed) {
				$LP_status = 3;
			}
			require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5LPStatus.php';
			// $this->plugin->includeClass('class.ilXapiCmi5LPStatus.php');
			\ilXapiCmi5LPStatus::trackResult($usr_id, $obj_id, $LP_status, $LP_score);
			
		}
    }

    
    /******* TESTING *******/
    
    // public static function handleLPStatusFromProxy($client, $token, $status, $score) {
		// // trackResult
        // self::_log("handleLPStatusFromProxy: ". $client . ":" . $token . ":" . $status . ":" . $score);
    // }
    
    public static function getLrsTypeByToken($a_token) {
		global $ilDB;
		$query = "SELECT type_id FROM xxcf_data_settings, xxcf_data_token WHERE xxcf_data_settings.obj_id = xxcf_data_token.obj_id AND xxcf_data_token.token = " . $ilDB->quote($a_token, 'text');
		$res = $ilDB->query($query);
		$type_id = null;
		$lrs = null;
		while ($row = $ilDB->fetchObject($res)) 
		{
			$type_id = $row->type_id;
		}
		if ($type_id) {
			$lrs = new ilXapiCmi5Type($type_id);
		}
		return $lrs;
	}
    
    private static function _log($txt) {
        file_put_contents("xapilog.txt",$txt."\n",FILE_APPEND);
	}

	##########################################################################################################
	#### UUID depends on ilObjXapiCmi5 at first time of showing content of the object by the user.
	#### Onetime Db-insertion, no update, delete or clone ops required.
	##########################################################################################################

	/**
	 * @param int $length
	 * @return string
	 */
	public function generateUserObjectUniqueId( $length = 32 )
	{

		if( (bool)strlen($this->user_object_uid) ) {
			return $this->user_object_uid;
		}

		$getId = function( $length ) {
			$multiplier = floor($length/8) * 2;
			$uid = str_shuffle(str_repeat(uniqid(), $multiplier));

			try {
				$ident = bin2hex(random_bytes($length));
			} catch (Exception $e) {
				$ident = $uid;
			}

			$start = rand(0, strlen($ident) - $length - 1);
			return substr($ident, $start, $length);
		};

		$id = $getId($length);

		$exists = $this->userObjectUniqueIdExists($id);

		while( $exists ) {
			$id = $getId($length);
			$exists = $this->userObjectUniqueIdExists($id);
		}

		$this->insertUserObjectUniqueId($id);

		return $id;

	}

	private function readUserObjectUniqueId()
	{
		global $DIC; /** @var Container */

		$query = "SELECT uuid FROM xxcf_usrobjuuid_map".
				" WHERE usr_id = " . $DIC->user()->getId() .
				" AND obj_id = " . $DIC->database()->quote($this->getId(), 'integer');
		$result = $DIC->database()->query($query);
		return is_array($row = $DIC->database()->fetchAssoc($result)) ? $row['uuid'] : '';
	}

	private function userObjectUniqueIdExists($id)
	{
		global $DIC; /** @var Container */

		$query = "SELECT uuid FROM xxcf_usrobjuuid_map WHERE uuid = " . $DIC->database()->quote($id, 'text');
		$result = $DIC->database()->query($query);
		return (bool)$num = $DIC->database()->numRows($result);
	}

	private function insertUserObjectUniqueId($ident)
	{
		global $DIC; /** @var Container */

		return (bool)$DIC->database()->insert('xxcf_usrobjuuid_map', [
			'usr_id'	=> ['integer', $DIC->user()->getId()],
			'obj_id'	=> ['integer', $this->getId()],
			'uuid'	=> ['text', $ident]
		]);
	}

}

?>
