<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

/**
 * xApi plugin: event log script
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 

// fim: [debug] optionally set error before initialisation
error_reporting (E_ALL);
ini_set("display_errors","on");
// fim.

chdir("../../../../../../../");

// Avoid redirection to start screen
// (see ilInitialisation::InitILIAS for details)
$_GET["baseClass"] = "ilStartUpGUI";

require_once "./include/inc.header.php";
// logGUI missing UK
// require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5LogGUI.php";

// $log_obj = new ilXapiCmi5Log();


//$log_obj->performCommand();


?>