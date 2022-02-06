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
$plugin = true;

// old required?
/*
if (empty($_COOKIE)) {
    $_COOKIE = json_decode(base64_decode(rawurldecode($_GET['sess'])),TRUE);
}
*/

/**
 * handle path context and includes
 */

if ($plugin) {
    // Avoid redirection to start screen
    // (see ilInitialisation::InitILIAS for details)
    $_GET["baseClass"] = "ilStartUpGUI";
    chdir("../../../../../../../");
    require_once './Services/Init/classes/class.ilInitialisation.php';
    require_once './Services/Object/classes/class.ilObjectFactory.php';
    require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/XapiCmi5/classes/class.ilXapiCmi5AuthToken.php';
}
else 
{
    chdir("../../");
    require_once 'libs/composer/vendor/autoload.php';
}

/**
 * handle ILIAS Init old
 */
//require_once __DIR__.'/classes/XapiProxy/DataService.php';
//DataService::initIlias($client);

$tokenRestriction = true;

$origParam = $_GET['param'];

if (!isset($origParam) || !strlen($origParam))
{
    $error = array('error-code' => 3,'error-text'=> 'invalid request: missing or empty param request parameter');
    send($error);
}

try
{
    $param = base64_decode(rawurldecode($origParam));
    
    $param = json_decode(openssl_decrypt(
        $param,
        ilXapiCmi5AuthToken::OPENSSL_ENCRYPTION_METHOD,
        ilXapiCmi5AuthToken::getWacSalt(),
        0,
        ilXapiCmi5AuthToken::OPENSSL_IV
    ), true);

    $_COOKIE[session_name()] = $param[session_name()];
    $_COOKIE['ilClientId'] = $param['ilClientId'];
    $objId = $param['obj_id'];
    $refId = $param['ref_id'];

    #\XapiProxy\DataService::initIlias($_COOKIE['ilClientId']);
    ilInitialisation::initILIAS();
    $DIC = $GLOBALS['DIC'];
}
catch (Exception $e)
{
    $error = array('error-code' => '3','error-text'=> 'internal server error');
    send($error);
}

try
{
    $object = ilObjectFactory::getInstanceByObjId($objId, false);
    $token = ilXapiCmi5AuthToken::getInstanceByObjIdAndRefIdAndUsrId($objId, $refId, $DIC->user()->getId());
    if ($object->getContentType() == $object::CONT_TYPE_CMI5)
    {
        $tokenCmi5Session = $token->getCmi5Session();
        $alreadyReturnedCmi5Session = $token->getReturnedForCmi5Session();
        if ($tokenCmi5Session == $alreadyReturnedCmi5Session)
        {
            // what about reloaded or refreshed pages?
            // see: https://stackoverflow.com/questions/456841/detect-whether-the-browser-is-refreshed-or-not-using-php/456915
            // Beware that the xapitoken request is an ajax request and not all clients send HTTP_REFERRER Header
            if ($tokenRestriction == true)
            {
                $error = array('error-code' => '1','error-text'=> 'The authorization token has already been returned.');
                send($error);
            }
        }
        $token->setReturnedForCmi5Session($tokenCmi5Session);
        $token->update();
    }
    if ($object->isBypassProxyEnabled()) {
        $authToken = $object->getLrsType()->getBasicAuthWithoutBasic();
    } else {
        $authToken = base64_encode(CLIENT_ID . ':' . $token->getToken());
    }
    
    
    $response = array("auth-token" => $authToken);
    send($response);
}
catch (Exception $e)
{
    $error = array('error-code' => '2','error-text'=> 'could not create valid session from token.');
    send($error);
}

function send($response)
{
    header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
    header('Access-Control-Allow-Credentials: true');
    header('Content-type:application/json;charset=utf-8');
    echo json_encode($response);
    exit;
}

/*
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

function send($response)
{
    header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
    header('Access-Control-Allow-Credentials: true');
    header('Content-type:application/json;charset=utf-8');
    echo json_encode($response);
    exit;
}
*/