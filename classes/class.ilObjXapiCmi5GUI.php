<?php 
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

use ILIAS\DI\Container;

include_once('./Services/Repository/classes/class.ilObjectPluginGUI.php');
include_once('./Services/MetaData/classes/class.ilMDEditorGUI.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5SettingsGUI.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/exceptions/class.ilXapiCmi5Exception.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5Access.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5LrsType.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5LrsTypeList.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5LaunchGUI.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/XapiResults/class.ilXapiCmi5StatementEvaluation.php');


/**
 * xApi plugin: repository object GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 * 
 * @ilCtrl_isCalledBy ilObjXapiCmi5GUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilUIPluginRouterGUI
 * @ilCtrl_Calls ilObjXapiCmi5GUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonactionDispatcherGUI, ilLearningProgressGUI, ilExportGUI, ilMDEditorGUI, ilObjectMetaDataGUI
 */
class ilObjXapiCmi5GUI extends ilObjectPluginGUI
{
    const LANGUAGE_MODULE = 'rep_robj_xxcf'; //plugin, sonst cmix

    const TAB_ID_INFO = 'tab_info';
    const TAB_ID_SETTINGS = 'tab_settings';
    const TAB_ID_STATEMENTS = 'tab_statements';
    const TAB_ID_SCORING = 'tab_scoring';
    const TAB_ID_LEARNING_PROGRESS = 'learning_progress';
    const TAB_ID_METADATA = 'tab_metadata';
    const TAB_ID_EXPORT = 'tab_export';
    const TAB_ID_PERMISSIONS = 'perm_settings';
    
    const CMD_INFO_SCREEN = 'infoScreen';
    const CMD_FETCH_XAPI_STATEMENTS = 'fetchXapiStatements';
    
    const DEFAULT_CMD = self::CMD_INFO_SCREEN;

    const NEW_OBJ_TITLE = "";

    /**
     * @var ilObjXapiCmi5 $object
     */
    public $object;

    /**
     * @var ilObjXapiCmi5Access
     */
    public $cmixAccess;


#only plugin start
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

#only plugin end


    public function __construct($a_id = 0, $a_id_type = self::REPOSITORY_NODE_ID, $a_parent_node_id = 0)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        parent::__construct($a_id, $a_id_type, $a_parent_node_id);

        if ($this->object instanceof ilObjXapiCmi5) {
            $this->cmixAccess = ilObjXapiCmi5Access::getInstance($this->object);
        }
        $DIC->language()->loadLanguageModule(self::LANGUAGE_MODULE);
    }

    /**
     * Get type.
     */
    public function getType()
    {
        return "xxcf";
    }

    public function initCreateForm($a_new_type)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setTarget("_top");
        $form->setFormAction($this->ctrl->getFormAction($this, "save"));
        $form->setTitle($this->getText($a_new_type . "_new"));

        $form = $this->initDidacticTemplate($form);

        $title = new ilHiddenInputGUI('title', 'title');
        $title->setValue(self::NEW_OBJ_TITLE);
        $form->addItem($title);

        $type = new ilRadioGroupInputGUI('Type', 'content_type');
        $type->setRequired(true);

        $typeLearningModule = new ilRadioOption($this->getText('cmix_add_cmi5_lm'), ilObjXapiCmi5::CONT_TYPE_CMI5);
        $typeLearningModule->setInfo($this->getText('cmix_add_cmi5_lm_info'));
        $type->addOption($typeLearningModule);

        $typeGenericModule = new ilRadioOption($this->getText('cmix_add_xapi_standard_object'), ilObjXapiCmi5::CONT_TYPE_GENERIC);
        $typeGenericModule->setInfo($this->getText('cmix_add_xapi_standard_object_info'));
        $type->addOption($typeGenericModule);

        $form->addItem($type);

        $item = new ilRadioGroupInputGUI($this->getText('cmix_add_lrs_type'), 'lrs_type_id');
        $item->setRequired(true);
        $types = ilXapiCmi5LrsTypeList::getTypesData(false, ilXapiCmi5LrsType::AVAILABILITY_CREATE);
        foreach ($types as $type) {
            $option = new ilRadioOption($type['title'], $type['type_id'], $type['description']);
            $item->addOption($option);
        }
        #$item->setValue($this->object->getLrsType()->getTypeId());
        $form->addItem($item);

        $source = new ilRadioGroupInputGUI($this->getText('cmix_add_source'), 'source_type');
        $source->setRequired(true);

        $srcRemoteContent = new ilRadioOption($this->getText('cmix_add_source_url'), 'resource');
        $srcRemoteContent->setInfo($this->getText('cmix_add_source_url_info'));
        $source->addOption($srcRemoteContent);

        $srcUploadContent = new ilRadioOption($this->getText('cmix_add_source_local_dir'), 'upload');
        $srcUploadContent->setInfo($this->getText('cmix_add_source_local_dir_info'));
        $source->addOption($srcUploadContent);

        $srcUpload = new ilFileInputGUI($this->lng->txt("select_file"), "uploadfile");
        $srcUpload->setAllowDeletion(false);
        $srcUpload->setSuffixes(['zip', 'xml']);
        $srcUpload->setRequired(true);
        $srcUploadContent->addSubItem($srcUpload);

        if (ilUploadFiles::_getUploadDirectory()) {
            $srcServerContent = new ilRadioOption($this->getText('cmix_add_source_upload_dir'), 'server');
            $srcServerContent->setInfo($this->getText('cmix_add_source_upload_dir_info'));
            $source->addOption($srcServerContent);

            $options = ['' => $this->getText('cmix_add_source_upload_select')];

            foreach (ilUploadFiles::_getUploadFiles() as $file) {
                $options[$file] = $file;
            }

            $srcSelect = new ilSelectInputGUI($this->getText("select_file"), "serverfile");
            $srcSelect->setOptions($options);
            $srcServerContent->addSubItem($srcSelect);
        }
        /*
        $srcExternalApp = new ilRadioOption($this->getText('cmix_add_source_external_app'), 'external');
        $srcExternalApp->setInfo($this->getText('cmix_add_source_external_app_info'));
        $source->addOption($srcExternalApp);
        */
        $form->addItem($source);
        $form->addCommandButton("save", $this->getText($a_new_type . "_add"));
        $form->addCommandButton("cancel", $this->getText("cancel"));

        return $form;
    }

    public function afterSave(ilObject $newObject)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        /* @var ilObjXapiCmi5 $newObject */
        $form = $this->initCreateForm($newObject->getType());

        if ($form->checkInput()) {
            $newObject->setContentType($form->getInput('content_type'));

            $newObject->setLrsTypeId($form->getInput('lrs_type_id'));
            $newObject->initLrsType();

            $newObject->setPrivacyIdent($newObject->getLrsType()->getPrivacyIdent());
            $newObject->setPrivacyName($newObject->getLrsType()->getPrivacyName());

            switch ($form->getInput('source_type')) {
                case 'resource': // remote resource

                    $newObject->setTitle($form->getInput('title'));
                    $newObject->setSourceType(ilObjXapiCmi5::SRC_TYPE_REMOTE);
                    break;

                case 'upload': // upload from local client

                    try {
                        $uploadImporter = new ilXapiCmi5ContentUploadImporter($newObject);
                        $uploadImporter->importFormUpload($form->getItemByPostVar('uploadfile'));

                        $newObject->setSourceType(ilObjXapiCmi5::SRC_TYPE_LOCAL);
                    } catch (ilXapiCmi5InvalidUploadContentException $e) {
                        $form->getItemByPostVar('uploadfile')->setAlert($e->getMessage());
                        ilUtil::sendFailure('something went wrong!', true);
                        $DIC->ctrl()->redirectByClass(self::class, 'create');
                    }

                    break;

                case 'server': // from upload directory

                    if (!ilUploadFiles::_getUploadDirectory()) {
                        throw new ilXapiCmi5Exception('access denied!');
                    }

                    $serverFile = $form->getInput('serverfile');

                    if (!ilUploadFiles::_checkUploadFile($serverFile)) {
                        throw new ilXapiCmi5Exception($DIC->language()->txt('upload_error_file_not_found'));
                    }

                    $uploadImporter = new ilXapiCmi5ContentUploadImporter($newObject);

                    $uploadImporter->importServerFile(implode(DIRECTORY_SEPARATOR, [
                        ilUploadFiles::_getUploadDirectory(), $serverFile
                    ]));

                    $newObject->setSourceType(ilObjXapiCmi5::SRC_TYPE_LOCAL);

                    break;

                case 'external':

                    $newObject->setSourceType(ilObjXapiCmi5::SRC_TYPE_EXTERNAL);
                    $newObject->setBypassProxyEnabled(true);
                    break;
            }

            $newObject->save();

            $this->initMetadata($newObject);

            // $DIC->ctrl()->redirectByClass(ilXapiCmi5SettingsGUI::class);
            parent::afterSave($newObject);

        }

        throw new ilXapiCmi5Exception('invalid creation form submit!');
    }

    public function initMetadata(ilObjXapiCmi5 $object)
    {
        $metadata = new ilMD($object->getId(), $object->getId(), $object->getType());

        $generalMetadata = $metadata->getGeneral();

        if (!$generalMetadata) {
            $generalMetadata = $metadata->addGeneral();
        }

        $generalMetadata->setTitle($object->getTitle());
        $generalMetadata->save();

        $id = $generalMetadata->addIdentifier();
        $id->setCatalog('ILIAS');
        $id->setEntry('il__' . $object->getType() . '_' . $object->getId());
        $id->save();
    }

    protected function initHeaderAction($a_sub_type = null, $a_sub_id = null)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $return = parent::initHeaderAction($a_sub_type, $a_sub_id);

        if ($this->creation_mode) {
            return $return;
        }
/*
        $validator = new ilCertificateDownloadValidator();
        if ($validator->isCertificateDownloadable((int) $DIC->user()->getId(), (int) $this->object->getId())) {
            $certLink = $DIC->ctrl()->getLinkTargetByClass(
                [ilObjXapiCmi5GUI::class, ilXapiCmi5SettingsGUI::class],
                ilXapiCmi5SettingsGUI::CMD_DELIVER_CERTIFICATE
            );

            $DIC->language()->loadLanguageModule('certificate');

            $return->addCustomCommand($certLink, 'download_certificate');

            $return->addHeaderIcon(
                'cert_icon',
                ilUtil::getImagePath('icon_cert.svg'),
                $DIC->language()->txt('download_certificate'),
                null,
                null,
                $certLink
            );
        }
*/
        return $return;
    }

//    public static function _goto($a_target)
//    {
//        global $DIC; /* @var \ILIAS\DI\Container $DIC */
//        $err = $DIC['ilErr']; /* @var ilErrorHandling $err */
//        $ctrl = $DIC->ctrl();
//        $request = $DIC->http()->request();
//        $access = $DIC->access();
//        $lng = $DIC->language();
//
//        if (!is_array($a_target)) {
//            $targetParameters = explode('_', $a_target);
//            $id = (int) $targetParameters[0];
//        } else {
//            $id = $a_target[0];
//        }
//
//        if ($id <= 0) {
//            $err->raiseError($getText('msg_no_perm_read'), $err->FATAL);
//        }
//
//        if ($access->checkAccess('read', '', $id)) {
//            $ctrl->setTargetScript('ilias.php');
//            $ctrl->initBaseClass(ilRepositoryGUI::class);
//            $ctrl->setParameterByClass(ilObjXapiCmi5GUI::class, 'ref_id', $id);
//            if (isset($request->getQueryParams()['gotolp'])) {
//                $ctrl->setParameterByClass(ilObjXapiCmi5GUI::class, 'gotolp', 1);
//            }
//            $ctrl->redirectByClass([ilRepositoryGUI::class, ilObjXapiCmi5GUI::class]);
//        } elseif ($access->checkAccess('visible', '', $id)) {
//            ilObjectGUI::_gotoRepositoryNode($id, 'infoScreen');
//        } elseif ($access->checkAccess('read', '', ROOT_FOLDER_ID)) {
//            ilUtil::sendInfo(
//                sprintf(
//                    $DIC->language()->txt('msg_no_perm_read_item'),
//                    ilObject::_lookupTitle(ilObject::_lookupObjId($id))
//                ),
//                true
//            );
//
//            ilObjectGUI::_gotoRepositoryRoot();
//        }
//
//        $err->raiseError($DIC->language()->txt("msg_no_perm_read_lm"), $err->FATAL);
//    }

	/**
     * Perform command
     *
     * @access public
     */
    public function performCommand($cmd)
    {
        global $DIC, $ilErr, $ilTabs;

        if ($this->ctrl->getNextClass() == "ilmdeditorgui")
        {
            $md_gui = new ilMDEditorGUI((int) $this->obj_id, (int) $this->sub_id, $this->obj_type);
			$DIC->tabs()->activateTab(self::TAB_ID_METADATA);
            return $this->ctrl->forwardCommand($md_gui);
        }
        //$GLOBALS['DIC']->logger()->root()->log("nextClass: " . $this->ctrl->getNextClass());
        // die($cmd);
        switch ($cmd)
        {
        	case "edit":
            case "saveSettings":
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
                if ($this->cmixAccess->hasDeleteXapiDataAccess()) {
                    $this->statementsView($cmd);
                }
                else {
                    throw new ilXapiCmi5Exception('access denied!');
                }


                break;
            case "editLPSettings":
                $this->checkPermission("edit_learning_progress");
                $this->setSubTabs("learning_progress");

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
					if ($this->object->getLrsTypeId() == "") {
						$pl = new ilXapiCmi5Plugin();
						$ilErr->raiseError($this->txt('type_not_set'), $ilErr->MESSAGE);
					}
					else if ($this->object->getLrsType()->getAvailability() == ilXapiCmi5LrsType::AVAILABILITY_NONE)	{
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
    public function executeCommand()
    {
        global $DIC;

        // TODO: access checks (!)

        if (!$this->creation_mode) {
            $link = ilLink::_getLink($this->object->getRefId(), $this->object->getType());
            $navigationHistory = $DIC['ilNavigationHistory']; // @var ilNavigationHistory $navigationHistory
            $navigationHistory->addItem($this->object->getRefId(), $link, $this->object->getType());
            $this->trackObjectReadEvent();
        }

        $this->prepareOutput();
        $this->addHeaderAction();


        switch ($DIC->ctrl()->getNextClass()) {
            case strtolower(ilObjectCopyGUI::class):

                $gui = new ilObjectCopyGUI($this);
                $gui->setType($this->getType());
                $DIC->ctrl()->forwardCommand($gui);

                break;

            case strtolower(ilCommonActionDispatcherGUI::class):

                $gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
                $this->ctrl->forwardCommand($gui);

                break;

            case strtolower(ilLearningProgressGUI::class):

                $DIC->tabs()->activateTab(self::TAB_ID_LEARNING_PROGRESS);

                $gui = new ilLearningProgressGUI(
                    ilLearningProgressGUI::LP_CONTEXT_REPOSITORY,
                    $this->object->getRefId()
                );

                $DIC->ctrl()->forwardCommand($gui);

                break;

            case strtolower(ilObjectMetaDataGUI::class):

                $DIC->tabs()->activateTab(self::TAB_ID_METADATA);

                $gui = new ilObjectMetaDataGUI($this->object);
                $DIC->ctrl()->forwardCommand($gui);
                break;

            case strtolower(ilPermissionGUI::class):

                $DIC->tabs()->activateTab(self::TAB_ID_PERMISSIONS);

                $gui = new ilPermissionGUI($this);
                $DIC->ctrl()->forwardCommand($gui);

                break;

            case strtolower(ilXapiCmi5SettingsGUI::class):

                $DIC->tabs()->activateTab(self::TAB_ID_SETTINGS);

                $gui = new ilXapiCmi5SettingsGUI($this->object);
                $DIC->ctrl()->forwardCommand($gui);

                break;

            case strtolower(ilXapiCmi5StatementsGUI::class):

                $DIC->tabs()->activateTab(self::TAB_ID_STATEMENTS);

                $gui = new ilXapiCmi5StatementsGUI($this->object);
                $DIC->ctrl()->forwardCommand($gui);

                break;

            case strtolower(ilXapiCmi5ScoringGUI::class):

                $DIC->tabs()->activateTab(self::TAB_ID_SCORING);

                $gui = new ilXapiCmi5ScoringGUI($this->object);
                $DIC->ctrl()->forwardCommand($gui);

                break;

            case strtolower(ilXapiCmi5ExportGUI::class):

                $DIC->tabs()->activateTab(self::TAB_ID_EXPORT);

                $gui = new ilXapiCmi5ExportGUI($this);
                $DIC->ctrl()->forwardCommand($gui);

                break;

            case strtolower(ilXapiCmi5RegistrationGUI::class):

                $DIC->tabs()->activateTab(self::TAB_ID_INFO);

                $gui = new ilXapiCmi5RegistrationGUI($this->object);
                $DIC->ctrl()->forwardCommand($gui);

                break;

            case strtolower(ilXapiCmi5LaunchGUI::class):

                $gui = new ilXapiCmi5LaunchGUI($this->object);
                $DIC->ctrl()->forwardCommand($gui);

                break;

            default:

                $command = $DIC->ctrl()->getCmd(self::DEFAULT_CMD);
                $this->{$command}();
        }
    }
*/
/*
    protected function setTabs()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC

        $DIC->tabs()->addTab(
            self::TAB_ID_INFO,
            $DIC->language()->txt(self::TAB_ID_INFO),
            $DIC->ctrl()->getLinkTargetByClass(self::class)
        );

        if ($this->cmixAccess->hasWriteAccess()) {
            $DIC->tabs()->addTab(
                self::TAB_ID_SETTINGS,
                $DIC->language()->txt(self::TAB_ID_SETTINGS),
                $DIC->ctrl()->getLinkTargetByClass(ilXapiCmi5SettingsGUI::class)
            );
        }

        if ($this->cmixAccess->hasStatementsAccess()) {
            $DIC->tabs()->addTab(
                self::TAB_ID_STATEMENTS,
                $DIC->language()->txt(self::TAB_ID_STATEMENTS),
                $DIC->ctrl()->getLinkTargetByClass(ilXapiCmi5StatementsGUI::class)
            );
        }

        if ($this->cmixAccess->hasHighscoreAccess()) {
            $DIC->tabs()->addTab(
                self::TAB_ID_SCORING,
                $DIC->language()->txt(self::TAB_ID_SCORING),
                $DIC->ctrl()->getLinkTargetByClass(ilXapiCmi5ScoringGUI::class)
            );
        }

        if ($this->cmixAccess->hasLearningProgressAccess()) {
            $DIC->tabs()->addTab(
                self::TAB_ID_LEARNING_PROGRESS,
                $DIC->language()->txt(self::TAB_ID_LEARNING_PROGRESS),
                $DIC->ctrl()->getLinkTargetByClass(ilLearningProgressGUI::class)
            );
        }

        if ($this->cmixAccess->hasWriteAccess()) {
            $gui = new ilObjectMetaDataGUI($this->object);
            $link = $gui->getTab();

            if (strlen($link)) {
                $DIC->tabs()->addTab(
                    self::TAB_ID_METADATA,
                    $DIC->language()->txt('meta_data'),
                    $link
                );
            }
        }

        if ($this->cmixAccess->hasWriteAccess()) {
            $DIC->tabs()->addTab(
                self::TAB_ID_EXPORT,
                $DIC->language()->txt(self::TAB_ID_EXPORT),
                $DIC->ctrl()->getLinkTargetByClass(ilXapiCmi5ExportGUI::class)
            );
        }

        if ($this->cmixAccess->hasEditPermissionsAccess()) {
            $DIC->tabs()->addTab(
                self::TAB_ID_PERMISSIONS,
                $DIC->language()->txt(self::TAB_ID_PERMISSIONS),
                $DIC->ctrl()->getLinkTargetByClass(ilPermissionGUI::class, 'perm')
            );
        }

        if (defined('DEVMODE') && DEVMODE) {
            $DIC->tabs()->addTab(
                'debug',
                'DEBUG',
                $DIC->ctrl()->getLinkTarget($this, 'debug')
            );
        }
    }
    */
    /**
     * Set tabs
     */
    protected function setTabs()
    {
        global $ilTabs, $ilCtrl, $lng;

		if ($this->checkCreationMode())
		{
			return;
		}

        $type = new ilXapiCmi5LrsType($this->object->getLrsTypeId());

		// view tab
		// if ($this->object->getLrsType()->getLaunchType() == ilXapiCmi5LrsType::LAUNCH_TYPE_EMBED)
		// {
			$ilTabs->addTab("viewEmbed", $this->lng->txt("content"), $ilCtrl->getLinkTarget($this, "viewEmbed"));
		// }

        //  info screen tab
        $ilTabs->addTab("infoScreen", $this->lng->txt("info_short"), $ilCtrl->getLinkTarget($this, "infoScreen"));

        if ($this->object->isStatementsReportEnabled()) { // ToDo: see in core "hasStatementsAccess"
            $ilTabs->addTab(self::TAB_ID_STATEMENTS, $this->getText('tab_statements'), $ilCtrl->getLinkTarget($this,'statements'));
        }
        // add "edit" tab

        if ($this->checkPermissionBool("write"))
        {
            $ilTabs->addTab("edit", $this->lng->txt("settings"), $ilCtrl->getLinkTarget($this, "edit"));
            $ilTabs->addTab("export", $this->lng->txt("export"), $ilCtrl->getLinkTargetByClass("ilexportgui", ""));
            $ilTabs->addTab(self::TAB_ID_METADATA, $this->lng->txt("meta_data"), $ilCtrl->getLinkTargetByClass([ilObjPluginDispatchGUI::class, ilObjXapiCmi5GUI::class, ilMDEditorGUI::class], "listSection"));
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

    protected function debug()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $DIC->tabs()->activateTab('debug');

        $filter = new ilXapiCmi5StatementsReportFilter();
        $filter->setActivityId($this->object->getActivityId());

        $linkBuilder = new ilXapiCmi5HighscoreReportLinkBuilder(
            $this->object->getId(),
            $this->object->getLrsType()->getLrsEndpointStatementsAggregationLink(),
            $filter
        );

        $request = new ilXapiCmi5HighscoreReportRequest(
            $this->object->getLrsType()->getBasicAuth(),
            $linkBuilder
        );

        try {
            $report = $request->queryReport($this->object->getId(), $this->object->getRefId());

            $DIC->ui()->mainTemplate()->setContent(
                $report->getResponseDebug()
            );
        } catch (Exception $e) {
            ilUtil::sendFailure($e->getMessage());
        }
        //ilUtil::sendSuccess('Object ID: '.$this->object->getId());
        ilUtil::sendInfo($linkBuilder->getPipelineDebug());
        ilUtil::sendQuestion('<pre>' . print_r($report->getTableData(), 1) . '</pre>');
    }

    public function addLocatorItems()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $locator = $DIC['ilLocator']; /* @var ilLocatorGUI $locator */
        $locator->addItem(
            $this->object->getTitle(),
            $this->ctrl->getLinkTarget($this, self::DEFAULT_CMD),
            "",
            $_GET["ref_id"]
        );
    }

    protected function trackObjectReadEvent()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        ilChangeEvent::_recordReadEvent(
            $this->object->getType(),
            $this->object->getRefId(),
            $this->object->getId(),
            $DIC->user()->getId()
        );
    }



    /**
     * Set the sub tabs
     *
     * @param string	main tab identifier
     */
    function setSubTabs($a_tab)
    {
    	global $ilUser, $ilTabs, $ilCtrl, $lng;

    	switch ($a_tab)
    	{
            case "learning_progress":
                $lng->loadLanguageModule('trac');
				if ($this->checkPermissionBool("edit_learning_progress"))
				{
					$ilTabs->addSubTab("lp_settings", $this->txt('settings'), $ilCtrl->getLinkTargetByClass(array('ilObjXapiCmi5GUI'), 'editLPSettings'));
				}
                if ($this->object->getLPMode() > 0 && $this->checkPermissionBool("read_learning_progress"))
                {

                    include_once("Services/Tracking/classes/class.ilObjUserTracking.php");
                    if (ilObjUserTracking::_enabledUserRelatedData())
                    {
                        $ilTabs->addSubTab("trac_objects", $lng->txt('trac_objects'), $ilCtrl->getLinkTargetByClass(array('ilObjXapiCmi5GUI','ilLearningProgressGUI','ilLPListOfObjectsGUI')));
                    }
                    $ilTabs->addSubTab("trac_summary", $lng->txt('trac_summary'), $ilCtrl->getLinkTargetByClass(array('ilObjXapiCmi5GUI','ilLearningProgressGUI', 'ilLPListOfObjectsGUI'), 'showObjectSummary'));
                }
                break;
        }
    }

    /**
     * show info screen
     *
     * @access public
     */
    public function infoScreen()
    {
		global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $this->tabs_gui->activateTab('infoScreen');

        include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
        $info = new ilInfoScreenGUI($this);

        // $info->addSection($this->txt('instructions'));
        // $info->addProperty("", $this->object->getInstructions());

        $info->enablePrivateNotes();

        // add view button
        if ($this->object->getLrsType()->getAvailability() == ilXapiCmi5LrsType::AVAILABILITY_NONE)
        {
            ilUtil::sendFailure($this->lng->txt('xxcf_message_type_not_available'), false);
        } elseif ($this->object->getOnline())
        {
            if ($this->object->getLrsType()->getLaunchType() == ilXapiCmi5LrsType::LAUNCH_TYPE_LINK)
            {
                $info->addButton($this->lng->txt("view"), $DIC->ctrl()->getLinkTarget($this, "view"));
            } elseif ($this->object->getLrsType()->getLaunchType() == ilXapiCmi5LrsType::LAUNCH_TYPE_PAGE) // ever used??
             {
                $info->addButton($this->lng->txt("view"), $DIC->ctrl()->getLinkTarget($this, "viewPage"));
            }
        }
		$DIC->ctrl()->forwardCommand($info);

        /* Core
        $DIC->tabs()->activateTab(self::TAB_ID_INFO);

        $DIC->ctrl()->setCmd("showSummary");
        $DIC->ctrl()->setCmdClass("ilinfoscreengui");
        $this->infoScreenForward();
        */
    }

    public function infoScreenForward()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $ilErr = $DIC['ilErr']; /* @var ilErrorHandling $ilErr */

        if (!$this->checkPermissionBool("visible") && !$this->checkPermissionBool("read")) {
            $ilErr->raiseError($DIC->language()->txt("msg_no_perm_read"));
        }

        $this->handleAvailablityMessage();
        $this->initInfoScreenToolbar();

        $info = new ilInfoScreenGUI($this);

        $info->enablePrivateNotes();

        if ($this->checkPermissionBool("read")) {
            $info->enableNews();
        }

        $info->enableNewsEditing(false);

        if ($this->checkPermissionBool("write")) {
            $news_set = new ilSetting("news");
            $enable_internal_rss = $news_set->get("enable_rss_for_internal");

            if ($enable_internal_rss) {
                $info->setBlockProperty("news", "settings", true);
                $info->setBlockProperty("news", "public_notifications_option", true);
            }
        }

        if (DEVMODE) {
            // Development Info
            $info->addSection('DEVMODE Info');
            $info->addProperty('Local Object ID', $this->object->getId());
            $info->addProperty('Current User ID', $DIC->user()->getId());
        }

        // standard meta data
        $info->addMetaDataSections($this->object->getId(), 0, $this->object->getType());

        // Info about privacy
        if ($this->object->isSourceTypeExternal()) {
            $info->addSection($DIC->language()->txt("cmix_info_privacy_section"));
        } else {
            $info->addSection($DIC->language()->txt("cmix_info_privacy_section_launch"));
        }

        $info->addProperty($DIC->language()->txt('cmix_lrs_type'), $this->object->getLrsType()->getTitle());

        if ($this->object->isSourceTypeExternal()) {
            $cmixUser = new ilXapiCmi5User($this->object->getId(), $DIC->user()->getId(), $this->object->getPrivacyIdent());
            if ($cmixUser->getUsrIdent()) {
                $info->addProperty(
                    $DIC->language()->txt("conf_user_registered_mail"),
                    $cmixUser->getUsrIdent()
                );
            }
        } else {
            $info->addProperty(
                $DIC->language()->txt("conf_privacy_name"),
                $DIC->language()->txt('conf_privacy_name_' . self::getPrivacyNameString($this->object->getPrivacyName()))
            );

            $info->addProperty(
                $DIC->language()->txt("conf_privacy_ident"),
                $DIC->language()->txt('conf_privacy_ident_' . self::getPrivacyIdentString($this->object->getPrivacyIdent()))
            );
        }

        if ($this->object->getLrsType()->getExternalLrs()) {
            $info->addProperty(
                $DIC->language()->txt("cmix_info_external_lrs_label"),
                $DIC->language()->txt('cmix_info_external_lrs_info')
            );
        }

        if (strlen($this->object->getLrsType()->getPrivacyCommentDefault())) {
            $info->addProperty(
                $DIC->language()->txt("cmix_indication_to_user"),
                nl2br($this->object->getLrsType()->getPrivacyCommentDefault())
            );
        }

        // FINISHED INFO SCREEN, NOW FORWARD

        $this->ctrl->forwardCommand($info);
    }

    protected function initInfoScreenToolbar()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        if (!$this->object->getOfflineStatus() && $this->object->getLrsType()->isAvailable()) {
            // TODO : check if this is the correct query
            // p.e. switched to another privacyIdent before: user exists but not with the new privacyIdent
            // re_check for isSourceTypeExternal
            //$cmixUserExists = ilXapiCmi5User::exists($this->object->getId(), $DIC->user()->getId());

            if ($this->object->isSourceTypeExternal()) {
                $extCmiUserExists = ilXapiCmi5User::exists($this->object->getId(), $DIC->user()->getId());
                $registerButton = ilLinkButton::getInstance();

                if ($extCmiUserExists) {
                    $registerButton->setCaption('change_registration');
                } else {
                    $registerButton->setPrimary(true);
                    $registerButton->setCaption('create_registration');
                }

                $registerButton->setUrl($DIC->ctrl()->getLinkTargetByClass(
                    ilXapiCmi5RegistrationGUI::class
                ));
                $DIC->toolbar()->addButtonInstance($registerButton);
            } else {

                $launchButton = ilLinkButton::getInstance();
                $launchButton->setPrimary(true);
                $launchButton->setCaption('launch');

                if ($this->object->getLaunchMethod() == ilObjXapiCmi5::LAUNCH_METHOD_NEW_WIN) {
                    $launchButton->setTarget('_blank');
                }

                // $launchButton->setUrl($DIC->ctrl()->getLinkTargetByClass(
                    // ilXapiCmi5LaunchGUI::class
                // ));
                $DIC->toolbar()->addButtonInstance($launchButton);

            }

            /**
             * beware: ilXapiCmi5User::exists($this->object->getId(),$DIC->user()->getId());
             * this is not a valid query because if you switched privacyIdent mode before you will get
             * an existing user without launched data like proxySuccess
             */
            $cmiUserExists = ilXapiCmi5User::exists($this->object->getId(),$DIC->user()->getId(),$this->object->getPrivacyIdent());

            if ($cmiUserExists) {
                $cmixUser = new ilXapiCmi5User($this->object->getId(), $DIC->user()->getId(), $this->object->getPrivacyIdent());

                if ($this->isFetchXapiStatementsRequired($cmixUser)) {
                    $fetchButton = ilLinkButton::getInstance();
                    $fetchButton->setCaption('fetch_xapi_statements');

                    $fetchButton->setUrl($DIC->ctrl()->getLinkTarget(
                        $this,
                        self::CMD_FETCH_XAPI_STATEMENTS
                    ));

                    $DIC->toolbar()->addButtonInstance($fetchButton);

                    $this->sendLastFetchInfo($cmixUser);
                }
            }
        }
    }

    protected function handleAvailablityMessage()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        if ($this->object->getLrsType()->getAvailability() == ilXapiCmi5LrsType::AVAILABILITY_NONE) {
            ilUtil::sendFailure($DIC->language()->txt('cmix_lrstype_not_avail_msg'));
        }
    }

    protected function isFetchXapiStatementsRequired(ilXapiCmi5User $cmixUser)
    {
        global $DIC;
        if ($this->object->getLaunchMode() != ilObjXapiCmi5::LAUNCH_MODE_NORMAL) {
            return false;
        }

        if ($this->object->isBypassProxyEnabled()) {
            return true;
        }

        if (!$cmixUser->hasProxySuccess()) {
            return true;
        }

        return false;
    }

    protected function sendLastFetchInfo(ilXapiCmi5User $cmixUser)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        if (!$cmixUser->getFetchUntil()->get(IL_CAL_UNIX)) {
            $info = $DIC->language()->txt('xapi_statements_not_fetched_yet');
        } else {
            $info = $DIC->language()->txt('xapi_statements_last_fetch_date') . ' ' . ilDatePresentation::formatDate(
                $cmixUser->getFetchUntil()
            );
        }

        ilUtil::sendInfo($info);
    }

    protected function fetchXapiStatements()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $logger = ilLoggerFactory::getLogger($this->object->getType());

        if ($this->object->getLaunchMode() != ilObjXapiCmi5::LAUNCH_MODE_NORMAL) {
            throw new ilXapiCmi5Exception('access denied!');
        }

        $cmixUser = new ilXapiCmi5User($this->object->getId(), $DIC->user()->getId(), $this->object->getPrivacyIdent());

        $fetchedUntil = $cmixUser->getFetchUntil();
        $now = new ilXapiCmi5DateTime(time(), IL_CAL_UNIX);

        $report = $this->getXapiStatementsReport($fetchedUntil, $now);

        if ($report->hasStatements()) {
            $evaluation = new ilXapiCmi5StatementEvaluation($logger, $this->object);
            $evaluation->evaluateReport($report);

            //$logger->debug('update lp for object (' . $this->object->getId() . ')');
            //ilLPStatusWrapper::_updateStatus($this->object->getId(), $DIC->user()->getId());
        }

        $cmixUser->setFetchUntil($now);
        $cmixUser->save();

        ilUtil::sendSuccess($DIC->language()->txt('xapi_statements_fetched_successfully'), true);
        $DIC->ctrl()->redirect($this, self::CMD_INFO_SCREEN);
    }

    protected function getXapiStatementsReport(ilXapiCmi5DateTime $since, ilXapiCmi5DateTime $until)
    {
        $filter = $this->buildReportFilter($since, $until);

        $linkBuilder = new ilXapiCmi5StatementsReportLinkBuilder(
            $this->object->getId(),
            $this->object->getRefId(),
            $this->object->getLrsType()->getLrsEndpointStatementsAggregationLink(),
            $filter
        );

        $request = new ilXapiCmi5StatementsReportRequest(
            $this->object->getLrsType()->getBasicAuth(),
            $linkBuilder
        );

        return $request->queryReport($this->object->getId(), $this->object->getRefId());
    }

    protected function buildReportFilter(ilXapiCmi5DateTime $since, ilXapiCmi5DateTime $until)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $filter = new ilXapiCmi5StatementsReportFilter();

        $filter->setActor(new ilXapiCmi5User($this->object->getId(), $DIC->user()->getId(), $this->object->getPrivacyIdent()));
        $filter->setActivityId($this->object->getActivityId());

        $filter->setStartDate($since);
        $filter->setEndDate($until);

        $start = $filter->getStartDate()->get(IL_CAL_DATETIME);
        $end = $filter->getEndDate()->get(IL_CAL_DATETIME);
        ilLoggerFactory::getLogger($this->object->getType())->debug("use filter from ($start) until ($end)");

        return $filter;
    }

    public static function getPrivacyIdentString(int $ident)
    {
        switch ($ident) {
            case 0:
                return "il_uuid_user_id";
            case 1:
                return "il_uuid_ext_account";
            case 2:
                return "il_uuid_login";
            case 3:
                return "real_email";
            case 4:
                return "il_uuid_random";
        }
        return '';
    }

    public static function getPrivacyNameString(int $ident)
    {
        switch ($ident) {
            case 0:
                return "none";
            case 1:
                return "firstname";
            case 2:
                return "lastname";
            case 3:
                return "fullname";
        }
        return '';
    }

//AB HIER NUR PLUGIN

    /**
     * view the object (default command)
     *
     * @access public
     */
    function viewObject()
    {
        global $ilErr;

        $this->object->trackAccess();

        switch ($this->object->getLrsType()->getLaunchType())
        {
            // case ilXapiCmi5LrsType::LAUNCH_TYPE_LINK:
                // $this->object->trackAccess();
                // ilUtil::redirect($this->object->getLaunchLink());
                // break;

            // case ilXapiCmi5LrsType::LAUNCH_TYPE_PAGE:
                // $this->ctrl->redirect($this, "viewPage");
                // break;

            case ilXapiCmi5LrsType::LAUNCH_TYPE_EMBED:
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
        global $tpl, $ilCtrl;
        $this->object->trackAccess();
        $this->tabs_gui->activateTab('viewEmbed');
        $my_tpl = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/templates/default/tpl.view.html', true, true);
        $my_tpl->setVariable('LAUNCH_LINK',preg_replace('/amp\;/','',$ilCtrl->getLinkTarget($this, "launch")));
        if ($this->object->getLaunchMethod() == ilObjXapiCmi5::LAUNCH_METHOD_IFRAME) {
            $my_tpl->setVariable('OPEN_MODE_IFRAME', 1);
        } else {
            $my_tpl->setVariable('OPEN_MODE_IFRAME', 0);
            if ($this->object->getLaunchMethod() == ilObjXapiCmi5::LAUNCH_METHOD_OWN_WIN) {
                $my_tpl->setVariable('TARGET', '_top');
            }
            else {
                $my_tpl->setVariable('TARGET', '_blank');
            }
        }
        $my_tpl->setVariable('WIN_LAUNCH_WIDTH', '1000');
        $my_tpl->setVariable('WIN_LAUNCH_HEIGHT', '700');
        $my_tpl->setVariable('FRAME_LAUNCH_WIDTH', '1000');
        $my_tpl->setVariable('FRAME_LAUNCH_HEIGHT', '700');

		$tpl->setContent($my_tpl->get());
    }

    function launchObject()
    {
        require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5LaunchGUI.php');
        $gui = new ilXapiCmi5LaunchGUI($this->object);
        $gui->executeCommand();
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
    // function create()
    // {
		// global $ilErr;
		// if (ilXapiCmi5LrsType::getCountTypesForCreate() == 0) {
			// $pl = new ilXapiCmi5Plugin();
			// $ilErr->raiseError($pl->txt('no_type_available_for_create'), $ilErr->MESSAGE);
		// } else {
			// parent::create();
		// }
    // }

    /**
     * cancel creation of a new object
     *
     * @access	public
     */
    // function cancelCreate()
    // {
        // $this->ctrl->returnToParent($this);
    // }

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
            $this->tabs_gui->activateTab(self::TAB_ID_STATEMENTS);
            $this->tpl->setContent($gui->executeCommand());
        }
        else {
            $gui->executeCommand();
        }
    }

    /**
     * Edit object
     *
     * @access public
     */
    public function editObject()
    {
        $this->tabs_gui->activateTab('edit');
        require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5SettingsGUI.php');
        $gui = new ilXapiCmi5SettingsGUI($this);
        $this->tpl->setContent($gui->executeCommand());
    }

    /**
     * saveSettings object
     *
     * @access public
     */
    public function saveSettingsObject()
    {
        $this->tabs_gui->activateTab('edit');
        require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5SettingsGUI.php');
        $gui = new ilXapiCmi5SettingsGUI($this);
        $this->tpl->setContent($gui->executeCommand());
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
     * check a token for validity
     * 
     * @return boolean	check is ok
     */
    // function checkToken()
    // {
        // $obj = new ilObjXapiCmi5();
        // $value = $obj->checkToken();
        // echo $value;
    // }
	
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

//NUR PLUGIN
//    protected function listSectionObject() {
//        $md_gui = new ilMDEditorGUI((int) $this->obj_id, (int) $this->sub_id, $this->obj_type);
//        $ret = $this->ctrl->forwardCommand($md_gui);
//    }

//ÜBERNEHMEN FÜR CORE?

    const ACTIVITY_ID_VALIDATION_REGEXP = '/^'.
		'(http|https):\/\/'.		// protocol
		'[a-z0-9\-\.]+'.			// hostpart
		'(\/[a-z0-9_\-\.]+)+'.		// pathpart
		'$/i';
    //wird hier verwendet:
    /*
    $item = new ilTextInputGUI($this->txt('activity_id'), 'activity_id');
    $item->setSize(40);
    $item->setMaxLength(128);
    $item->setValidationRegexp(self::ACTIVITY_ID_VALIDATION_REGEXP);
    $item->setValidationFailureMessage($this->txt('activity_id_validation_failure'));
    $item->setRequired(true);
    */


    public function getText($txt)
    {
        if (self::LANGUAGE_MODULE == 'cmix') {
            return $this->lng->txt($txt);
        } else {
            return $this->lng->txt(self::LANGUAGE_MODULE."_".$txt);
        }
    }
}
