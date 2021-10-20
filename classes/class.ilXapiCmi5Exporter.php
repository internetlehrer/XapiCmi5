<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

require_once("./Services/Export/classes/class.ilXmlExporter.php");

/**
 * Class ilTestRepositoryObjectExporter
 *
 */
class ilXapiCmi5Exporter extends ilXmlExporter {

	/**
	 * Get xml representation
	 *
	 * @param    string        entity
	 * @param    string        schema version
	 * @param    string        id
	 * @return    string        xml string
	 */
	public function getXmlRepresentation($a_entity, $a_schema_version, $a_id) {
		$ref_ids = ilObject::_getAllReferences($a_id);
		$ref_id = array_shift($ref_ids);
		require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5.php");
		$entity = new ilObjXapiCmi5($ref_id);

		include_once "./Services/Xml/classes/class.ilXmlWriter.php";
		$writer = new ilXmlWriter();
		$writer->xmlStartTag("xxcf");
		$writer->xmlElement("title", null, $entity->getTitle());
		$writer->xmlElement("description", null, $entity->getDescription());
		// $writer->xmlElement("online", null, $entity->isOnline());
		$writer->xmlElement("availability_type", null, $entity->getAvailabilityType());
		$writer->xmlElement("type_name", null, $entity->getTypeName());
		$writer->xmlElement("instructions", null, $entity->getInstructions());
		$writer->xmlElement("launch_url", null, $entity->getLaunchUrl());
		$writer->xmlElement("activity_id", null, $entity->getActivityId());
		$writer->xmlElement("use_fetch", null, $entity->getUseFetch());
		$writer->xmlElement("privacy_ident", null, $entity->getPrivacyIdent());
		$writer->xmlElement("privacy_name", null, $entity->getPrivacyName());
		$writer->xmlElement("show_statements", null, $entity->isStatementsReportEnabled());
		$writer->xmlElement("lp_mode", null, $entity->getLPMode());
		$writer->xmlElement("lp_threshold", null, $entity->getLPThreshold());
		$writer->xmlElement("only_moveon", null, (int)$entity->getOnlyMoveon());
		$writer->xmlElement("achieved", null, (int)$entity->getAchieved());
		$writer->xmlElement("answered", null, (int)$entity->getAnswered());
		$writer->xmlElement("completed", null, (int)$entity->getCompleted());
		$writer->xmlElement("failed", null, (int)$entity->getFailed());
		$writer->xmlElement("initialized", null, (int)$entity->getInitialized());
		$writer->xmlElement("passed", null, (int)$entity->getPassed());
		$writer->xmlElement("progressed", null, (int)$entity->getProgressed());
		$writer->xmlElement("satisfied", null, (int)$entity->getSatisfied());
		$writer->xmlElement("terminated", null, (int)$entity->getTerminated());
		$writer->xmlElement("hide_data", null, (int)$entity->getHideData());
		$writer->xmlElement("timestamp", null, (int)$entity->getTimestamp());
		$writer->xmlElement("duration", null, (int)$entity->getDuration());
		$writer->xmlElement("no_substatements", null, (int)$entity->getNoSubstatements());		
		$writer->xmlEndTag("xxcf");

		return $writer->xmlDumpMem(false);;
	}

	public function init() {
		// TODO: Implement init() method.
	}

	/**
	 * Returns schema versions that the component can export to.
	 * ILIAS chooses the first one, that has min/max constraints which
	 * fit to the target release. Please put the newest on top. Example:
	 *
	 *        return array (
	 *        "4.1.0" => array(
	 *            "namespace" => "http://www.ilias.de/Services/MetaData/md/4_1",
	 *            "xsd_file" => "ilias_md_4_1.xsd",
	 *            "min" => "4.1.0",
	 *            "max" => "")
	 *        );
	 *
	 *
	 * @return        array
	 */
	public function getValidSchemaVersions($a_entity) {
		return array (
	         "5.2.0" => array(
	             "namespace" => "http://www.ilias.de/Plugins/TestRepositoryObject/md/5_2",
	             "xsd_file" => "ilias_md_5_2.xsd",
	             "min" => "5.2.0",
	             "max" => "")
	         );
	}
}