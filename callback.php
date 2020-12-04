<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

/**
 * xApi plugin: callback script
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 

// fim: [debug] optionally set error before initialisation
// error_reporting (E_ALL);
// ini_set("display_errors","on");
// fim.

chdir("../../../../../../../");

// Avoid redirection to start screen
// (see ilInitialisation::InitILIAS for details)
$_GET["baseClass"] = "ilStartUpGUI";

require_once "./include/inc.header.php";
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5GUI.php";

//$track_obj = new ilObjXapiCmi5();
//$track_obj->performCommand("checkToken");

echo "p";
exit;

?>
