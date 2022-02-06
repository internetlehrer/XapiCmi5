<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilXapiCmi5SettingsGUI
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 */
require_once __DIR__.'/class.ilXapiCmi5User.php';
require_once __DIR__.'/class.ilObjXapiCmi5.php';
require_once('./Services/Repository/classes/class.ilObjectPlugin.php');

class ilXapiCmi5SettingsGUI
{
    const LANGUAGE_MODULE = 'rep_robj_xxcf'; //plugin, sonst cmix
    const CMD_SHOW = 'view';
    const CMD_DELIVER_CERTIFICATE = 'deliverCertificate';
    
    const CMD_SAVE = 'saveSettings';
    
    const DEFAULT_CMD = self::CMD_SHOW;
    
    const SUBTAB_ID_SETTINGS = 'settings';
    const SUBTAB_ID_CERTIFICATE = 'certificate';
    
    /**
     * @var ilObjXapiCmi5
     */
    protected $object;
    
    protected $gui;

    protected $lng;
    
    /**
     * @param ilObjXapiCmi5 $object
     */
    public function __construct(ilObjXapiCmi5GUI $gui)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $this->gui = $gui;
        $this->object = $gui->object;
        $DIC->language()->loadLanguageModule(self::LANGUAGE_MODULE);
        $this->lng = $DIC->language();
    }
    
    public function initSubtabs()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        
        // $DIC->tabs()->addSubTab(
            // self::SUBTAB_ID_SETTINGS,
            // $this->getText(self::SUBTAB_ID_SETTINGS),
            // $DIC->ctrl()->getLinkTarget($this, self::CMD_SHOW)
        // );

        // $validator = new ilCertificateActiveValidator();

        // if ($validator->validate()) {
            // $DIC->tabs()->addSubTab(
                // self::SUBTAB_ID_CERTIFICATE,
                // $this->getText(self::SUBTAB_ID_CERTIFICATE),
                // $DIC->ctrl()->getLinkTargetByClass(ilCertificateGUI::class, 'certificateEditor')
            // );
        // }
    }
    
    public function executeCommand()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $this->initSubtabs();
        $cmd = $DIC->ctrl()->getCmd('show') . 'Cmd';

        if ($cmd === "editCmd") {
            return $this->showCmd();
        }
        elseif ($cmd === "saveSettingsCmd") {
            return $this->saveCmd();
        }
        // else { // ToDo
            // return $this->{$cmd}();
        // }
    }
    
    protected function saveCmd()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $form = $this->buildForm();
        
        if ($form->checkInput()) {
            $this->saveSettings($form);
            
            ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
            // $DIC->ctrl()->redirect($this->gui, self::CMD_SHOW);
        }
        
        return $this->showCmd($form);
    }
    
    protected function showCmd(ilPropertyFormGUI $form = null)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $DIC->tabs()->activateSubTab(self::SUBTAB_ID_SETTINGS);
        
        $form = $this->buildForm();
        return $form->getHTML();
        // $DIC->ui()->mainTemplate()->setContent($form->getHTML());
    }
    
    protected function buildForm()
    {
        global $DIC;
        /* @var \ILIAS\DI\Container $DIC */

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        // $form->setFormAction($DIC->ctrl()->getFormAction($this));
        $form->setFormAction($DIC->ctrl()->getFormAction($this->gui));

        $ne = new ilNonEditableValueGUI($this->getText('type'), "");
        $ne->setValue($this->getText('type_'.$this->object->getContentType()));
        $form->addItem($ne);

        $ne = new ilNonEditableValueGUI($this->getText('cmix_lrs_type'), "");
        $ne->setValue($this->object->getLrsType()->getTitle());
        $form->addItem($ne);
        
        $item = new ilTextInputGUI($this->lng->txt('title'), 'title');
        $item->setSize(40);
        $item->setMaxLength(128);
        $item->setRequired(true);
        $item->setInfo($this->getText('title_info'));
        $item->setValue($this->object->getTitle());
        $form->addItem($item);
        
        $item = new ilTextAreaInputGUI($this->lng->txt('description'), 'description');
        $item->setInfo($this->getText('description_info'));
        $item->setRows(2);
        $item->setCols(80);
        $item->setValue($this->object->getDescription());
        $form->addItem($item);
        
        $item = new ilTextInputGUI($this->getText('activity_id'), 'activity_id');
        $item->setRequired(true);
        $item->setSize(40);
        $item->setMaxLength(128);
        // $item->setRequired(true);
        $item->setInfo($this->getText('activity_id_info'));
        $item->setValue($this->object->getActivityId());
        $form->addItem($item);
        
        $item = new ilCheckboxInputGUI($this->lng->txt('online'), 'online');
        $item->setInfo($this->getText("online_info"));
        $item->setValue("1");
   		if ($this->object->getAvailabilityType() == ilObjXapiCmi5::ACTIVATION_UNLIMITED)
		{
			$item->setChecked(true);
		}
        // if (!$this->object->getOfflineStatus()) {
            // $item->setChecked(true);
        // }
        $form->addItem($item);
        
        if (!$this->object->isSourceTypeExternal()) {
            $item = new ilFormSectionHeaderGUI();
            $item->setTitle($this->getText("launch_options"));
            $form->addItem($item);
            
            if ($this->object->isSourceTypeRemote()) {
                $item = new ilTextInputGUI($this->getText('launch_url'), 'launch_url');
                $item->setSize(40);
                $item->setMaxLength(128);
                $item->setRequired(true);
                $item->setInfo($this->getText('launch_url_info'));
                $item->setValue($this->object->getLaunchUrl());
                $form->addItem($item);
            }
            
            if ($this->object->getContentType() != ilObjXapiCmi5::CONT_TYPE_CMI5) {
                $item = new ilCheckboxInputGUI($this->getText('use_fetch'), 'use_fetch');
                $item->setInfo($this->getText("use_fetch_info"));
                $item->setValue("1");
                
                if ($this->object->isAuthFetchUrlEnabled()) {
                    $item->setChecked(true);
                }
                $form->addItem($item);
            }
            
            $display = new ilRadioGroupInputGUI($this->getText('launch_options'), 'display');
            $display->setRequired(true);
            $display->setValue($this->object->getLaunchMethod());
            $optOwnWindow = new ilRadioOption($this->getText('conf_own_window'), ilObjXapiCmi5::LAUNCH_METHOD_OWN_WIN);
            $optOwnWindow->setInfo($this->getText('conf_own_window_info'));
            $display->addOption($optOwnWindow);
            $optAnyWindow = new ilRadioOption($this->getText('conf_new_window'), ilObjXapiCmi5::LAUNCH_METHOD_NEW_WIN);
            $optAnyWindow->setInfo($this->getText('conf_new_window_info'));
            $display->addOption($optAnyWindow);
            //plugin
            $optIframe = new ilRadioOption($this->getText('conf_iframe'), ilObjXapiCmi5::LAUNCH_METHOD_IFRAME);
            $optIframe->setInfo($this->getText('conf_iframe_info'));
            $display->addOption($optIframe);
            $form->addItem($display);
            
            $launchMode = new ilRadioGroupInputGUI($this->getText('conf_launch_mode'), 'launch_mode');
            $launchMode->setRequired(true);
            $launchMode->setValue($this->object->getLaunchMode());
            $optNormal = new ilRadioOption($this->getText('conf_launch_mode_normal'), ilObjXapiCmi5::LAUNCH_MODE_NORMAL);
            $optNormal->setInfo($this->getText('conf_launch_mode_normal_info'));
            $launchMode->addOption($optNormal);
            $optBrowse = new ilRadioOption($this->getText('conf_launch_mode_browse'), ilObjXapiCmi5::LAUNCH_MODE_BROWSE);
            $optBrowse->setInfo($this->getText('conf_launch_mode_browse_info'));
            $launchMode->addOption($optBrowse);
            $optReview = new ilRadioOption($this->getText('conf_launch_mode_review'), ilObjXapiCmi5::LAUNCH_MODE_REVIEW);
            $optReview->setInfo($this->getText('conf_launch_mode_review_info'));
            $launchMode->addOption($optReview);
            $form->addItem($launchMode);
        }
            
        $lpDeterioration = new ilCheckboxInputGUI($this->getText('conf_keep_lp'), 'avoid_lp_deterioration');
        $lpDeterioration->setInfo($this->getText('conf_keep_lp_info'));
        if ($this->object->isKeepLpStatusEnabled()) {
            $lpDeterioration->setChecked(true);
        }
        if (!$this->object->isSourceTypeExternal()) {
            $optNormal->addSubItem($lpDeterioration);
        } else {
            $form->addItem($lpDeterioration);
        }

        if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) {
            $switchMode = new ilCheckboxInputGUI($this->getText('conf_switch_to_review'), 'switch_to_review');
            $switchMode->setInfo($this->getText("conf_switch_to_review_info"));
            if ($this->object->isSwitchToReviewEnabled()) {
                $switchMode->setChecked(true);
            }
            $optNormal->addSubItem($switchMode);
            
            $masteryScore = new ilNumberInputGUI($this->getText('conf_mastery_score'), 'mastery_score');
            $masteryScore->setInfo($this->getText('conf_mastery_score_info'));
            $masteryScore->setSuffix('%');
            $masteryScore->allowDecimals(true);
            $masteryScore->setDecimals(2);
            $masteryScore->setMinvalueShouldBeGreater(false);
            $masteryScore->setMinValue(0);
            $masteryScore->setMaxvalueShouldBeLess(false);
            $masteryScore->setMaxValue(100);
            $masteryScore->setSize(4);
            if (empty($this->object->getMasteryScore())) {
                $this->object->setMasteryScorePercent(ilObjXapiCmi5::LMS_MASTERY_SCORE);
            }
            $masteryScore->setValue($this->object->getMasteryScorePercent());
            $optNormal->addSubItem($masteryScore);
        }
        
        if (!$this->object->isSourceTypeExternal()) {
            // Only Plugin : proxy is always enabled
            /*
            if ($this->object->getContentType() != ilObjXapiCmi5::CONT_TYPE_CMI5) {
                $sectionHeader = new ilFormSectionHeaderGUI();
                $sectionHeader->setTitle($this->getText('sect_learning_progress_options'));
                $form->addItem($sectionHeader);
                $bypassProxy = new ilRadioGroupInputGUI($this->getText('conf_bypass_proxy'), 'bypass_proxy');
                $bypassProxy->setInfo($this->getText('conf_bypass_proxy_info'));
                $bypassProxy->setValue($this->object->isBypassProxyEnabled());
                $opt1 = new ilRadioOption($this->getText('conf_bypass_proxy_disabled'), 0);
                $bypassProxy->addOption($opt1);
                $opt2 = new ilRadioOption($this->getText('conf_bypass_proxy_enabled'), 1);
                $bypassProxy->addOption($opt2);
                $form->addItem($bypassProxy);
                if ($this->object->getLrsType()->isBypassProxyEnabled()) {
                    $bypassProxy->setDisabled(true);
                }
            }
            */
            $item = new ilFormSectionHeaderGUI();
            $item->setTitle($this->getText("privacy_options"));
            $form->addItem($item);
            
            $userIdent = new ilRadioGroupInputGUI($this->getText('conf_privacy_ident'), 'privacy_ident');
            $op = new ilRadioOption(
                $this->getText('conf_privacy_ident_il_uuid_user_id'),
                ilXapiCmi5LrsType::PRIVACY_IDENT_IL_UUID_USER_ID
            );
            $op->setInfo($this->getText('conf_privacy_ident_il_uuid_user_id_info'));
            $userIdent->addOption($op);
            $op = new ilRadioOption(
                $this->getText('conf_privacy_ident_il_uuid_login'),
                ilXapiCmi5LrsType::PRIVACY_IDENT_IL_UUID_LOGIN
            );
            $op->setInfo($this->getText('conf_privacy_ident_il_uuid_login_info'));
            $userIdent->addOption($op);
            $op = new ilRadioOption(
                $this->getText('conf_privacy_ident_il_uuid_ext_account'),
                ilXapiCmi5LrsType::PRIVACY_IDENT_IL_UUID_EXT_ACCOUNT
            );
            $op->setInfo($this->getText('conf_privacy_ident_il_uuid_ext_account_info'));
            $userIdent->addOption($op);
            $op = new ilRadioOption(
                $this->getText('conf_privacy_ident_il_uuid_random'),
                ilXapiCmi5LrsType::PRIVACY_IDENT_IL_UUID_RANDOM
            );
            $op->setInfo($this->getText('conf_privacy_ident_il_uuid_random_info'));
            $userIdent->addOption($op);
            $op = new ilRadioOption(
                $this->getText('conf_privacy_ident_real_email'),
                ilXapiCmi5LrsType::PRIVACY_IDENT_REAL_EMAIL
            );
            $op->setInfo($this->getText('conf_privacy_ident_real_email_info'));
            $userIdent->addOption($op);
            $userIdent->setValue($this->object->getPrivacyIdent());
            $userIdent->setInfo(
                $this->getText('conf_privacy_ident_info') . ' ' . ilXapiCmi5User::getIliasUuid()
            );
            $userIdent->setRequired(false);
            $form->addItem($userIdent);
            
            $userName = new ilRadioGroupInputGUI($this->getText('conf_privacy_name'), 'privacy_name');
            $op = new ilRadioOption($this->getText('conf_privacy_name_none'), ilXapiCmi5LrsType::PRIVACY_NAME_NONE);
            $op->setInfo($this->getText('conf_privacy_name_none_info'));
            $userName->addOption($op);
            $op = new ilRadioOption($this->getText('conf_privacy_name_firstname'), ilXapiCmi5LrsType::PRIVACY_NAME_FIRSTNAME);
            $op->setInfo($this->getText('conf_privacy_name_firstname_info'));
            $userName->addOption($op);
            $op = new ilRadioOption($this->getText('conf_privacy_name_lastname'), ilXapiCmi5LrsType::PRIVACY_NAME_LASTNAME);
            $op->setInfo($this->getText('conf_privacy_name_lastname_info'));
            $userName->addOption($op);
            $op = new ilRadioOption($this->getText('conf_privacy_name_fullname'), ilXapiCmi5LrsType::PRIVACY_NAME_FULLNAME);
            $op->setInfo($this->getText('conf_privacy_name_fullname_info'));
            $userName->addOption($op);
            $userName->setValue($this->object->getPrivacyName());
            $userName->setInfo($this->getText('conf_privacy_name_info'));
            $userName->setRequired(false);
            $form->addItem($userName);

            if ($this->object->getLrsType()->getForcePrivacySettings()) {
                $userIdent->setDisabled(true);
                $userName->setDisabled(true);
            }

            $item = new ilCheckboxInputGUI($this->getText('only_moveon_label'), 'only_moveon');
            $item->setInfo($this->getText('only_moveon_info'));
            $item->setChecked($this->object->getOnlyMoveon());

            $subitem = new ilCheckboxInputGUI($this->getText('achieved_label'), 'achieved');
            $subitem->setInfo($this->getText('achieved_info'));
            $subitem->setChecked($this->object->getAchieved());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
            $item->addSubItem($subitem);

            $subitem = new ilCheckboxInputGUI($this->getText('answered_label'), 'answered');
            $subitem->setInfo($this->getText('answered_info'));
            $subitem->setChecked($this->object->getAnswered());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
            $item->addSubItem($subitem);

            $subitem = new ilCheckboxInputGUI($this->getText('completed_label'), 'completed');
            $subitem->setInfo($this->getText('completed_info'));
            $subitem->setChecked($this->object->getCompleted());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
            $item->addSubItem($subitem);

            $subitem = new ilCheckboxInputGUI($this->getText('failed_label'), 'failed');
            $subitem->setInfo($this->getText('failed_info'));
            $subitem->setChecked($this->object->getFailed());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
            $item->addSubItem($subitem);

            $subitem = new ilCheckboxInputGUI($this->getText('initialized_label'), 'initialized');
            $subitem->setInfo($this->getText('initialized_info'));
            $subitem->setChecked($this->object->getInitialized());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
            $item->addSubItem($subitem);

            $subitem = new ilCheckboxInputGUI($this->getText('passed_label'), 'passed');
            $subitem->setInfo($this->getText('passed_info'));
            $subitem->setChecked($this->object->getPassed());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
            $item->addSubItem($subitem);

            $subitem = new ilCheckboxInputGUI($this->getText('progressed_label'), 'progressed');
            $subitem->setInfo($this->getText('progressed_info'));
            $subitem->setChecked($this->object->getProgressed());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
            $item->addSubItem($subitem);
            if ($this->object->getContentType() != ilObjXapiCmi5::CONT_TYPE_CMI5) {
                $subitem = new ilCheckboxInputGUI($this->getText('satisfied_label'), 'satisfied');
                $subitem->setInfo($this->getText('satisfied_info'));
                $subitem->setChecked($this->object->getSatisfied());
                if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
                $item->addSubItem($subitem);

                $subitem = new ilCheckboxInputGUI($this->getText('terminated_label'), 'terminated');
                $subitem->setInfo($this->getText('terminated_info'));
                $subitem->setChecked($this->object->getTerminated());
                if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
                $item->addSubItem($subitem);
            }
            if ($this->object->getLrsType()->getForcePrivacySettings()) $item->setDisabled(true);
            $form->addItem($item);

            $item = new ilCheckboxInputGUI($this->getText('hide_data_label'), 'hide_data');
            $item->setInfo($this->getText('hide_data_info'));
            $item->setChecked($this->object->getHideData());

            $subitem = new ilCheckboxInputGUI($this->getText('timestamp_label'), 'timestamp');
            $subitem->setInfo($this->getText('timestamp_info'));
            $subitem->setChecked($this->object->getTimestamp());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
            $item->addSubItem($subitem);

            $subitem = new ilCheckboxInputGUI($this->getText('duration_label'), 'duration');
            $subitem->setInfo($this->getText('duration_info'));
            $subitem->setChecked($this->object->getDuration());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $subitem->setDisabled(true);
            $item->addSubItem($subitem);

            if ($this->object->getLrsType()->getForcePrivacySettings()) $item->setDisabled(true);
            $form->addItem($item);

            $item = new ilCheckboxInputGUI($this->getText('no_substatements_label'), 'no_substatements');
            $item->setInfo($this->getText('no_substatements_info'));
            $item->setChecked($this->object->getNoSubstatements());
            if ($this->object->getLrsType()->getForcePrivacySettings()) $item->setDisabled(true);
            $form->addItem($item);

        }
        
        $item = new ilFormSectionHeaderGUI();
        $item->setTitle($this->getText("log_options"));
        $form->addItem($item);
        
        $item = new ilCheckboxInputGUI($this->getText('show_debug'), 'show_debug');
        $item->setInfo($this->getText("show_debug_info"));
        $item->setValue("1");
        if ($this->object->isStatementsReportEnabled()) {
            $item->setChecked(true);
        }
        $form->addItem($item);
        
        /*
        $highscore = new ilCheckboxInputGUI($this->getText("highscore_enabled"), "highscore_enabled");
        $highscore->setValue(1);
        $highscore->setChecked($this->object->getHighscoreEnabled());
        $highscore->setInfo($this->getText("highscore_description"));
        $form->addItem($highscore);
        $highscore_tables = new ilRadioGroupInputGUI($this->getText('highscore_mode'), 'highscore_mode');
        $highscore_tables->setRequired(true);
        $highscore_tables->setValue($this->object->getHighscoreMode());
        $highscore_table_own = new ilRadioOption($this->getText('highscore_own_table'), ilObjXapiCmi5::HIGHSCORE_SHOW_OWN_TABLE);
        $highscore_table_own->setInfo($this->getText('highscore_own_table_description'));
        $highscore_tables->addOption($highscore_table_own);
        $highscore_table_other = new ilRadioOption($this->getText('highscore_top_table'), ilObjXapiCmi5::HIGHSCORE_SHOW_TOP_TABLE);
        $highscore_table_other->setInfo($this->getText('highscore_top_table_description'));
        $highscore_tables->addOption($highscore_table_other);
        $highscore_table_other = new ilRadioOption($this->getText('highscore_all_tables'), ilObjXapiCmi5::HIGHSCORE_SHOW_ALL_TABLES);
        $highscore_table_other->setInfo($this->getText('highscore_all_tables_description'));
        $highscore_tables->addOption($highscore_table_other);
        $highscore->addSubItem($highscore_tables);
        $highscore_top_num = new ilNumberInputGUI($this->getText("highscore_top_num"), "highscore_top_num");
        $highscore_top_num->setSize(4);
        $highscore_top_num->setRequired(true);
        $highscore_top_num->setMinValue(1);
        $highscore_top_num->setSuffix($this->getText("highscore_top_num_unit"));
        $highscore_top_num->setValue($this->object->getHighscoreTopNum(null));
        $highscore_top_num->setInfo($this->getText("highscore_top_num_description"));
        $highscore->addSubItem($highscore_top_num);
        $highscore_achieved_ts = new ilCheckboxInputGUI($this->getText("highscore_achieved_ts"), "highscore_achieved_ts");
        $highscore_achieved_ts->setValue(1);
        $highscore_achieved_ts->setChecked($this->object->getHighscoreAchievedTS());
        $highscore_achieved_ts->setInfo($this->getText("highscore_achieved_ts_description"));
        $highscore->addSubItem($highscore_achieved_ts);
        $highscore_percentage = new ilCheckboxInputGUI($this->getText("highscore_percentage"), "highscore_percentage");
        $highscore_percentage->setValue(1);
        $highscore_percentage->setChecked($this->object->getHighscorePercentage());
        $highscore_percentage->setInfo($this->getText("highscore_percentage_description"));
        $highscore->addSubItem($highscore_percentage);
        $highscore_wtime = new ilCheckboxInputGUI($this->getText("highscore_wtime"), "highscore_wtime");
        $highscore_wtime->setValue(1);
        $highscore_wtime->setChecked($this->object->getHighscoreWTime());
        $highscore_wtime->setInfo($this->getText("highscore_wtime_description"));
        $highscore->addSubItem($highscore_wtime);
        */
        
        $form->setTitle($this->getText('settings'));
        $form->addCommandButton(self::CMD_SAVE, $this->lng->txt("save"));
        $form->addCommandButton(self::CMD_SHOW, $this->lng->txt("cancel"));
        
        return $form;
    }
    
    protected function saveSettings(ilPropertyFormGUI $form)
    {
        $this->object->setTitle($form->getInput('title'));
        $this->object->setDescription($form->getInput('description'));
        
        $this->object->setActivityId($form->getInput('activity_id'));
        // $this->object->setOfflineStatus(!(bool) $form->getInput('online'));
        $this->object->setAvailabilityType($form->getInput('online') ? ilObjXapiCmi5::ACTIVATION_UNLIMITED : ilObjXapiCmi5::ACTIVATION_OFFLINE);
        
        if (!$this->object->isSourceTypeExternal()) {
            $this->object->setLaunchMethod($form->getInput('display'));
            
            $this->object->setLaunchMode($form->getInput('launch_mode'));
            
            if ($this->object->getLaunchMode() == ilObjXapiCmi5::LAUNCH_MODE_NORMAL) {
                if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) {
                    $this->object->setMasteryScorePercent($form->getInput('mastery_score'));
                }
                $this->object->setKeepLpStatusEnabled((bool) $form->getInput('avoid_lp_deterioration'));
                $this->object->setSwitchToReviewEnabled((bool) $form->getInput('switch_to_review'));
            }
            else {
                $this->object->setKeepLpStatusEnabled(true);
                $this->object->setSwitchToReviewEnabled(false);
            }

            if ($this->object->isSourceTypeRemote()) {
                $this->object->setLaunchUrl($form->getInput('launch_url'));
            }
            
            if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) {
                $this->object->setAuthFetchUrlEnabled(true);
            } else {
                $this->object->setAuthFetchUrlEnabled((bool) $form->getInput('use_fetch'));
            }

            if (!$this->object->getLrsType()->isBypassProxyEnabled()) {
                if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) {
                    $this->object->setBypassProxyEnabled(false);
                }
                else {
                    $this->object->setBypassProxyEnabled((bool) $form->getInput('bypass_proxy'));
                }
            }
            
            if (!$this->object->getLrsType()->getForcePrivacySettings()) {
                $this->object->setPrivacyIdent($form->getInput('privacy_ident'));
                $this->object->setPrivacyName($form->getInput('privacy_name'));
                $this->object->setOnlyMoveon((bool)$form->getInput("only_moveon"));
                $this->object->setAchieved((bool)$form->getInput("achieved"));
                $this->object->setAnswered((bool)$form->getInput("answered"));
                $this->object->setCompleted((bool)$form->getInput("completed"));
                $this->object->setFailed((bool)$form->getInput("failed"));
                $this->object->setInitialized((bool)$form->getInput("initialized"));
                $this->object->setPassed((bool)$form->getInput("passed"));
                $this->object->setProgressed((bool)$form->getInput("progressed"));
                if ($this->object->getContentType() == ilObjXapiCmi5::CONT_TYPE_CMI5) {
                    $this->object->setSatisfied(true);
                    $this->object->setTerminated(true);
                } else {
                    $this->object->setSatisfied((bool)$form->getInput("satisfied"));
                    $this->object->setTerminated((bool)$form->getInput("terminated"));
                }
                $this->object->setHideData((bool)$form->getInput("hide_data"));
                $this->object->setTimestamp((bool)$form->getInput("timestamp"));
                $this->object->setDuration((bool)$form->getInput("duration"));
                $this->object->setNoSubstatements((bool)$form->getInput("no_substatements"));
            }
        } else { //SourceTypeExternal
            $this->object->setBypassProxyEnabled(true);
            $this->object->setKeepLpStatusEnabled((bool) $form->getInput('avoid_lp_deterioration'));
        }
        
        $this->object->setStatementsReportEnabled((bool) $form->getInput('show_debug'));
        
        $this->object->setHighscoreEnabled(false);
        /*
        $this->object->setHighscoreEnabled((bool) $form->getInput('highscore_enabled'));
        if ($this->object->getHighscoreEnabled()) {
            // highscore settings
            $this->object->setHighscoreEnabled((bool) $form->getInput('highscore_enabled'));
            $this->object->setHighscoreAchievedTS((bool) $form->getInput('highscore_achieved_ts'));
            $this->object->setHighscorePercentage((bool) $form->getInput('highscore_percentage'));
            $this->object->setHighscoreWTime((bool) $form->getInput('highscore_wtime'));
            $this->object->setHighscoreMode((int) $form->getInput('highscore_mode'));
            $this->object->setHighscoreTopNum((int) $form->getInput('highscore_top_num'));
        }
        */
        $this->object->update();
    }
    
    // protected function deliverCertificateCmd()
    // {
        // global $DIC; /* @var \ILIAS\DI\Container $DIC */

        // $validator = new ilCertificateDownloadValidator();

        // if (!$validator->isCertificateDownloadable((int) $DIC->user()->getId(), (int) $this->object->getId())) {
            // ilUtil::sendFailure($this->getText("permission_denied"), true);
            // $DIC->ctrl()->redirectByClass(ilObjXapiCmi5GUI::class, ilObjXapiCmi5GUI::CMD_INFO_SCREEN);
        // }

        // $repository = new ilUserCertificateRepository();

        // $certLogger = $DIC->logger()->cert();
        // $pdfGenerator = new ilPdfGenerator($repository, $certLogger);

        // $pdfAction = new ilCertificatePdfAction(
            // $certLogger,
            // $pdfGenerator,
            // new ilCertificateUtilHelper(),
            // $this->getText('error_creating_certificate_pdf')
        // );

        // $pdfAction->downloadPdf((int) $DIC->user()->getId(), (int) $this->object->getId());
    // }
    protected function getText($txt)
    {
        if (self::LANGUAGE_MODULE == 'cmix') {
            return $this->lng->txt($txt);
        } else {
            return $this->lng->txt(self::LANGUAGE_MODULE."_".$txt);
        }
    }
}
