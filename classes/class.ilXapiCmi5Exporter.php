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

    const ENTITY = 'xxcf';
    const SCHEMA_VERSION = '5.3.0';

    private $main_object = null;
    private $_dataset = null;

    public function __construct()
    {
        parent::__construct();
        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5DataSet.php");
        $this->_dataset = new ilXapiCmi5DataSet();
        $this->_dataset->setExportDirectories($this->dir_relative, $this->dir_absolute);
        $this->_dataset->setDSPrefix("ds");

        /*
        $this->main_object = $a_main_object;
        include_once("./Modules/CmiXapi/classes/class.ilCmiXapiDataSet.php");
        $this->dataset = new ilCmiXapiDataSet($this->main_object->getRefId());
        $this->getXmlRepresentation(self::ENTITY, self::SCHEMA_VERSION, $this->main_object->getRefId());
        */
    }

    public function init()
    {
    }

    /**
     * Get xml representation
     *
     * @param	string		entity
     * @param	string		target release
     * @param	string		id
     * @return	string		xml string
     */
    public function getXmlRepresentation($a_entity, $a_schema_version, $a_id)
    {
        return $this->_dataset->getXapiCmi5XmlRepresentation($a_entity, $a_schema_version, $a_id, "", true, true);
    }

    /**
	 * Get xml representation
	 *
	 * @param    string        entity
	 * @param    string        schema version
	 * @param    string        id
	 * @return    string        xml string
	 */
//	public function getXmlRepresentation($a_entity, $a_schema_version, $a_id) {
//		$ref_ids = ilObject::_getAllReferences($a_id);
//		$ref_id = array_shift($ref_ids);
//		require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5.php");
//		$entity = new ilObjXapiCmi5($ref_id);
//
//		include_once "./Services/Xml/classes/class.ilXmlWriter.php";
//		$writer = new ilXmlWriter();
//		$writer->xmlStartTag("xxcf");
//		$writer->xmlElement("title", null, $entity->getTitle());
//		$writer->xmlElement("description", null, $entity->getDescription());
//		// $writer->xmlElement("online", null, $entity->isOnline());
//		$writer->xmlElement("availability_type", null, $entity->getAvailabilityType());
//		$writer->xmlElement("type_name", null, $entity->getTypeName());//until version 3.3
//        $entity->getLr
//		$writer->xmlElement("instructions", null, $entity->getInstructions());
//		$writer->xmlElement("launch_url", null, $entity->getLaunchUrl());
//		$writer->xmlElement("activity_id", null, $entity->getActivityId());
//		$writer->xmlElement("auth_fetch_url", null, $entity->isAuthFetchUrlEnabled());
//		$writer->xmlElement("privacy_ident", null, $entity->getPrivacyIdent());
//		$writer->xmlElement("privacy_name", null, $entity->getPrivacyName());
//		$writer->xmlElement("show_statements", null, $entity->isStatementsReportEnabled());
//		$writer->xmlElement("lp_mode", null, $entity->getLPMode());
//		$writer->xmlElement("lp_threshold", null, $entity->getLPThreshold());
//		$writer->xmlElement("only_moveon", null, (int)$entity->getOnlyMoveon());
//		$writer->xmlElement("achieved", null, (int)$entity->getAchieved());
//		$writer->xmlElement("answered", null, (int)$entity->getAnswered());
//		$writer->xmlElement("completed", null, (int)$entity->getCompleted());
//		$writer->xmlElement("failed", null, (int)$entity->getFailed());
//		$writer->xmlElement("initialized", null, (int)$entity->getInitialized());
//		$writer->xmlElement("passed", null, (int)$entity->getPassed());
//		$writer->xmlElement("progressed", null, (int)$entity->getProgressed());
//		$writer->xmlElement("satisfied", null, (int)$entity->getSatisfied());
//		$writer->xmlElement("terminated", null, (int)$entity->getTerminated());
//		$writer->xmlElement("hide_data", null, (int)$entity->getHideData());
//		$writer->xmlElement("timestamp", null, (int)$entity->getTimestamp());
//		$writer->xmlElement("duration", null, (int)$entity->getDuration());
//		$writer->xmlElement("no_substatements", null, (int)$entity->getNoSubstatements());
//      $writer->xmlElement("no_unallocatable_statements", null, (int)$entity->getNoUnallocatableStatements());
//		$writer->xmlEndTag("xxcf");
//
//		return $writer->xmlDumpMem(false);;
//	}


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
	         "5.3.0" => array(
	             "namespace" => "http://www.ilias.de/Plugins/xxcf/5_2",
	             "xsd_file" => "ilias_xxcf_5_3.xsd",
	             "min" => "5.3.0",
	             "max" => "")
	         );
	}
}