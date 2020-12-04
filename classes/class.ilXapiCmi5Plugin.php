<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

include_once('./Services/Repository/classes/class.ilRepositoryObjectPlugin.php');

/**
 * xApi plugin
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
class ilXapiCmi5Plugin extends ilRepositoryObjectPlugin
{


	/**
	 * Returns name of the plugin
	 *
	 * @return string
	 * @access public
	 */
	public function getPluginName()
	{
		return 'XapiCmi5';
	}

	/**
	 * Remove all custom tables when plugin is uninstalled
	 */
	protected function uninstallCustom()
	{
		global $ilDB;
		$ilDB->dropTable('xxcf_data_types');
		$ilDB->dropTable('xxcf_data_settings');
		$ilDB->dropTable('xxcf_results');
		$ilDB->dropTable('xxcf_user_mapping');
		$ilDB->dropTable('xxcf_data_token');
	}
	

	/**
	* Create webspace directory for the plugin
	* 
	* @param	string		level ("plugin", "type" or "object")
	* @param	integer		type id or object id 
	* 
	* @return	string		webspace directory
	*/
	static function _createWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		switch($a_level)
		{
			case "plugin":
				$plugin_dir = self::_getWebspaceDir('plugin');
				if (!is_dir($plugin_dir))
				{
					ilUtil::makeDir($plugin_dir);
				}
				return $plugin_dir;
								
			case "type":
				$plugin_dir = self::_createWebspaceDir("plugin");
				$type_dir = $plugin_dir . "/type_". $a_id;
				if (!is_dir($type_dir))
				{
					ilUtil::makeDir($type_dir);
				}
				return $type_dir;
								
			case "object":
				$plugin_dir = self::_createWebspaceDir("plugin");
				$object_dir = $plugin_dir . "/object_". $a_id;
				if (!is_dir($object_dir))
				{
					ilUtil::makeDir($object_dir);
				}
				return $object_dir;
		}
	}	
	
	/**
	* Get a webspace directory
	*
	* @param	string		level ("plugin", "type" or "object")
	* @param	integer		type id or object id 
	* 
	* @return	string		webspace directory
	*/
	static function _getWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		switch($a_level)
		{
			case "plugin":
				return ilUtil::getWebspaceDir()."/xxcf";
				
			case "type":
				return ilUtil::getWebspaceDir()."/xxcf/type_".$a_id;
				
			case "object":
				return ilUtil::getWebspaceDir()."/xxcf/object_".$a_id;
		}
	}

	/**
	* Delete a webspace directory
	*
	* @param	string		level ("plugin", "type" or "object")
	* @param	integer		type id or object id
	*/
	static function _deleteWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		return ilUtil::delDir(self::_getWebspaceDir($a_level, $a_id));
	}
	
}
?>