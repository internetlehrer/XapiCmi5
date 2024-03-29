<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");

/**
 * xAPI plugin: configuration GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
class ilXapiCmi5ConfigGUI extends ilPluginConfigGUI
{
	/** @var ilXapiCmi5LrsType $type */
	private $type;

	/** @var ilPropertyFormGUI $form */
	private $form;

	/**
	 * perform command
	 */
	public function performCommand($cmd)
	{
		global $tree, $rbacsystem, $ilErr, $lng, $ilCtrl, $tpl;
		
		$this->plugin_object->includeClass('class.ilXapiCmi5LrsType.php');
		$this->plugin_object->includeClass('class.ilObjXapiCmi5.php');
		
		// control flow
		$cmd = $ilCtrl->getCmd($this);
		switch ($cmd)
		{
			case 'editType':
		   	case 'submitSettings':
			case 'deleteType':
			case 'deleteTypeConfirmed':
				$this->type = new ilXapiCmi5LrsType($_GET['type_id']);
				$tpl->setDescription($this->type->getTitle());
				
				$ilCtrl->saveParameter($this, 'type_id');
				$this->initTabs('edit_type');
		   		$this->$cmd();
				break;
				
			default:
				$this->initTabs();
				if (!$cmd)
				{
					$cmd = "configure";
				}
				$this->$cmd();
				break;
		}
	}
	
	/**
	 * Get a plugin specific language text
	 * 
	 * @param 	string	language var
	 */
	function txt($a_var)
	{
		return $this->plugin_object->txt($a_var);
	}
	
	/**
	 * Init Tabs
	 * 
	 * @param string	mode ('edit_type' or '')
	 */
	function initTabs($a_mode = "")
	{
		global $ilCtrl, $ilTabs, $lng;

		switch ($a_mode)
		{
			case "edit_type":
				$ilTabs->clearTargets();
				
				$ilTabs->setBackTarget(
					$this->plugin_object->txt('content_types'),
					$ilCtrl->getLinkTarget($this, 'listTypes')
				);

				$ilTabs->addTab("edit_type", 
					$this->plugin_object->txt('xxcf_edit_type'), 
					$ilCtrl->getLinkTarget($this, 'editType')
				);
				
				$ilTabs->addSubTab("type_settings", 
					$this->plugin_object->txt('type_settings'), 
					$ilCtrl->getLinkTarget($this, 'editType')
				);
				
				break;
				
			default:
				$ilTabs->addTab("types", 
					$this->plugin_object->txt('content_types'), 
					$ilCtrl->getLinkTarget($this, 'listTypes')
				);
				break;	
		}
	}

	/**
	 * Entry point for configuring the module
	 */
	function configure()
	{
		$this->listTypes();
	}

	/**
	 * Show a list of the xxcf types
	 */
	function listTypes()
	{
		global $tpl;

		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5LrsTypesTableGUI.php');
		$table_gui = new ilXapiCmi5LrsTypesTableGUI($this, 'listTypes');
		$table_gui->init($this);
		$tpl->setContent($table_gui->getHTML());
	}

	/**
	 * Show the form to add a new type
	 */
	function createType()
	{
		global $tpl;
		$this->type = new ilXapiCmi5LrsType();
		$this->initTypeForm(true);
		$tpl->setContent($this->form->getHTML());
	}
	
	/**
	 * 
	 * 
	 * @return unknown_type
	 */
	private function initTypeForm($create = false)
	{
		global $ilCtrl, $lng;
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->setTitle($this->txt('create_type'));
		
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->setTitle($this->plugin_object->txt('lrs_type'));

		$item = new ilTextInputGUI($this->plugin_object->txt('conf_title'), 'title');
		$item->setValue($this->type->getTitle());
		$item->setInfo($this->plugin_object->txt('info_title'));
		$item->setRequired(true);
		$item->setMaxLength(255);
		$form->addItem($item);

		$item = new ilTextInputGUI($this->plugin_object->txt('conf_description'), 'description');
		$item->setValue($this->type->getDescription());
		$item->setInfo($this->plugin_object->txt('info_description'));
		$form->addItem($item);
		
		$item = new ilSelectInputGUI($this->plugin_object->txt('conf_availability'), 'availability');
		$item->setOptions (
				array(
					ilXapiCmi5LrsType::AVAILABILITY_CREATE => $this->plugin_object->txt('conf_availability_' . ilXapiCmi5LrsType::AVAILABILITY_CREATE),
					ilXapiCmi5LrsType::AVAILABILITY_EXISTING => $this->plugin_object->txt('conf_availability_' . ilXapiCmi5LrsType::AVAILABILITY_EXISTING),
					ilXapiCmi5LrsType::AVAILABILITY_NONE => $this->plugin_object->txt('conf_availability_' . ilXapiCmi5LrsType::AVAILABILITY_NONE)
				)
		);
		$item->setValue($this->type->getAvailability());
		$item->setInfo($this->plugin_object->txt('info_availability'));
		$item->setRequired(true);
		$form->addItem($item);

		$item = new ilRadioGroupInputGUI($this->plugin_object->txt('conf_lrs_type_id'), 'lrs_type_id');
		$op = new ilRadioOption($this->plugin_object->txt('conf_lrs_type_id_0'), 0);
		// $op -> setInfo($this->plugin_object->txt('conf_log_level_0'));
		$item->addOption($op);
		$item->setValue($this->type->getLrsTypeId());
		$item->setInfo($this->plugin_object->txt('info_lrs_type_id'));
		$item->setRequired(false);
		$form->addItem($item);

        $sectionHeader = new ilFormSectionHeaderGUI();
        $sectionHeader->setTitle($this->plugin_object->txt('lrs_authentication'));
        $form->addItem($sectionHeader);

		// Endpoint 1

		$item = new ilTextInputGUI($this->plugin_object->txt('conf_lrs_endpoint_1'), 'lrs_endpoint_1');
		$item->setValue($this->type->getLrsEndpoint());
		$item->setInfo($this->plugin_object->txt('info_lrs_endpoint'));
		$item->setRequired(true);
		$item->setMaxLength(255);
		$form->addItem($item);

		$item = new ilTextInputGUI($this->plugin_object->txt('conf_lrs_key_1'), 'lrs_key_1');
		$item->setValue($this->type->getLrsKey());
		$item->setInfo($this->plugin_object->txt('info_lrs_key'));
		$item->setRequired(true);
		$item->setMaxLength(128);
		$form->addItem($item);

		$item = new ilTextInputGUI($this->plugin_object->txt('conf_lrs_secret_1'), 'lrs_secret_1');
		$item->setValue($this->type->getLrsSecret());
		$item->setInfo($this->plugin_object->txt('info_lrs_secret'));
		$item->setRequired(true);
		$item->setMaxLength(128);
		$form->addItem($item);

		// Endpoint 2

		$item = new ilTextInputGUI($this->plugin_object->txt('conf_lrs_endpoint_2'), 'lrs_endpoint_2');
		$item->setValue($this->type->getLrsEndpoint2());
		$item->setInfo($this->plugin_object->txt('info_lrs_endpoint'));
		$item->setRequired(false);
		$item->setMaxLength(255);
		$form->addItem($item);

		$item = new ilTextInputGUI($this->plugin_object->txt('conf_lrs_key_2'), 'lrs_key_2');
		$item->setValue($this->type->getLrsKey2());
		$item->setInfo($this->plugin_object->txt('info_lrs_key'));
		$item->setRequired(false);
		$item->setMaxLength(128);
		$form->addItem($item);

		$item = new ilTextInputGUI($this->plugin_object->txt('conf_lrs_secret_2'), 'lrs_secret_2');
		$item->setValue($this->type->getLrsSecret2());
		$item->setInfo($this->plugin_object->txt('info_lrs_secret'));
		$item->setRequired(false);
		$item->setMaxLength(128);
		$form->addItem($item);

		// Endpoint use

		$item = new ilRadioGroupInputGUI($this->plugin_object->txt('conf_endpoint_use'), 'endpoint_use');
		$op = new ilRadioOption($this->plugin_object->txt('conf_endpoint_use_1only'), $this->type::ENDPOINT_USE_1);
		$item->addOption($op);
		$op = new ilRadioOption($this->plugin_object->txt('conf_endpoint_use_1default_2fallback'), $this->type::ENDPOINT_USE_1DEFAULT_2FALLBACK);
		$item->addOption($op);
		$op = new ilRadioOption($this->plugin_object->txt('conf_endpoint_use_1fallback_2default'), $this->type::ENDPOINT_USE_1FALLBACK_2DEFAULT);
		$item->addOption($op);
		$op = new ilRadioOption($this->plugin_object->txt('conf_endpoint_use_2only'), $this->type::ENDPOINT_USE_2);
		$item->addOption($op);
		$item->setInfo($this->plugin_object->txt('info_endpoint_use'));
		$item->setValue($this->type->getEndpointUse());
		$item->setRequired(false);
		$form->addItem($item);

	    $sectionHeader = new ilFormSectionHeaderGUI();
        $sectionHeader->setTitle($this->plugin_object->txt('privacy_options'));
        $form->addItem($sectionHeader);
	
		$item = new ilRadioGroupInputGUI($this->plugin_object->txt('conf_privacy_ident'), 'privacy_ident');
        $op = new ilRadioOption(
            $this->plugin_object->txt('conf_privacy_ident_il_uuid_user_id'),
            ilXapiCmi5LrsType::PRIVACY_IDENT_IL_UUID_USER_ID
        );
        $op->setInfo($this->plugin_object->txt('conf_privacy_ident_il_uuid_user_id_info'));
        $item->addOption($op);
        $op = new ilRadioOption(
            $this->plugin_object->txt('conf_privacy_ident_il_uuid_login'),
            ilXapiCmi5LrsType::PRIVACY_IDENT_IL_UUID_LOGIN
        );
        $op->setInfo($this->plugin_object->txt('conf_privacy_ident_il_uuid_login_info'));
        $item->addOption($op);
        $op = new ilRadioOption(
            $this->plugin_object->txt('conf_privacy_ident_il_uuid_ext_account'),
            ilXapiCmi5LrsType::PRIVACY_IDENT_IL_UUID_EXT_ACCOUNT
        );
        $op->setInfo($this->plugin_object->txt('conf_privacy_ident_il_uuid_ext_account_info'));
        $item->addOption($op);
        $op = new ilRadioOption(
            $this->plugin_object->txt('conf_privacy_ident_il_uuid_random'),
            ilXapiCmi5LrsType::PRIVACY_IDENT_IL_UUID_RANDOM
        );
        $op->setInfo($this->plugin_object->txt('conf_privacy_ident_il_uuid_random_info'));
        $item->addOption($op);
        $op = new ilRadioOption(
            $this->plugin_object->txt('conf_privacy_ident_real_email'),
            ilXapiCmi5LrsType::PRIVACY_IDENT_REAL_EMAIL
        );
        $op->setInfo($this->plugin_object->txt('conf_privacy_ident_real_email_info'));
        $item->addOption($op);
        $item->setValue($this->type->getPrivacyIdent());
        $item->setInfo(
            $this->plugin_object->txt('conf_privacy_ident_info') . ' ' . ilXapiCmi5User::getIliasUuid()
        );
        $item->setRequired(false);
        $form->addItem($item);


		$item = new ilRadioGroupInputGUI($this->plugin_object->txt('conf_privacy_name'), 'privacy_name');
        $op = new ilRadioOption($this->plugin_object->txt('conf_privacy_name_none'), ilXapiCmi5LrsType::PRIVACY_NAME_NONE);
        $op->setInfo($this->plugin_object->txt('conf_privacy_name_none_info'));
        $item->addOption($op);
        $op = new ilRadioOption($this->plugin_object->txt('conf_privacy_name_firstname'), ilXapiCmi5LrsType::PRIVACY_NAME_FIRSTNAME);
        $op->setInfo($this->plugin_object->txt('conf_privacy_name_firstname_info'));
        $item->addOption($op);
        $op = new ilRadioOption($this->plugin_object->txt('conf_privacy_name_lastname'), ilXapiCmi5LrsType::PRIVACY_NAME_LASTNAME);
        $op->setInfo($this->plugin_object->txt('conf_privacy_name_lastname_info'));
        $item->addOption($op);
        $op = new ilRadioOption($this->plugin_object->txt('conf_privacy_name_fullname'), ilXapiCmi5LrsType::PRIVACY_NAME_FULLNAME);
        $op->setInfo($this->plugin_object->txt('conf_privacy_name_fullname_info'));
        $item->addOption($op);
		$item->setValue($this->type->getPrivacyName());
		$item->setInfo($this->plugin_object->txt('conf_privacy_name_info'));
		$item->setRequired(false);
		$form->addItem($item);

		// $sectionHeader = new ilFormSectionHeaderGUI();
        // $sectionHeader->setTitle($this->plugin_object->txt('title_data_reduction'));
        // $form->addItem($sectionHeader);
	

        $item = new ilCheckboxInputGUI($this->plugin_object->txt('only_moveon_label'), 'only_moveon');
        $item->setInfo($this->plugin_object->txt('only_moveon_info'));
        $item->setChecked($this->type->getOnlyMoveon());

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('achieved_label'), 'achieved');
        $subitem->setInfo($this->plugin_object->txt('achieved_info'));
        $subitem->setChecked($this->type->getAchieved());
        $item->addSubItem($subitem);

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('answered_label'), 'answered');
        $subitem->setInfo($this->plugin_object->txt('answered_info'));
        $subitem->setChecked($this->type->getAnswered());
        $item->addSubItem($subitem);

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('completed_label'), 'completed');
        $subitem->setInfo($this->plugin_object->txt('completed_info'));
        $subitem->setChecked($this->type->getCompleted());
        $item->addSubItem($subitem);

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('failed_label'), 'failed');
        $subitem->setInfo($this->plugin_object->txt('failed_info'));
        $subitem->setChecked($this->type->getFailed());
        $item->addSubItem($subitem);

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('initialized_label'), 'initialized');
        $subitem->setInfo($this->plugin_object->txt('initialized_info'));
        $subitem->setChecked($this->type->getInitialized());
        $item->addSubItem($subitem);

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('passed_label'), 'passed');
        $subitem->setInfo($this->plugin_object->txt('passed_info'));
        $subitem->setChecked($this->type->getPassed());
        $item->addSubItem($subitem);

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('progressed_label'), 'progressed');
        $subitem->setInfo($this->plugin_object->txt('progressed_info'));
        $subitem->setChecked($this->type->getProgressed());
        $item->addSubItem($subitem);

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('satisfied_label'), 'satisfied');
        $subitem->setInfo($this->plugin_object->txt('satisfied_info'));
        $subitem->setChecked($this->type->getSatisfied());
        $item->addSubItem($subitem);

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('terminated_label'), 'terminated');
        $subitem->setInfo($this->plugin_object->txt('terminated_info'));
        $subitem->setChecked($this->type->getTerminated());
        $item->addSubItem($subitem);

        $form->addItem($item);

        $item = new ilCheckboxInputGUI($this->plugin_object->txt('hide_data_label'), 'hide_data');
        $item->setInfo($this->plugin_object->txt('hide_data_info'));
        $item->setChecked($this->type->getHideData());

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('timestamp_label'), 'timestamp');
        $subitem->setInfo($this->plugin_object->txt('timestamp_info'));
        $subitem->setChecked($this->type->getTimestamp());
        $item->addSubItem($subitem);

        $subitem = new ilCheckboxInputGUI($this->plugin_object->txt('duration_label'), 'duration');
        $subitem->setInfo($this->plugin_object->txt('duration_info'));
        $subitem->setChecked($this->type->getDuration());
        $item->addSubItem($subitem);

        $form->addItem($item);

        $item = new ilCheckboxInputGUI($this->plugin_object->txt('no_substatements_label'), 'no_substatements');
        $item->setInfo($this->plugin_object->txt('no_substatements_info'));
        $item->setChecked($this->type->getNoSubstatements());
        $form->addItem($item);

		$item = new ilCheckboxInputGUI($this->plugin_object->txt('no_unallocatable_label'), 'no_unallocatable');
		$item->setInfo($this->plugin_object->txt('no_unallocatable_info'));
		$item->setChecked($this->type->getNoUnallocatableStatements());
		$form->addItem($item);

		$item = new ilRadioGroupInputGUI($this->plugin_object->txt('conf_privacy_setting_conf'), 'force_privacy_setting');
        $op = new ilRadioOption($this->plugin_object->txt('conf_privacy_setting_default'), 0);
        $item->addOption($op);
        $op = new ilRadioOption($this->plugin_object->txt('conf_privacy_setting_force'), 1);
        $item->addOption($op);
        $item->setValue($this->type->getForcePrivacySettings());
        $form->addItem($item);

        $sectionHeader = new ilFormSectionHeaderGUI();
        $sectionHeader->setTitle($this->plugin_object->txt('hints'));
        $form->addItem($sectionHeader);


		$item = new ilCheckboxInputGUI($this->getPluginObject()->txt('conf_external_lrs'), 'external_lrs');
		$item->setValue($this->type->getExternalLrs());
		$item->setInfo($this->plugin_object->txt('info_external_lrs'));
		$form->addItem($item);
		
		$item = new ilTextAreaInputGUI($this->plugin_object->txt('conf_privacy_comment_default'), 'privacy_comment_default');
		$item->setInfo($this->plugin_object->txt('info_privacy_comment_default'));
		$item->setValue($this->type->getPrivacyCommentDefault());
		$item->setRows(5);
		//$item->setCols(80);
		$form->addItem($item);

		$item = new ilTextAreaInputGUI($this->plugin_object->txt('conf_remarks'), 'remarks');
		$item->setInfo($this->plugin_object->txt('info_remarks'));
		$item->setValue($this->type->getRemarks());
		$item->setRows(5);
		//$item->setCols(80);
		$form->addItem($item);

		if ($create == true) $form->addCommandButton('submitNewType', $lng->txt('create'));
		else $form->addCommandButton('submitSettings', $lng->txt('save'));
		$form->addCommandButton('listTypes', $lng->txt('cancel'));
		
		$this->form = $form;
	}
	
	/**
	 * Submit a new type
	 */
	private function submitNewType()
	{
		$this->type = new ilXapiCmi5LrsType();
		$this->saveSettings(true);
	}
	/**
	 * Submit the form to update
	 */
	function submitSettings()
	{
		$this->saveSettings(false);
	}
	
	private function saveSettings($create = false)
	{
		global $lng,  $ilTabs, $ilCtrl, $tpl;
		$ilTabs->activateSubTab('type_settings');
		
		$this->initTypeForm($create);
		if (!$this->form->checkInput())
		{
			$this->form->setValuesByPost();
			$tpl->setContent($this->form->getHTML());
			return;
		} 
		else
		{
			$this->type->setTitle($this->form->getInput("title"));
			$this->type->setDescription($this->form->getInput("description"));
			$this->type->setAvailability($this->form->getInput("availability"));
			//$this->type->setLogLevel($this->form->getInput("log_level"));
			$this->type->setLrsTypeId($this->form->getInput("lrs_type_id"));
			$this->type->setLrsEndpoint($this->form->getInput("lrs_endpoint_1"));
			$this->type->setLrsKey($this->form->getInput("lrs_key_1"));
			$this->type->setLrsSecret($this->form->getInput("lrs_secret_1"));
			$this->type->setLrsEndpoint2($this->form->getInput("lrs_endpoint_2"));
			$this->type->setLrsKey2($this->form->getInput("lrs_key_2"));
			$this->type->setLrsSecret2($this->form->getInput("lrs_secret_2"));
			$this->type->setEndpointUse($this->form->getInput("endpoint_use"));
			$this->type->setExternalLrs($this->form->getInput("external_lrs"));
			$this->type->setPrivacyIdent($this->form->getInput("privacy_ident"));
			$this->type->setPrivacyName($this->form->getInput("privacy_name"));
			$this->type->setPrivacyCommentDefault($this->form->getInput("privacy_comment_default"));
			$this->type->setRemarks($this->form->getInput("remarks"));

			$this->type->setOnlyMoveon((bool)$this->form->getInput("only_moveon"));
			$this->type->setAchieved((bool)$this->form->getInput("achieved"));
			$this->type->setAnswered((bool)$this->form->getInput("answered"));
			$this->type->setCompleted((bool)$this->form->getInput("completed"));
			$this->type->setFailed((bool)$this->form->getInput("failed"));
			$this->type->setInitialized((bool)$this->form->getInput("initialized"));
			$this->type->setPassed((bool)$this->form->getInput("passed"));
			$this->type->setProgressed((bool)$this->form->getInput("progressed"));
			$this->type->setSatisfied((bool)$this->form->getInput("satisfied"));
			$this->type->setTerminated((bool)$this->form->getInput("terminated"));
			$this->type->setHideData((bool)$this->form->getInput("hide_data"));
			$this->type->setTimestamp((bool)$this->form->getInput("timestamp"));
			$this->type->setDuration((bool)$this->form->getInput("duration"));
			$this->type->setNoSubstatements((bool)$this->form->getInput("no_substatements"));
			$this->type->setNoUnallocatableStatements((bool)$this->form->getInput("no_unallocatable"));

			$this->type->setForcePrivacySettings((bool) $this->form->getInput("force_privacy_setting"));
			if ($this->type->getForcePrivacySettings()) {
				$this->type->updatePrivacySettingsFromLrsType();
			}

			ilUtil::sendSuccess($this->plugin_object->txt('type_saved'), true);
			if ($create == true) {
				$this->type->create();
				$ilCtrl->redirect($this, 'listTypes');	
			}
			else {
				$this->type->update();
				$ilCtrl->redirect($this, 'editType');	
			}
		}
	}

	/**
	 * Show the form to edit an existing type
	 */
	function editType()
	{
		global $ilCtrl, $ilTabs, $tpl;

		$ilTabs->activateSubTab('type_settings');
		$this->initTypeForm(false);
		$tpl->setContent($this->form->getHTML());
	}
	
	/**
	 * Show a confirmation screen to delete a type
	 */
	function deleteType()
	{
		global $ilCtrl, $lng, $tpl;

		require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');

		$gui = new ilConfirmationGUI();
		$gui->setFormAction($ilCtrl->getFormAction($this));
		$gui->setHeaderText($this->txt('delete_type'));
		$gui->addItem('type_id', $this->type->getTypeId(), $this->type->getTitle());
		$gui->setConfirm($lng->txt('delete'), 'deleteTypeConfirmed');
		$gui->setCancel($lng->txt('cancel'), 'listTypes');

		$tpl->setContent($gui->getHTML());
	}

	/**
	 * Delete a type after confirmation
	 */
	function deleteTypeConfirmed()
	{
		global $ilCtrl, $lng;

		$this->type->delete();
		ilUtil::sendSuccess($this->txt('type_deleted'), true);
		$ilCtrl->redirect($this, 'listTypes');
	}
}
?>
