<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

include_once "./Services/Repository/classes/class.ilObjectPluginListGUI.php";

/**
 * xApi plugin: repository object list
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
class ilObjXapiCmi5ListGUI extends ilObjectPluginListGUI 
{
	/**
	*  all xxcf type definitions
	*/
	static $xxcf_types = array();

    /**
     * Init type
     */
    function initType() 
    {
        $this->setType("xxcf");
    }

    /**
     * Get name of gui class handling the commands
     */
    function getGuiClass() 
    {
        return "ilObjXapiCmi5GUI";
    }

    /**
     * Get commands
     */
    function initCommands() 
    {
        return array
            (
            array(
                "permission" => "read",
                "cmd" => "view",
                "default" => true),
            array(
                "permission" => "write",
                "cmd" => "edit",
                "txt" => $this->txt("edit"),
                "default" => false),
        );
    }

    /**
     * get properties (offline)
     *
     * @access public
     * @param
     * 
     */
    public function getProperties() 
    {
        global $lng;

        $this->plugin->includeClass("class.ilObjXapiCmi5Access.php");
        if (!ilObjXapiCmi5Access::_lookupOnline($this->obj_id)) 
        {
            $props[] = array("alert" => true, "property" => $lng->txt("status"),
                "value" => $lng->txt("offline"));
        }
        return $props ? $props : array();
    }

}
// END class.ilObjXapiCmi5ListGUI
?>