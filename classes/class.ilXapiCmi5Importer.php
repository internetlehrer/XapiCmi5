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

    /**
     * Import xml representation
     *
     * @param $a_entity
     * @param $a_id
     * @param $a_xml
     * @param $a_mapping
     * @return    string        xml string
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
	public function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping) {

		global $lng;
		$xml = simplexml_load_string($a_xml);

		$entity = $a_entity;

		if($new_id = $a_mapping->getMapping('Services/Container','objs',$a_id)) {
			$entity = ilObjectFactory::getInstanceByObjId($new_id,false);
		} elseif($new_id = $a_mapping->getMapping('Services/Container','refs',0)) {
			$entity = ilObjectFactory::getInstanceByRefId($new_id,false);
		} elseif(!$entity instanceof ilObjXapiCmi5) {
			$entity = new ilObjXapiCmi5();
			$entity->setTitle((string) $xml->title." ".$lng->txt("copy_of_suffix"));
			$entity->setImportId($a_id);
			$entity->create();
		}

		//check id for given type_name
		$entity->setTypeName((string) $xml->type_name);
		if ($entity->getTypeId() == 0) {
			$pl = new ilXapiCmi5Plugin();
			ilUtil::sendFailure(sprintf($pl->txt('type_name_not_available'),$xml->type_name), false);
			return false;
		}

		try {
			$entity->setDescription((string) $xml->description);
			// $entity->setOnline((string) $xml->online);
			$entity->setAvailabilityType((string) $xml->availability_type);
			$entity->setInstructions((string) $xml->instructions);
			$entity->setLaunchUrl((string) $xml->launch_url);
			$entity->setActivityId((string) $xml->activity_id);
			$entity->setUseFetch((string) $xml->use_fetch);
			$entity->setPrivacyIdent((string) $xml->privacy_ident);
			$entity->setPrivacyName((string) $xml->privacy_name);
			$entity->setShowDebug((string) $xml->show_debug);
			$entity->setLPMode((string) $xml->lp_mode);
			$entity->setLPThreshold((string) $xml->lp_threshold);
			$entity->setOnlyMoveon((int) $xml->only_moveon);
			$entity->setAchieved((int) $xml->achieved);
			$entity->setAnswered((int) $xml->answered);
			$entity->setCompleted((int) $xml->completed);
			$entity->setFailed((int) $xml->failed);
			$entity->setInitialized((int) $xml->initialized);
			$entity->setPassed((int) $xml->passed);
			$entity->setProgressed((int) $xml->progressed);
			$entity->setSatisfied((int) $xml->satisfied);
			$entity->setTerminated((int) $xml->terminated);
			$entity->setHideData((int) $xml->hide_data);
			$entity->setTimestamp((int) $xml->timestamp);
			$entity->setDuration((int) $xml->duration);
			$entity->setNoSubstatements((int) $xml->no_substatements);			
			$entity->update();
			$a_mapping->addMapping("Plugins/XapiCmi5", "xxcf", $a_id, $entity->getId());
		} catch (Exception $e) {
			$GLOBALS['ilLog']->write(__METHOD__.': Parsing failed with message, "'.$e->getMessage().'".');
		}
		
		return $entity->getId();

	}
}