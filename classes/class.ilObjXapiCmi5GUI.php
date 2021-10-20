<?php 
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

use ILIAS\DI\Container;

include_once('./Services/Repository/classes/class.ilObjectPluginGUI.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5Exception.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5Access.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5Type.php');

/**
 * xApi plugin: repository object GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 * 
 * @ilCtrl_isCalledBy ilObjXapiCmi5GUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjXapiCmi5GUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonactionDispatcherGUI, ilLearningProgressGUI, ilExportGUI
 */
class ilObjXapiCmi5GUI extends ilObjectPluginGUI
{
    const META_TIMEOUT_INFO = 1;
    const META_TIMEOUT_REFRESH = 60;

    const ACTIVITY_ID_VALIDATION_REGEXP = '/^'.
		'(http|https):\/\/'.		// protocol
		'[a-z0-9\-\.]+'.			// hostpart
		'(\/[a-z0-9_\-\.]+)+'.		// pathpart
		'$/i';

    /**
     * Valid meta data groups for displaying
     */
    var $meta_groups = array('General', 'LifeCycle', 'Technical', 'Rights');

    /** @var ilObjXapiCmi5 $object */
    public $object;

    /** @var $txt */
    public $txt;

    /**
     * Initialisation
     *
     * @access protected
     */
    protected function afterConstructor()
    {
        // anything needed after object has been constructed
    }

    /**
     * Get type.
     */
    final function getType()
    {
        return "xxcf";
    }

    function getTitle()
    {
        return $this->object->getTitle();
    }

    public function getText($txt)
    {
        return $this->txt($txt);
    }
    /**
     * After object has been created -> jump to this command
     */
    function getAfterCreationCmd()
    {
        return "edit";
    }

    /**
     * Get standard command
     */
    function getStandardCmd()
    {
        return "view";
    }


	/**
 	 * Extended check for being in creation mode
	 *
	 * Use this instead of getCreationMode() because ilRepositoryGUI sets it weakly
	 * The creation form for contents is extended and has different commands
	 * In creation mode $this->object is the parent container and can't be used
	 *
	 * @return bool		creation mode
	 */
	protected function checkCreationMode()
	{
		global $ilCtrl;
		$cmd = $ilCtrl->getCmd();
		if ($cmd == "create" or $cmd == "cancelCreate" or $cmd == "save" or $cmd == "Save")
		{
			$this->setCreationMode(true);
		}
		return $this->getCreationMode();
	}

	/**
     * Perform command
     *
     * @access public
     */
    public function performCommand($cmd)
    {
    	global $ilErr, $ilCtrl, $ilTabs;
        //$GLOBALS['DIC']->logger()->root()->log("COMMAND: " . $cmd);
        switch ($cmd)
        {
        	case "edit":
        	case "update":
            case "showExport":
        		$this->checkPermission("write");
            	// $this->setSubTabs("edit");
            	
                $cmd .= "Object";
                $this->$cmd();
                break;
            case "statements":
            case "show":
            case "applyFilter":
            case "asyncUserAutocomplete":
            case "resetFilter":
                $this->checkPermission("read");
                $this->statementsView($cmd);
                break;
            case "deleteAllData":
            case "asyncDelete":
            case "deleteFilteredData":
                $this->checkPermission("read");
                if (ilObjXapiCmi5Access::hasDeleteXapiDataAccess($this->object)) {
                    $this->statementsView($cmd);
                }
                else {
                    throw new ilXapiCmi5Exception('access denied!');
                }
                break;
            case "editLPSettings":
                $this->checkPermission("edit_learning_progress");
                // $this->setSubTabs("learning_progress");

                $cmd .= "Object";
                $this->$cmd();
                break;

            case "checkToken":
               	$this->$cmd();
                break;
            
            default:
            	
				if ($this->checkCreationMode())
				{
					$this->$cmd();
				}
				else
				{
					$this->checkPermission("read");
					if ($this->object->getTypeId() == "") {
						$pl = new ilXapiCmi5Plugin();
						$ilErr->raiseError($this->txt('type_not_set'), $ilErr->MESSAGE);
					} 
					else if ($this->object->typedef->getAvailability() == ilXapiCmi5Type::AVAILABILITY_NONE)	{
						$ilErr->raiseError($this->txt('message_type_not_available'), $ilErr->MESSAGE);
					}
					// else if ($this->object->getAvailabilityType() == $this->object::ACTIVATION_OFFLINE) {
						// $ilErr->raiseError($this->txt('message_not_available'), $ilErr->MESSAGE);
					// }
					else if ($this->object->getLaunchUrl() == "") {
						$ilErr->raiseError($this->txt('message_no_launch_url_specified'), $ilErr->MESSAGE);
					}


					if (!$cmd)
					{
						$cmd = "viewObject";
					}
					$cmd .= "Object";
					$this->$cmd();
				}
        }
    }
    
    /*
    public function hasStatementsAccess() {
        if ($this->checkPermissionBool('write')) {
            return true;
        }
        if (ilObjXapiCmi5Access::hasOutcomesAccess($this->object)) {
            return true;
        }
        return false;
    }
	*/
    /**
     * Set tabs
     */
    function setTabs()
    {
        global $ilTabs, $ilCtrl, $lng;

		if ($this->checkCreationMode())
		{
			return;
		}

        $type = new ilXapiCmi5Type($this->object->getTypeId());

		// view tab
		// if ($this->object->typedef->getLaunchType() == ilXapiCmi5Type::LAUNCH_TYPE_EMBED)
		// {
			$ilTabs->addTab("viewEmbed", $this->lng->txt("content"), $ilCtrl->getLinkTarget($this, "viewEmbed"));
		// }

        //  info screen tab
        $ilTabs->addTab("infoScreen", $this->lng->txt("info_short"), $ilCtrl->getLinkTarget($this, "infoScreen"));

        if ($this->object->isStatementsReportEnabled()) { // ToDo: see in core "hasStatementsAccess"
            $ilTabs->addTab("tab_statements", $this->getText('tab_statements'), $ilCtrl->getLinkTarget($this,'statements'));
        }
        // add "edit" tab
        
        if ($this->checkPermissionBool("write"))
        {
            $ilTabs->addTab("edit", $this->lng->txt("settings"), $ilCtrl->getLinkTarget($this, "edit"));
			$ilTabs->addTab("export", $this->lng->txt("export"), $ilCtrl->getLinkTargetByClass("ilexportgui", ""));
        }
        
        include_once("Services/Tracking/classes/class.ilObjUserTracking.php");
        if (ilObjUserTracking::_enabledLearningProgress() && ($this->checkPermissionBool("edit_learning_progress") || $this->checkPermissionBool("read_learning_progress")))
        {
            if ($this->object->getLPMode() > 0 && $this->checkPermissionBool("read_learning_progress"))
            {
				if (ilObjUserTracking::_enabledUserRelatedData())
				{
					$ilTabs->addTab("learning_progress", $lng->txt('learning_progress'), $ilCtrl->getLinkTargetByClass(array('ilObjXapiCmi5GUI','ilLearningProgressGUI','ilLPListOfObjectsGUI')));//, 'showObjectSummary'
				}
				else
				{
					$ilTabs->addTab("learning_progress", $lng->txt('learning_progress'), $ilCtrl->getLinkTargetByClass(array('ilObjXapiCmi5GUI','ilLearningProgressGUI', 'ilLPListOfObjectsGUI'), 'showObjectSummary'));
				}
				if ($this->checkPermissionBool("edit_learning_progress") && in_array($ilCtrl->getCmdClass(), array('illearningprogressgui', 'illplistofobjectsgui'))) {
					$ilTabs->addSubTab("lp_settings", $this->txt('settings'), $ilCtrl->getLinkTargetByClass(array('ilObjXapiCmi5GUI'), 'editLPSettings'));
				}
            }
			elseif ($this->checkPermissionBool("edit_learning_progress")) {
				$ilTabs->addTab('learning_progress', $lng->txt('learning_progress'), $ilCtrl->getLinkTarget($this,'editLPSettings'));
			}
            
        }
        // standard permission tab
        $this->addPermissionTab();
    }
    
    /**
     * Set the sub tabs
     * 
     * @param string	main tab identifier
     */
    // function setSubTabs($a_tab)
    // {
    	// global $ilUser, $ilTabs, $ilCtrl, $lng;
    	
    	// switch ($a_tab)
    	// {
            // case "learning_progress":
                // $lng->loadLanguageModule('trac');
				// if ($this->checkPermissionBool("edit_learning_progress"))
				// {
					// $ilTabs->addSubTab("lp_settings", $this->txt('settings'), $ilCtrl->getLinkTargetByClass(array('ilObjXapiCmi5GUI'), 'editLPSettings'));
				// }
                // if ($this->object->getLPMode() == ilObjXapiCmi5::LP_ACTIVE && $this->checkPermissionBool("read_learning_progress"))
                // {

                    // include_once("Services/Tracking/classes/class.ilObjUserTracking.php");
                    // if (ilObjUserTracking::_enabledUserRelatedData())
                    // {
                        // $ilTabs->addSubTab("trac_objects", $lng->txt('trac_objects'), $ilCtrl->getLinkTargetByClass(array('ilObjXapiCmi5GUI','ilLearningProgressGUI','ilLPListOfObjectsGUI')));
                    // }
                    // $ilTabs->addSubTab("trac_summary", $lng->txt('trac_summary'), $ilCtrl->getLinkTargetByClass(array('ilObjXapiCmi5GUI','ilLearningProgressGUI', 'ilLPListOfObjectsGUI'), 'showObjectSummary'));
                // }
                // break;
        // }
    // }

    /**
     * show info screen
     *
     * @access public
     */
    public function infoScreen() 
    {
		global $ilCtrl;

        $this->tabs_gui->activateTab('infoScreen');

        include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
        $info = new ilInfoScreenGUI($this);
        
        // $info->addSection($this->txt('instructions'));
        // $info->addProperty("", $this->object->getInstructions());
        
        $info->enablePrivateNotes();
        
        // add view button
        if ($this->object->typedef->getAvailability() == ilXapiCmi5Type::AVAILABILITY_NONE)
        {
            ilUtil::sendFailure($this->lng->txt('xxcf_message_type_not_available'), false);
        } elseif ($this->object->getOnline())
        {
            if ($this->object->typedef->getLaunchType() == ilXapiCmi5Type::LAUNCH_TYPE_LINK)
            {
                $info->addButton($this->lng->txt("view"), $ilCtrl->getLinkTarget($this, "view"));
            } elseif ($this->object->typedef->getLaunchType() == ilXapiCmi5Type::LAUNCH_TYPE_PAGE)
             {
                $info->addButton($this->lng->txt("view"), $ilCtrl->getLinkTarget($this, "viewPage"));
            }
        }
		$ilCtrl->forwardCommand($info);
    }

    
    /**
     * view the object (default command)
     *
     * @access public
     */
    function viewObject() 
    {
        global $ilErr;

        switch ($this->object->typedef->getLaunchType())
        {
            // case ilXapiCmi5Type::LAUNCH_TYPE_LINK:
                // $this->object->trackAccess();
                // ilUtil::redirect($this->object->getLaunchLink());
                // break;

            // case ilXapiCmi5Type::LAUNCH_TYPE_PAGE:
                // $this->ctrl->redirect($this, "viewPage");
                // break;

            case ilXapiCmi5Type::LAUNCH_TYPE_EMBED:
    			$this->ctrl->redirect($this, "viewEmbed");
                break;

            default:
                $this->ctrl->redirect($this, "infoScreen");
                break;
        }
    }

    function getRegistration() {
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
    /**
     * view the embedded object
     *
     * @access public
     */
    function viewEmbedObject()
    {
        global $tpl, $ilErr, $ilUser, $DIC;
		$token = $this->object->fillToken();
        $this->object->trackAccess();
        $user_ident = "";
	    $activityId = $this->object->getActivityId();
        $registration = $this->getRegistration();
		//see initCmixUser()
		require_once __DIR__.'/class.ilXapiCmi5User.php';
		$cmixUser = new ilXapiCmi5User($this->object->getId(), $DIC->user()->getId(),$this->object->getPrivacyIdent());
		$user_ident = $cmixUser->getUsrIdent();
		if ($user_ident == '' || $user_ident == null) {
			$user_ident = ilXapiCmi5User::getIdent($this->object->getPrivacyIdent(), $ilUser);
			$cmixUser->setUsrIdent($user_ident);
			$cmixUser->save();
		}
		$privacy_name = ilXapiCmi5User::getNamePlugin($this->object->getPrivacyName(), $ilUser);
		
        $this->tabs_gui->activateTab('viewEmbed');
		$my_tpl = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/templates/default/tpl.view_embed.html', true, true);

		if ($this->object->getUseFetch() == true) {
            //$sess = openssl_encrypt(json_encode($_COOKIE),'aes128','SALT'); // needs a salt
            $sess = rawurlencode(base64_encode(json_encode($_COOKIE))); // needs a salt
			$my_tpl->setCurrentBlock("fetch");
			$my_tpl->setVariable('REF_ID', $this->object->getRefId());
			$my_tpl->setVariable('SESSION', $sess);
			$my_tpl->setVariable('ILIAS_URL', ILIAS_HTTP_PATH);
			$my_tpl->parseCurrentBlock();
		} else {
			$my_tpl->setCurrentBlock("no_fetch");
			$my_tpl->setVariable('ILIAS_URL', ILIAS_HTTP_PATH);
			$my_tpl->setVariable('LAUNCH_KEY', CLIENT_ID);//$this->object->getLaunchKey());
			$my_tpl->setVariable('LAUNCH_SECRET', $token);//$this->object->getLaunchSecret());
			$my_tpl->parseCurrentBlock();
		}

		$my_tpl->setVariable('ILIAS_URL', ILIAS_HTTP_PATH);
        $my_tpl->setVariable('XAPI_USER_ID', $user_ident); 
        $my_tpl->setVariable('XAPI_USER_NAME', $privacy_name);
        $my_tpl->setVariable('XAPI_ACTIVITY_ID', $activityId);
        $my_tpl->setVariable('XAPI_REGISTRATION', $registration);
        $my_tpl->setVariable('LAUNCH_URL', $this->object->getLaunchUrl());
        // $my_tpl->setVariable('LAUNCH_TARGET', 'window');
        $my_tpl->setVariable('OPEN_MODE_IFRAME', $this->object->getOpenMode());
        $my_tpl->setVariable('WIN_LAUNCH_WIDTH', '1000');
        $my_tpl->setVariable('WIN_LAUNCH_HEIGHT', '700');
        $my_tpl->setVariable('FRAME_LAUNCH_WIDTH', '1000');
        $my_tpl->setVariable('FRAME_LAUNCH_HEIGHT', '700');

		$tpl->setContent($my_tpl->get());
    }

    // /**
     // * view the object as a page
     // *
     // * @access public
     // */
    // function viewPageObject()
    // {
        // global $ilErr;

        // $this->object->trackAccess();
        // echo $this->object->getPageCode();
        // exit;
    // }

    /**
     * create new object form
     *
     * @access	public
     */
    function create()
    {
		global $ilErr;
		if (ilXapiCmi5Type::getCountTypesForCreate() == 0) {
			$pl = new ilXapiCmi5Plugin();
			$ilErr->raiseError($pl->txt('no_type_available_for_create'), $ilErr->MESSAGE);
		} else {
			parent::create();
		}
    }
    
    /**
     * cancel creation of a new object
     *
     * @access	public
     */
    function cancelCreate()
    {
        $this->ctrl->returnToParent($this);
    }

    /**
     * Edit object
     *
     * @access protected
     */
    public function statementsView($cmd)
    {
        require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5StatementsGUI.php');
        $gui = new ilXapiCmi5StatementsGUI($this); // beware this is the current GUI Class
        if ($cmd !== "asyncUserAutocomplete" && $cmd !== "asyncDelete") {
            $this->tabs_gui->activateTab('statements');
            $this->tpl->setContent($gui->executeCommand());
        }
        else {
            $gui->executeCommand();
        }
    }

    /**
     * Edit object
     *
     * @access protected
     */
    public function editObject()
    {
        global $ilErr, $ilAccess;

        $this->tabs_gui->activateTab('edit');
        // $this->tabs_gui->activateSubTab('settings');

        $this->editForm($this->loadFormValues());
        // $this->loadFormValues();
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * update object
     *
     * @access public
     */
    public function updateObject()
    {
        $this->tabs_gui->activateTab('edit');
        // $this->tabs_gui->activateSubTab('settings');
        
        $this->editForm();
        if ($this->form->checkInput())
        {
            $this->saveFormValues();
            ilUtil::sendInfo($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "edit");
        }
        else
        {
            $this->form->setValuesByPost();
            $this->tpl->setVariable('ADM_CONTENT', $this->form->getHTML());
        }
    }

    public function initCreateForm($a_new_type)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
		$form = parent::initCreateForm($a_new_type);
		
		$item = new ilRadioGroupInputGUI($this->lng->txt('type'), 'type_id');
		$item->setRequired(true);
		$types = ilXapiCmi5Type::_getTypesData(false, ilXapiCmi5Type::AVAILABILITY_CREATE);
		foreach ($types as $type)
		{
			$option = new ilRadioOption($type['title'], $type['type_id'], $type['description']);
			$item->addOption($option);
		}
		$form->addItem($item);

		return $form;
	}
	
    /**
     * @param ilObject $newObj
     * @global $DIC
     */
	public function afterSave(ilObject $newObj)
	{
	    global $tpl,$DIC; /** @var Container $DIC */

		$form = $this->initCreateForm($this->getType());
        if (!$form->checkInput())
        {
            $form->setValuesByPost();
            $tpl->setContent($form->getHTML());
            return;
        }

		$newObj->setTypeId((int) $form->getInput("type_id"));
		$newObj->setAvailabilityType( ilObjXapiCmi5::ACTIVATION_OFFLINE );
		//take default values for type
		$newObj->setPrivacyIdent( $newObj->typedef->getPrivacyIdent() );
		$newObj->setPrivacyName( $newObj->typedef->getPrivacyName() );
		$newObj->setOnlyMoveon( (int)$newObj->typedef->getOnlyMoveon() );
		$newObj->setAchieved( (int)$newObj->typedef->getAchieved() );
		$newObj->setAnswered( (int)$newObj->typedef->getAnswered() );
		$newObj->setCompleted( (int)$newObj->typedef->getCompleted() );
		$newObj->setFailed( (int)$newObj->typedef->getFailed() );
		$newObj->setInitialized( (int)$newObj->typedef->getInitialized() );
		$newObj->setPassed( (int)$newObj->typedef->getPassed() );
		$newObj->setProgressed( (int)$newObj->typedef->getProgressed() );
		$newObj->setSatisfied( (int)$newObj->typedef->getSatisfied() );
		$newObj->setTerminated( (int)$newObj->typedef->getTerminated() );
		$newObj->setHideData( (int)$newObj->typedef->getHideData() );
		$newObj->setTimestamp( (int)$newObj->typedef->getTimestamp() );
		$newObj->setDuration( (int)$newObj->typedef->getDuration() );
		$newObj->setNoSubstatements( (int)$newObj->typedef->getNoSubstatements() );
		$newObj->doUpdate();

		parent::afterSave($newObj);
	}

	
    /**
     * Init properties form
     *
     * @param		 array		(assoc) form values
     * @access       protected
     */
    protected function editForm($a_values = array())
    {
        if (is_object($this->form))
        {
            return true;
        }
		
		$forcePrivacySettings = $this->object->typedef->getForcePrivacySettings();

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $this->form = new ilPropertyFormGUI();
        $this->form->setFormAction($this->ctrl->getFormAction($this));

        $item = new ilTextInputGUI($this->lng->txt('title'), 'title');
        $item->setSize(40);
        $item->setMaxLength(128);
        $item->setRequired(true);
        $item->setInfo($this->txt('title_info'));
		$item->setValue($a_values['title']);        
        $this->form->addItem($item);

        $item = new ilTextAreaInputGUI($this->lng->txt('description'), 'description');
        $item->setInfo($this->txt('xxcf_description_info'));
        $item->setRows(2);
        //$item->setCols(80);
		$item->setValue($a_values['description']);        
        $this->form->addItem($item);

		$item = new ilNonEditableValueGUI($this->lng->txt('type'), 'type_title');
		$item->setValue($a_values['type_title']);
		$item->setInfo($a_values['type_description']);
		$this->form->addItem($item);
		
		$item = new ilCheckboxInputGUI($this->lng->txt('online'), 'online');
		$item->setInfo($this->txt("xxcf_online_info"));
		$item->setValue("1");
		if ($a_values['online'])
		{
			$item->setChecked(true);
		}        
		$this->form->addItem($item);
		
		$item = new ilFormSectionHeaderGUI();
		$item->setTitle($this->txt("launch_options"));
		$this->form->addItem($item);

		$item = new ilTextInputGUI($this->txt('launch_url'), 'launch_url');
		$item->setSize(40);
		$item->setMaxLength(128);
		$item->setRequired(true);
		$item->setInfo($this->txt('launch_url_info'));
		$item->setValue($a_values['launch_url']);        
		$this->form->addItem($item);
		
		$item = new ilTextInputGUI($this->txt('activity_id'), 'activity_id');
		$item->setSize(40);
		$item->setMaxLength(128);
		$item->setValidationRegexp(self::ACTIVITY_ID_VALIDATION_REGEXP);
		$item->setValidationFailureMessage($this->txt('activity_id_validation_failure'));
		$item->setRequired(true);
		// $item->setRequired(true);
		$item->setInfo($this->txt('activity_id_info'));
		$item->setValue($a_values['activity_id']);        
		$this->form->addItem($item);
		
		$item = new ilCheckboxInputGUI($this->txt('use_fetch'), 'use_fetch');
		$item->setInfo($this->txt("use_fetch_info"));
		$item->setValue("1");
		if ($a_values['use_fetch'])
		{
			$item->setChecked(true);
		}        
		$this->form->addItem($item);

		$item = new ilCheckboxInputGUI($this->txt('open_mode_iframe'), 'open_mode_iframe');
		$item->setInfo($this->txt("open_mode_iframe_info"));
		$item->setValue("1");
		if ($a_values['open_mode_iframe'])
		{
			$item->setChecked(true);
		}        
		$this->form->addItem($item);
		

		$item = new ilFormSectionHeaderGUI();
		if ($forcePrivacySettings) {
			$item->setTitle($this->txt("privacy_options") . ' (' . $this->txt("no_change_default_privacy_settings") .')');
		} else {
			$item->setTitle($this->txt("privacy_options"));
		}
		$this->form->addItem($item);

		$item = new ilRadioGroupInputGUI($this->txt('content_privacy_ident'), 'privacy_ident');
		$op = new ilRadioOption($this->txt('conf_privacy_ident_0'), 0);
		$item->addOption($op);
		// $op = new ilRadioOption($this->txt('conf_privacy_ident_1'), 1);
		// $item->addOption($op);
		// $op = new ilRadioOption($this->txt('conf_privacy_ident_2'), 2);
		// $item->addOption($op);
		$op = new ilRadioOption($this->txt('conf_privacy_ident_3'), 3);
		$item->addOption($op);
		$op = new ilRadioOption($this->txt('conf_privacy_ident_4'), 4);
		$item->addOption($op);
		$item->setValue($a_values['privacy_ident']);
		$item->setInfo($this->txt('info_privacy_ident'));
		$item->setRequired(false);
		$item->setDisabled($forcePrivacySettings);
		$this->form->addItem($item);

		$item = new ilRadioGroupInputGUI($this->txt('content_privacy_name'), 'privacy_name');
		$op = new ilRadioOption($this->txt('conf_privacy_name_0'), 0);
		$item->addOption($op);
		$op = new ilRadioOption($this->txt('conf_privacy_name_1'), 1);
		$item->addOption($op);
		$op = new ilRadioOption($this->txt('conf_privacy_name_2'), 2);
		$item->addOption($op);
		$op = new ilRadioOption($this->txt('conf_privacy_name_3'), 3);
		$item->addOption($op);
		$item->setValue($a_values['privacy_name']);
		$item->setInfo($this->txt('info_privacy_name'));
		$item->setRequired(false);
		$item->setDisabled($forcePrivacySettings);
		$this->form->addItem($item);

		$item = new ilFormSectionHeaderGUI();
		if ($forcePrivacySettings) {
			$item->setTitle($this->txt("title_data_reduction") . ' (' . $this->txt("no_change_default_privacy_settings") .')');
		} else {
			$item->setTitle($this->txt("title_data_reduction"));
		}
		$this->form->addItem($item);

		$item = new ilCheckboxInputGUI($this->txt('only_moveon_label'), 'only_moveon');
		$item->setInfo($this->txt('only_moveon_info'));
		$item->setValue("1");
		$item->setChecked((bool)$a_values['only_moveon']);

		$subitem = new ilCheckboxInputGUI($this->txt('achieved_label'), 'achieved');
		$subitem->setInfo($this->txt('achieved_info'));
		$subitem->setChecked((bool)$a_values['achieved']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$subitem = new ilCheckboxInputGUI($this->txt('answered_label'), 'answered');
		$subitem->setInfo($this->txt('answered_info'));
		$subitem->setChecked((bool)$a_values['answered']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$subitem = new ilCheckboxInputGUI($this->txt('completed_label'), 'completed');
		$subitem->setInfo($this->txt('completed_info'));
		$subitem->setChecked((bool)$a_values['completed']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$subitem = new ilCheckboxInputGUI($this->txt('failed_label'), 'failed');
		$subitem->setInfo($this->txt('failed_info'));
		$subitem->setChecked((bool)$a_values['failed']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$subitem = new ilCheckboxInputGUI($this->txt('initialized_label'), 'initialized');
		$subitem->setInfo($this->txt('initialized_info'));
		$subitem->setChecked((bool)$a_values['initialized']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$subitem = new ilCheckboxInputGUI($this->txt('passed_label'), 'passed');
		$subitem->setInfo($this->txt('passed_info'));
		$subitem->setChecked((bool)$a_values['passed']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$subitem = new ilCheckboxInputGUI($this->txt('progressed_label'), 'progressed');
		$subitem->setInfo($this->txt('progressed_info'));
		$subitem->setChecked((bool)$a_values['progressed']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$subitem = new ilCheckboxInputGUI($this->txt('satisfied_label'), 'satisfied');
		$subitem->setInfo($this->txt('satisfied_info'));
		$subitem->setChecked((bool)$a_values['satisfied']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$subitem = new ilCheckboxInputGUI($this->txt('terminated_label'), 'terminated');
		$subitem->setInfo($this->txt('terminated_info'));
		$subitem->setChecked((bool)$a_values['terminated']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$item->setDisabled($forcePrivacySettings);
		$this->form->addItem($item);


		$item = new ilCheckboxInputGUI($this->txt('hide_data_label'), 'hide_data');
		$item->setInfo($this->txt('hide_data_info'));
		$item->setValue("1");
		$item->setChecked((bool)$a_values['hide_data']);

		$subitem = new ilCheckboxInputGUI($this->txt('timestamp_label'), 'timestamp');
		$subitem->setInfo($this->txt('timestamp_info'));
		$subitem->setChecked((bool)$a_values['timestamp']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$subitem = new ilCheckboxInputGUI($this->txt('duration_label'), 'duration');
		$subitem->setInfo($this->txt('duration_info'));
		$subitem->setChecked((bool)$a_values['duration']);
		$subitem->setDisabled($forcePrivacySettings);
		$item->addSubItem($subitem);

		$item->setDisabled($forcePrivacySettings);
		$this->form->addItem($item);


		$item = new ilCheckboxInputGUI($this->txt('no_substatements_label'), 'no_substatements');
		$item->setInfo($this->txt('no_substatements_info'));
		$item->setValue("1");
		$item->setChecked((bool)$a_values['no_substatements']);
		$item->setDisabled($forcePrivacySettings);
		$this->form->addItem($item);


		$item = new ilFormSectionHeaderGUI();
		$item->setTitle($this->txt("log_options"));
		$this->form->addItem($item);

		$item = new ilCheckboxInputGUI($this->txt('show_statements'), 'show_statements');
		$item->setInfo($this->txt("show_statements_info"));
		$item->setValue("1");
		if ($a_values['show_statements'])
		{
			$item->setChecked(true);
		}        
		$this->form->addItem($item);

		$this->form->setTitle($this->lng->txt('settings'));
		$this->form->addCommandButton("update", $this->lng->txt("save"));
		$this->form->addCommandButton("view", $this->lng->txt("cancel"));

    }
    

    /**
     * Fill the properties form with database values
     *
     * @access   protected
     */
    protected function loadFormValues()
    {
		$values = array();

		$values['title'] = $this->object->getTitle();
		$values['description'] = $this->object->getDescription();
		$values['type_id'] = $this->object->getTypeId();
		$values['type_title'] = $this->object->typedef->getTitle();
		$values['type_description'] = $this->object->typedef->getDescription();
		$values['instructions'] = $this->object->getInstructions();
		if ($this->object->getAvailabilityType() == ilObjXapiCmi5::ACTIVATION_UNLIMITED)
		{
			$values['online'] = '1';
		}
		$values['launch_url'] = $this->object->getLaunchUrl();
		$values['activity_id'] = $this->object->getActivityId();
		// $values['launch_key'] = $this->object->getLaunchKey();
		// $values['launch_secret'] = $this->object->getLaunchSecret();
		$values['show_statements'] = $this->object->isStatementsReportEnabled();
		$values['use_fetch'] = $this->object->getUseFetch();
		$values['open_mode_iframe'] = $this->object->getOpenMode();
		$values['privacy_ident'] = $this->object->getPrivacyIdent();
		$values['privacy_name'] = $this->object->getPrivacyName();

		$values['only_moveon'] = $this->object->getOnlyMoveon();
		$values['achieved'] = $this->object->getAchieved();
		$values['answered'] = $this->object->getAnswered();
		$values['completed'] = $this->object->getCompleted();
		$values['failed'] = $this->object->getFailed();
		$values['initialized'] = $this->object->getInitialized();
		$values['passed'] = $this->object->getPassed();
		$values['progressed'] = $this->object->getProgressed();
		$values['satisfied'] = $this->object->getSatisfied();
		$values['terminated'] = $this->object->getTerminated();
		$values['hide_data'] = $this->object->getHideData();
		$values['timestamp'] = $this->object->getTimestamp();
		$values['duration'] = $this->object->getDuration();
		$values['no_substatements'] = $this->object->getNoSubstatements();
		

		return $values;
    }

    
    /**
     * Save the property form values to the object
     *
     * @access   protected
     */
    protected function saveFormValues() 
    {

        $this->object->setTitle($this->form->getInput("title"));
        $this->object->setDescription($this->form->getInput("description"));
        // if ($this->form->getInput("type_id"))
        // {
            // $this->object->setTypeId($this->form->getInput("type_id"));
        // }
        $this->object->setAvailabilityType($this->form->getInput('online') ? ilObjXapiCmi5::ACTIVATION_UNLIMITED : ilObjXapiCmi5::ACTIVATION_OFFLINE);
		$this->object->setLaunchUrl($this->form->getInput("launch_url"));
		$this->object->setActivityId($this->form->getInput("activity_id"));
		// $this->object->setLaunchKey($this->form->getInput("launch_key"));
		// $this->object->setLaunchSecret($this->form->getInput("launch_secret"));
		$this->object->setStatementsReportEnabled($this->form->getInput("show_statements"));
		$this->object->setUseFetch($this->form->getInput("use_fetch"));
		$this->object->setOpenMode($this->form->getInput("open_mode_iframe"));
		if ($this->object->typedef->getForcePrivacySettings() == false) {
			$this->object->setPrivacyIdent($this->form->getInput("privacy_ident"));
			$this->object->setPrivacyName($this->form->getInput("privacy_name"));
			$this->object->setOnlyMoveon((bool)$this->form->getInput("only_moveon"));
			$this->object->setAchieved((bool)$this->form->getInput("achieved"));
			$this->object->setAnswered((bool)$this->form->getInput("answered"));
			$this->object->setCompleted((bool)$this->form->getInput("completed"));
			$this->object->setFailed((bool)$this->form->getInput("failed"));
			$this->object->setInitialized((bool)$this->form->getInput("initialized"));
			$this->object->setPassed((bool)$this->form->getInput("passed"));
			$this->object->setProgressed((bool)$this->form->getInput("progressed"));
			$this->object->setSatisfied((bool)$this->form->getInput("satisfied"));
			$this->object->setTerminated((bool)$this->form->getInput("terminated"));
			$this->object->setHideData((bool)$this->form->getInput("hide_data"));
			$this->object->setTimestamp((bool)$this->form->getInput("timestamp"));
			$this->object->setDuration((bool)$this->form->getInput("duration"));
			$this->object->setNoSubstatements((bool)$this->form->getInput("no_substatements"));
		}

        $this->object->update();
    }
    
    

    /**
     * Edit the learning progress settings
     */
    protected function editLPSettingsObject()
    {
        $this->tabs_gui->activateTab('learning_progress');
        $this->tabs_gui->activateSubTab('lp_settings');

        $this->initFormLPSettings();
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * Init the form for Learning progress settings
     */
    protected function initFormLPSettings()
    {
        global $ilSetting, $lng, $ilCtrl;

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($ilCtrl->getFormAction($this));
        $form->setTitle($this->txt('lp_settings'));

        $rg = new ilRadioGroupInputGUI($this->txt('lp_mode'), 'lp_mode');
        $rg->setRequired(true);
        $rg->setValue($this->object->getLPMode());
        $ro = new ilRadioOption($this->txt('lp_inactive'),ilObjXapiCmi5::LP_INACTIVE, $this->txt('lp_inactive_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->txt('lp_completed'),ilObjXapiCmi5::LP_Completed, $this->txt('lp_completed_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->txt('lp_passed'),ilObjXapiCmi5::LP_Passed, $this->txt('lp_passed_info'));
        $rg->addOption($ro);
        // $ro = new ilRadioOption($this->txt('lp_completed_and_passed'),ilObjXapiCmi5::LP_CompletedAndPassed, $this->txt('lp_completed_and_passed_info'));
        // $rg->addOption($ro);
        $ro = new ilRadioOption($this->txt('lp_completed_or_passed'),ilObjXapiCmi5::LP_CompletedOrPassed, $this->txt('lp_completed_or_passed_info'));
        $rg->addOption($ro);
        $form->addItem($rg);

        $form->addCommandButton('updateLPSettings', $lng->txt('save'));
        $this->form = $form;

    }

    /**
     * Update the LP settings
     */
    protected function updateLPSettingsObject()
    {
    	global $DIC; /** @var Container $DIC */
		$tpl = $DIC->ui()->mainTemplate();

        $this->tabs_gui->activateTab('learning_progress');
        $this->tabs_gui->activateSubTab('lp_settings');

        $this->initFormLPSettings();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHTML());
            return;
        }

        $this->object->setLPMode($this->form->getInput('lp_mode'));
        $this->object->update();
        $this->ctrl->redirect($this, 'editLPSettings');
    }

     /**
     * Refresh the meta data
     *
     * @access   public
     */
    public function refreshMetaObject()
    {
        $this->object->fetchMetaData(self::META_TIMEOUT_REFRESH);
        $this->ctrl->redirect($this, "infoScreen");
    }

    /**
     * check a token for validity
     * 
     * @return boolean	check is ok
     */
    function checkToken()
    {
        $obj = new ilObjXapiCmi5();
        $value = $obj->checkToken();
        echo $value;
    }
	
	protected function showExportObject() {
		require_once("./Services/Export/classes/class.ilExportGUI.php");
		$export = new ilExportGUI($this);
		$export->addFormat("xml");
		$ret = $this->ctrl->forwardCommand($export);
	}
	/**
	 * erase!
	 */
	private function activateTab() {
		$next_class = $this->ctrl->getCmdClass();

		switch($next_class) {
			case 'ilexportgui':
				$this->tabs->activateTab("export");
				break;
		}

		return;
	}

}

?>
