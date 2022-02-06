<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

require_once("./Services/Export/classes/class.ilXmlImporter.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5Plugin.php");

/**
 * Class ilXapiCmi5Importer
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 */
class ilXapiCmi5Importer extends ilXmlImporter {

    /** @var array */
    private $_moduleProperties = [];

    /** @var array */
    public $manifest = [];

    /** @var ilXapiCmi5DataSet */
    private $_dataset;

    /** @var ilObjXapiCmi5 */
    private $_cmixObj;

    /** @var int */
    private $_newId = null;

    /** @var string */
    private $_entity;

    /** @var int */
    private $_import_objId;

    /** @var string */
    private $_import_dirname;

    /** @var ilImportMapping */
    private $_mapping;

    /** @var boolean */
    private $_hasContent = false;

    /** @var string|null */
    private $_relWebDir = 'lm_data/lm_';

    /** @var string */
    private $_relImportDir = '';

    /** @var bool */
    private $_isSingleImport = false;

    /**
     * ilXapiCmi5Importer constructor.
     */
    public function __construct()
    {
        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5DataSet.php");
        $this->_dataset = new ilXapiCmi5DataSet();
        $this->_dataset->_cmixSettingsProperties['Title'] = '';
        $this->_dataset->_cmixSettingsProperties['Description'] = '';
        //todo: at the moment restricted to one module in xml file, extend?
    }

    /**
     * Init the object creation from import
     * @param string          $a_entity
     * @param string          $a_id
     * @param string          $a_xml
     * @param ilImportMapping $a_mapping
     * @return int
     * @throws \ILIAS\Filesystem\Exception\FileNotFoundException
     * @throws \ILIAS\Filesystem\Exception\IOException
     */
    public function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping) : int
    {
        global $DIC;
        /** @var \ILIAS\DI\Container $DIC */
        $this->_entity = $a_entity;
        $this->_import_objId = $a_id;
        $this->_import_dirname = $a_xml;
        $this->_mapping = $a_mapping;

        if (false === ($this->_newId = $a_mapping->getMapping('Services/Container', 'objs', $this->_import_objId))) {
            $this->prepareSingleObject();
            $this->getImportDirectorySingle();
            $this->_isSingleImport = true;
        } else {
            $this->prepareContainerObject();
            $this->getImportDirectoryContainer();
        }
        $this->prepareLocalSourceStorage();
        $this->parseXmlFileProperties();
        $this->updateNewObj();
		return $this->_newId;
    }

    /**
     * Builds the XapiCmi5 Object
     * @return $this
     */
    private function prepareSingleObject()
    {
        global $DIC;
        /** @var \ILIAS\DI\Container $DIC */

        // create new cmix object
        $this->_cmixObj = new ilObjXapiCmi5();
        // set type of questionpool object
        $this->_cmixObj->setType('xxcf');
        // set title of questionpool object to "dummy"
        $this->_cmixObj->setTitle("dummy");
        // set description of questionpool object
        $this->_cmixObj->setDescription("test import");
        // create the questionpool class in the ILIAS database (object_data table)
        $this->_cmixObj->create(true);
        $this->_newId = $this->_cmixObj->getId();
        $this->_mapping->addMapping('Plugins/XapiCmi5', 'xxcf', $this->_import_objId, $this->_newId);
        //$this->getImport();
        $this->_cmixObj->update();

        return $this;
    }

    /**
     * Builds the XapiCmi5 Object
     * @return $this
     */
    private function prepareContainerObject()
    {
        global $DIC;
        /** @var \ILIAS\DI\Container $DIC */

        // Container import => test object already created TODO
        include_once "./Modules/Test/classes/class.ilObjTest.php";
        $this->_import_dirname = $this->getImportDirectoryContainer();

        if ($this->_newId = $this->_mapping->getMapping('Services/Container', 'objs', $this->_import_objId)) {
            // container content
            $this->_cmixObj = ilObjectFactory::getInstanceByObjId($this->_newId, false);
            //$_SESSION['tst_import_subdir'] = $this->getImportPackageName();
            $this->_cmixObj->save(); // this generates test id first time
            //var_dump([$this->getImportDirectory(), $this->_import_dirname]); exit;
            $this->_mapping->addMapping("Plugins/XapiCmi5", "xxcf", $this->_import_objId, $this->_newId);
        }
        $this->_cmixObj->save();
        $this->_cmixObj->update();
    }

    /**
     * Creates a folder in the data directory of the document root
     * @return $this
     * @throws \ILIAS\Filesystem\Exception\FileNotFoundException
     * @throws \ILIAS\Filesystem\Exception\IOException
     */
    private function prepareLocalSourceStorage()
    {
        global $DIC;
        /** @var \ILIAS\DI\Container $DIC */

        if (true === (bool) $DIC->filesystem()->temp()->has($this->_relImportDir . '/content.zip')) {
            $this->_hasContent = true;
            $this->_relWebDir = $this->_relWebDir . $this->_cmixObj->getId();
            if (false === (bool) $DIC->filesystem()->web()->has($this->_relWebDir)) {
                $DIC->filesystem()->web()->createDir($this->_relWebDir);
                $DIC->filesystem()->web()->put($this->_relWebDir . '/content.zip', $DIC->filesystem()->temp()->read($this->_relImportDir . '/content.zip'));
                $webDataDir = ilUtil::getWebspaceDir();
                ilUtil::unzip($webDataDir . "/" . $this->_relWebDir . "/content.zip");
                $DIC->filesystem()->web()->delete($this->_relWebDir . '/content.zip');
            }
        }
        return $this;
    }

    /**
     * Parse xml file and set properties
     * @return $this
     * @throws \ILIAS\Filesystem\Exception\FileNotFoundException
     * @throws \ILIAS\Filesystem\Exception\IOException
     */
    private function parseXmlFileProperties()
    {
        global $DIC; /** @var \ILIAS\DI\Container $DIC */

        $xmlRoot = null;
        $xml = $DIC->filesystem()->temp()->readStream($this->_relImportDir . '/properties.xml');
        if ($xml !== false) {
            $xmlRoot = simplexml_load_string($xml);
        }
        foreach ($this->_dataset->_cmixSettingsProperties as $key => $property) {
            $this->_moduleProperties[$key] = trim($xmlRoot->$key->__toString());
        }
        $this->_moduleProperties['Title'] = $this->_moduleProperties['Title'];
        return $this;
    }

    /**
     * Finalize the new XapiCmi5 Object
     * @return $this
     */
    private function updateNewObj()
    {
        global $DIC; /** @var \ILIAS\DI\Container $DIC */
        $this->_cmixObj->setTitle($this->_moduleProperties['Title']." ".$DIC->language()->txt("copy_of_suffix"));
        $this->_cmixObj->setDescription($this->_moduleProperties['Description']);
        $this->_cmixObj->update();

        if ($this->_moduleProperties['LrsTypeId']) {
            $this->_cmixObj->setLrsTypeId((int) $this->_moduleProperties['LrsTypeId']);
            $this->_cmixObj->setLrsType(new ilXapiCmi5LrsType((int) $this->_moduleProperties['LrsTypeId']));
        }
        $this->_cmixObj->setContentType($this->_moduleProperties['ContentType']);
        $this->_cmixObj->setSourceType($this->_moduleProperties['SourceType']);
        $this->_cmixObj->setActivityId($this->_moduleProperties['ActivityId']);
        $this->_cmixObj->setInstructions($this->_moduleProperties['Instructions']);
//        $this->_cmixObj->setOfflineStatus($this->_moduleProperties['OfflineStatus']);
        $this->_cmixObj->setLaunchUrl($this->_moduleProperties['LaunchUrl']);
        $this->_cmixObj->setAuthFetchUrlEnabled($this->_moduleProperties['AuthFetchUrl']);
        $this->_cmixObj->setLaunchMethod($this->_moduleProperties['LaunchMethod']);
        $this->_cmixObj->setLaunchMode($this->_moduleProperties['LaunchMode']);
        $this->_cmixObj->setMasteryScore($this->_moduleProperties['MasteryScore']);
        $this->_cmixObj->setKeepLpStatusEnabled($this->_moduleProperties['KeepLp']);
        $this->_cmixObj->setPrivacyIdent($this->_moduleProperties['PrivacyIdent']);
        $this->_cmixObj->setPrivacyName($this->_moduleProperties['PrivacyName']);
        $this->_cmixObj->setUserPrivacyComment($this->_moduleProperties['UsrPrivacyComment']);
        $this->_cmixObj->setStatementsReportEnabled($this->_moduleProperties['ShowStatements']);
        $this->_cmixObj->setXmlManifest($this->_moduleProperties['XmlManifest']);
        $this->_cmixObj->setVersion($this->_moduleProperties['Version']);
        $this->_cmixObj->setHighscoreEnabled($this->_moduleProperties['HighscoreEnabled']);
        $this->_cmixObj->setHighscoreAchievedTS($this->_moduleProperties['HighscoreAchievedTs']);
        $this->_cmixObj->setHighscorePercentage($this->_moduleProperties['HighscorePercentage']);
        $this->_cmixObj->setHighscoreWtime($this->_moduleProperties['HighscoreWtime']);
        $this->_cmixObj->setHighscoreOwnTable($this->_moduleProperties['HighscoreOwnTable']);
        $this->_cmixObj->setHighscoreTopTable($this->_moduleProperties['HighscoreTopTable']);
        $this->_cmixObj->setHighscoreTopNum($this->_moduleProperties['HighscoreTopNum']);
        $this->_cmixObj->setBypassProxyEnabled($this->_moduleProperties['BypassProxy']);
        $this->_cmixObj->setOnlyMoveon($this->_moduleProperties['OnlyMoveon']);
        $this->_cmixObj->setAchieved($this->_moduleProperties['Achieved']);
        $this->_cmixObj->setAnswered($this->_moduleProperties['Answered']);
        $this->_cmixObj->setCompleted($this->_moduleProperties['Completed']);
        $this->_cmixObj->setFailed($this->_moduleProperties['Failed']);
        $this->_cmixObj->setInitialized($this->_moduleProperties['Initialized']);
        $this->_cmixObj->setPassed($this->_moduleProperties['Passed']);
        $this->_cmixObj->setProgressed($this->_moduleProperties['Progressed']);
        $this->_cmixObj->setSatisfied($this->_moduleProperties['Satisfied']);
        $this->_cmixObj->setTerminated($this->_moduleProperties['Terminated']);
        $this->_cmixObj->setHideData($this->_moduleProperties['HideData']);
        $this->_cmixObj->setTimestamp($this->_moduleProperties['Timestamp']);
        $this->_cmixObj->setDuration($this->_moduleProperties['Duration']);
        $this->_cmixObj->setNoSubstatements($this->_moduleProperties['NoSubstatements']);
        $this->_cmixObj->setPublisherId((string)$this->_moduleProperties['PublisherId']);
//        $this->_cmixObj->setAnonymousHomepage($this->_moduleProperties['AnonymousHomepage']);
        $this->_cmixObj->setMoveOn((string)$this->_moduleProperties['MoveOn']);
        $this->_cmixObj->setLaunchParameters((string)$this->_moduleProperties['LaunchParameters']);
        $this->_cmixObj->setEntitlementKey((string)$this->_moduleProperties['EntitlementKey']);
        $this->_cmixObj->setSwitchToReviewEnabled($this->_moduleProperties['SwitchToReview']);
        $this->_cmixObj->setLPMode($this->_moduleProperties['LPMode']); //plugin specific
        // $this->_cmixObj->setWidth($this->_moduleProperties['Width']);//plugin specific
        // $this->_cmixObj->setHeight($this->_moduleProperties['Height']);//plugin specific
        $this->_cmixObj->setAvailabilityType($this->_moduleProperties['AvailabilityType']);//plugin specific

        $this->_cmixObj->save();
        $this->_cmixObj->updateMetaData();

        return $this;
    }

    /**
     * Delete the import directory
     * @return $this
     * @throws \ILIAS\Filesystem\Exception\FileNotFoundException
     * @throws \ILIAS\Filesystem\Exception\IOException
     */
    private function deleteImportDirectiry()
    {
        global $DIC; /** @var \ILIAS\DI\Container $DIC */
        $DIC->filesystem()->temp()->delete($this->_relImportDir);
        return $this;
    }

    /**
     * Gets the relative path to the Filesystem::temp Folder
     * @return $this
     */
    private function getImportDirectorySingle()
    {
        $importTempDir = $this->getImportDirectory();
        $dirArr = array_reverse(explode('/', $importTempDir));
        $this->_relImportDir = $dirArr[1] . '/' . $dirArr[0];
        return $this;
    }

    /**
     * Gets the relative path to the Filesystem::temp Folder
     * @return $this
     */
    private function getImportDirectoryContainer()
    {
        $importTempDir = $this->getImportDirectory();
        $dirArr = array_reverse(explode('/', $importTempDir));
        $this->_relImportDir = $dirArr[3] . '/' . $dirArr[2] . '/' . $dirArr[1] . '/' . $dirArr[0];
        return $this;
        /*
        $dir = $this->getImportDirectory();
        $dir = dirname($dir);
        return $dir;
        */
    }

    /**  */
    public function init() : void
    {
    }

    /**
     * if single import then deleteImportDirectiry
     */
    public function __destruct()
    {
        if (true === $this->_isSingleImport) {
            $this->deleteImportDirectiry();
        }
    }





//    /**
//     * Import xml representation
//     *
//     * @param $a_entity
//     * @param $a_id
//     * @param $a_xml
//     * @param $a_mapping
//     * @return    string        xml string
//     * @throws ilDatabaseException
//     * @throws ilObjectNotFoundException
//     */
//	public function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping) {
//
//		global $lng;
//		$xml = simplexml_load_string($a_xml);
//
//		$entity = $a_entity;
//
//		if($new_id = $a_mapping->getMapping('Services/Container','objs',$a_id)) {
//			$entity = ilObjectFactory::getInstanceByObjId($new_id,false);
//		} elseif($new_id = $a_mapping->getMapping('Services/Container','refs',0)) {
//			$entity = ilObjectFactory::getInstanceByRefId($new_id,false);
//		} elseif(!$entity instanceof ilObjXapiCmi5) {
//			$entity = new ilObjXapiCmi5();
//			$entity->setTitle((string) $xml->title." ".$lng->txt("copy_of_suffix"));
//			$entity->setImportId($a_id);
//			$entity->create();
//		}
//
//		//check id for given type_name
//		$entity->setTypeName((string) $xml->type_name);
//		if ($entity->getTypeId() == 0) {
//			$pl = new ilXapiCmi5Plugin();
//			ilUtil::sendFailure(sprintf($pl->txt('type_name_not_available'),$xml->type_name), false);
//			return false;
//		}
//
//		try {
//			$entity->setDescription((string) $xml->description);
//			// $entity->setOnline((string) $xml->online);
//			$entity->setAvailabilityType((string) $xml->availability_type);
//			$entity->setInstructions((string) $xml->instructions);
//			$entity->setLaunchUrl((string) $xml->launch_url);
//			$entity->setActivityId((string) $xml->activity_id);
//			$entity->setAuthFetchUrlEnabled((string) $xml->auth_fetch_url);
//			$entity->setAuthFetchUrlEnabled((string) $xml->use_fetch);//before V4
//			$entity->setPrivacyIdent((string) $xml->privacy_ident);
//			$entity->setPrivacyName((string) $xml->privacy_name);
//			$entity->setStatementsReportEnabled((int) $xml->show_statements);
//			$entity->setLPMode((string) $xml->lp_mode);
//			$entity->setLPThreshold((string) $xml->lp_threshold);
//			$entity->setOnlyMoveon((int) $xml->only_moveon);
//			$entity->setAchieved((int) $xml->achieved);
//			$entity->setAnswered((int) $xml->answered);
//			$entity->setCompleted((int) $xml->completed);
//			$entity->setFailed((int) $xml->failed);
//			$entity->setInitialized((int) $xml->initialized);
//			$entity->setPassed((int) $xml->passed);
//			$entity->setProgressed((int) $xml->progressed);
//			$entity->setSatisfied((int) $xml->satisfied);
//			$entity->setTerminated((int) $xml->terminated);
//			$entity->setHideData((int) $xml->hide_data);
//			$entity->setTimestamp((int) $xml->timestamp);
//			$entity->setDuration((int) $xml->duration);
//			$entity->setNoSubstatements((int) $xml->no_substatements);
//			$entity->update();
//			$a_mapping->addMapping("Plugins/XapiCmi5", "xxcf", $a_id, $entity->getId());
//		} catch (Exception $e) {
//			$GLOBALS['ilLog']->write(__METHOD__.': Parsing failed with message, "'.$e->getMessage().'".');
//		}
//
//		return $entity->getId();
//
//	}
}  // EOF class
