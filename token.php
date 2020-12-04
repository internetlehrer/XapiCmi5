<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

/**
 * xApi plugin: token generation script
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
chdir("../../../../../../../");

if (empty($_COOKIE)) {
    $_COOKIE = json_decode(base64_decode(rawurldecode($_GET['sess'])),TRUE);
}

// Avoid redirection to start screen
// (see ilInitialisation::InitILIAS for details)

$_GET["baseClass"] = "ilStartUpGUI";

require_once "./include/inc.header.php";
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilObjXapiCmi5.php";

$track_obj = new ilObjXapiCmi5();
$token = base64_encode(CLIENT_ID . ':' . $track_obj->getToken());
$res = array("auth-token" => $token);

//$token = base64_encode('isam:hdgtezdhztcghdzehdzusuejdzsuhtdh');
//$res = array("auth-token" => $token);

header('Content-type:application/json;charset=utf-8');
echo json_encode($res);
?>
