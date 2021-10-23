<?php
/**
 * Copyright (c) 2018 internetlehrer GmbH
 * GPLv2, see LICENSE 
 */

/**
 * xAPI Content plugin: base parameters
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 

// alphanumerical ID of the plugin; never change this
$id = "xxcf";
 
// code version; must be changed for all code changes
define('xxcf_version', '3.2.9');
$version = xxcf_version;
 
// ilias min and max version; must always reflect the versions that should
// run with the plugin
$ilias_min_version = "5.4.0";
$ilias_max_version = "6.99";
 
// optional, but useful: Add one or more responsible persons and a contact email
$responsible = "Uwe Kohnle";
$responsible_mail = "kohnle@internetlehrer-gmbh.de";

$supports_export = true;
$learning_progress = true;
?>
