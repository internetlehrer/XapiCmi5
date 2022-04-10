<#1>
<?php
/**
 * Copyright (c) 2018 internetlehrer GmbH 
 * GPLv2, see LICENSE 
 */

/**
 * xApi plugin: database update script
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 

/**
 * Type definitions
 */
if(!$ilDB->tableExists('xxcf_data_types'))
{
	$types = array(
		'type_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'type_name' => array(
			'type' => 'text',
			'length' => 32
		),
		'title' => array(
			'type' => 'text',
			'length' => 255
		),
		'description' => array(
			'type' => 'text',
			'length' => 4000
		),
		'availability' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 1
		),
		'remarks' => array(
			'type' => 'text',
			'length' => 4000
		),
		'time_to_delete' => array(
			'type' => 'integer',
			'length' => 4
		),
		'log_level' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 0
		),
		'lrs_type_id' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 1
		),
		'lrs_endpoint' => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => true
		),
		'lrs_key' => array(
			'type' => 'text',
			'length' => 128,
			'notnull' => true
		),
		'lrs_secret' => array(
			'type' => 'text',
			'length' => 128,
			'notnull' => true
		),
		'privacy_ident' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 0
		),
		'privacy_name' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 0
		),
		'privacy_comment_default' => array(
			'type' => 'text',
			'length' => 2000,
			'notnull' => true
		),
		'external_lrs' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		)		
	);
	$ilDB->createTable("xxcf_data_types", $types);
	$ilDB->addPrimaryKey("xxcf_data_types", array("type_id"));
	$ilDB->createSequence("xxcf_data_types");
}

?>
<#2>
<?php
/**
 * settings for xapi-objects
 */
if(!$ilDB->tableExists('xxcf_data_settings'))
{
	$settings = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'type_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'instructions' => array(
			'type' => 'text',
			'length' => 4000
		),
		'availability_type' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'meta_data_xml' => array(
			'type' => 'clob'
		),
		'lp_mode' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 0
		),
		'lp_threshold' => array(
			'type' => 'float',
			'notnull' => true,
			'default' => 0.5
		),
		'launch_key' => array(
			'type' => 'text',
			'length' => 64,
			'notnull' => true
		),
		'launch_secret' => array(
			'type' => 'text',
			'length' => 64
		),
		'launch_url' => array(
			'type' => 'text',
			'length' => 64,
			'notnull' => true
		),
		'activity_id' => array(
			'type' => 'text',
			'length' => 64,
			'notnull' => true
		),
		'open_mode' => array (
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		),
		'width' => array (
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 950
		),
		'height' => array (
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 650
		),
		'show_debug' => array (
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		),
		'privacy_comment' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => true
		),
		'version' => array (
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 1
		)
	);

	$ilDB->createTable("xxcf_data_settings", $settings);
	$ilDB->addPrimaryKey("xxcf_data_settings", array("obj_id"));
}
?>
<#3>
<?php 
/**
 * table for detailed learning progress
 */
if(!$ilDB->tableExists('xxcf_results'))
{
	$values = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
		),
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
		),
		'usr_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
		),
		'version' => array (
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 1
		),
		'result' => array(
			'type' => 'float',
			'notnull' => false,
		),
		'status' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		),
		'time' => array(
			'type' => 'timestamp',
			'notnull' => true,
			'default' => ''
		)
	);
	$ilDB->createTable("xxcf_results", $values);
	$ilDB->addPrimaryKey("xxcf_results", array("id"));
	$ilDB->createSequence("xxcf_results");
	$ilDB->addIndex("xxcf_results", array("obj_id","usr_id"), 'i1', false);
}
?>
<#4>
<?php
/**
 * table for user mapping ILIAS-LRS
 */
if(!$ilDB->tableExists('xxcf_user_mapping'))
{
	$values = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'usr_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'lrs_name' => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => true,
			'default' => ''
		),
		'lrs_mail' => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => true
		)
	);
	$ilDB->createTable("xxcf_user_mapping", $values);
	$ilDB->addPrimaryKey("xxcf_user_mapping", array("obj_id","usr_id"));
}
?>
<#5>
<?php
/**
 * table token for auth
 */
if(!$ilDB->tableExists('xxcf_data_token'))
{
	$token = array(
		'token' => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => true,
			'default' => 0
		),
		'time' => array(
			'type' => 'timestamp',
			'notnull' => true,
			'default' => ''
		)
	);
	$ilDB->createTable("xxcf_data_token", $token);
	$ilDB->addPrimaryKey("xxcf_data_token", array("token", "time"));
}
?>
<#6>
<?php
/**
 * 
 */ 
	ilUtil::makeDirParents(ilUtil::getWebspaceDir().'/xxcf/cache');
?>
<#7>
<?php
/**
 * Check whether type exists in object data, if not, create the type
 * The type is normally created at plugin activation, see ilRepositoryObjectPlugin::beforeActivation()
 */
	$set = $ilDB->query("SELECT obj_id FROM object_data WHERE type='typ' AND title = 'xxcf'");
	if ($rec = $ilDB->fetchAssoc($set))
	{
		$typ_id = $rec["obj_id"];
	}
	else
	{
		$typ_id = $ilDB->nextId("object_data");
		$ilDB->manipulate("INSERT INTO object_data ".
			"(obj_id, type, title, description, owner, create_date, last_update) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote("typ", "text").",".
			$ilDB->quote("xxcf", "text").",".
			$ilDB->quote("Plugin xAPI", "text").",".
			$ilDB->quote(-1, "integer").",".
			$ilDB->quote(ilUtil::now(), "timestamp").",".
			$ilDB->quote(ilUtil::now(), "timestamp").
			")");
	}

?>
<#8>
<?php
/**
* Add new RBAC operations
*/
	$set = $ilDB->query("SELECT obj_id FROM object_data WHERE type='typ' AND title = 'xxcf'");
	$rec = $ilDB->fetchAssoc($set);
	$typ_id = $rec["obj_id"];

	$operations = array('edit_learning_progress','read_learning_progress');
	foreach ($operations as $operation)
	{
		$query = "SELECT ops_id FROM rbac_operations WHERE operation = ".$ilDB->quote($operation, 'text');
		$res = $ilDB->query($query);
		$row = $ilDB->fetchObject($res);
		$ops_id = $row->ops_id;
		
		$query = "DELETE FROM rbac_ta WHERE typ_id=".$ilDB->quote($typ_id, 'integer')." AND ops_id=".$ilDB->quote($ops_id, 'integer');
		$ilDB->manipulate($query);

		$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ("
		.$ilDB->quote($typ_id, 'integer').","
		.$ilDB->quote($ops_id, 'integer').")";
		$ilDB->manipulate($query);
	}

?>
<#9>
<?php
	$ilDB->modifyTableColumn('xxcf_data_settings','launch_key', array(
			'type' => 'text',
			'length' => 64,
			'notnull' => false)
	);
	$ilDB->modifyTableColumn('xxcf_data_settings','launch_url', array(
			'type' => 'text',
			'length' => 255,
			'notnull' => false)
	);
	$ilDB->modifyTableColumn('xxcf_data_settings','activity_id', array(
			'type' => 'text',
			'length' => 128,
			'notnull' => false)
	);
	$ilDB->modifyTableColumn('xxcf_data_settings','privacy_comment', array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false)
	);
?>
<#10>
<?php
	if ( !$ilDB->tableColumnExists('xxcf_data_token', 'obj_id') ) {
		$ilDB->addTableColumn('xxcf_data_token', 'obj_id', array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true,
				'default' => 0
		));
	}
	if ( !$ilDB->tableColumnExists('xxcf_data_token', 'usr_id') ) {
		$ilDB->addTableColumn('xxcf_data_token', 'usr_id', array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true,
				'default' => 0
		));
	}

?>
<#11>
<?php
	if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'use_fetch') ) {
		$ilDB->addTableColumn('xxcf_data_settings', 'use_fetch', array(
				'type' => 'integer',
				'length' => 1,
				'notnull' => true,
				'default' => 1
		));
	}
?>
<#12>
<?php
	if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'privacy_ident') ) {
		$ilDB->addTableColumn('xxcf_data_settings', 'privacy_ident', array(
				'type' => 'integer',
				'length' => 2,
				'notnull' => true,
				'default' => 0
		));
	}
	if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'privacy_name') ) {
		$ilDB->addTableColumn('xxcf_data_settings', 'privacy_name', array(
				'type' => 'integer',
				'length' => 2,
				'notnull' => true,
				'default' => 0
		));
	}
	if ( !$ilDB->tableColumnExists('xxcf_user_mapping', 'content_name') ) {
		$ilDB->addTableColumn('xxcf_user_mapping', 'content_name', array(
				'type' => 'text',
				'length' => 255,
				'notnull' => true,
				'default' => ''
		));
	}
	if ( !$ilDB->tableColumnExists('xxcf_user_mapping', 'content_mail') ) {
		$ilDB->addTableColumn('xxcf_user_mapping', 'content_mail', array(
				'type' => 'text',
				'length' => 255,
				'notnull' => true
		));
	}

?>
<#13>
<?php
//removed
?>
<#14>
<?php
/**
 * table for user mapping ILIAS-LRS
 */
if(!$ilDB->tableExists('xxcf_usrobjuuid_map'))
{
	$values = array(
		'usr_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'uuid' => array(
			'type' => 'text',
			'length' => 128,
			'notnull' => true,
			'default' => ''
		)
	);
	$ilDB->createTable("xxcf_usrobjuuid_map", $values);
	$ilDB->addPrimaryKey("xxcf_usrobjuuid_map", array("usr_id","obj_id","uuid"));
}
?>
<#15>
<?php
if ( !$ilDB->tableColumnExists('xxcf_data_types', 'lrs_endpoint_2') ) {
    $ilDB->addTableColumn('xxcf_data_types', 'lrs_endpoint_2', array(
        'type' => 'text',
        'length' => 255,
        'notnull' => false
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_data_types', 'lrs_key_2') ) {
    $ilDB->addTableColumn('xxcf_data_types', 'lrs_key_2', array(
        'type' => 'text',
        'length' => 128,
        'notnull' => false
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_data_types', 'lrs_secret_2') ) {
    $ilDB->addTableColumn('xxcf_data_types', 'lrs_secret_2', array(
        'type' => 'text',
        'length' => 128,
        'notnull' => false
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_data_types', 'endpoint_use') ) {
    $ilDB->addTableColumn('xxcf_data_types', 'endpoint_use', array(
        'type' => 'text',
        'length' => 18,
        'notnull' => true,
        'default' => '1only'
    ));
}
?>
<#16>
<?php
/* Migration */
if($ilDB->tableExists('xxcf_data_types')) {
    $queue = [];
    if ( $ilDB->tableColumnExists('xxcf_data_types', 'endpoint_use') )
    {
        $query = 'SELECT `type_id`, `endpoint_use` FROM `xxcf_data_types`;';
        $res = $ilDB->query($query);
        while ($row = $ilDB->fetchAssoc($res)) {
            $queue[$row['type_id']] = [
                'endpoint_use'     => $row['endpoint_use']
            ];
        }
    }

    if (!empty($queue)) {
        foreach ($queue as $id => $row) {
            $ilDB->update(
                'xxcf_data_types',
                [
                    "endpoint_use"     => [
                        "text", '1only'
                    ]
                ],
                [
                    "type_id" => [
                        "integer", $id
                    ]
                ]
            );
        }
    }
}
?>
<#17>
<?php
if($ilDB->tableExists('xxcf_data_types')) {
	if ( $ilDB->tableColumnExists('xxcf_data_types', 'lrs_endpoint') ) {
		$ilDB->manipulate("ALTER TABLE `xxcf_data_types` CHANGE `lrs_endpoint` `lrs_endpoint_1` VARCHAR( 255 ) NOT NULL");
	}
	if ( $ilDB->tableColumnExists('xxcf_data_types', 'lrs_key') ) {
		$ilDB->manipulate("ALTER TABLE `xxcf_data_types` CHANGE `lrs_key` `lrs_key_1` VARCHAR( 128 ) NOT NULL");
	}
	if ( $ilDB->tableColumnExists('xxcf_data_types', 'lrs_secret') ) {
		$ilDB->manipulate("ALTER TABLE `xxcf_data_types` CHANGE `lrs_secret` `lrs_secret_1` VARCHAR( 128 ) NOT NULL");
	}
}
?>
<#18>
<?php
if($ilDB->tableExists('xxcf_data_settings'))
{
    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'only_moveon') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'only_moveon', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'achieved') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'achieved', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'answered') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'answered', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'completed') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'completed', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'failed') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'failed', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'initialized') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'initialized', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'passed') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'passed', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'progressed') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'progressed', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'satisfied') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'satisfied', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'terminated') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'terminated', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'hide_data') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'hide_data', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'timestamp') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'timestamp', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'duration') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'duration', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'no_substatements') ) {
        $ilDB->addTableColumn('xxcf_data_settings', 'no_substatements', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }

    // Migrate existing entries
    $queue = [];
    $query = 'SELECT * FROM `xxcf_data_settings`;';
    $res = $ilDB->query($query);
    while ($row = $ilDB->fetchAssoc($res)) {
        $queue[$row['obj_id']] = $row;
    }

    if (!empty($queue)) {
        foreach ($queue as $objId => $row) {
            if( !is_numeric($row['only_moveon']) && !is_numeric($row['no_substatements']) ) {
                $ilDB->update(
                    'xxcf_data_settings',
                    [
                        "only_moveon"           => ["integer", 0],
                        "achieved"              => ["integer", 1],
                        "answered"              => ["integer", 1],
                        "completed"             => ["integer", 1],
                        "failed"                => ["integer", 1],
                        "initialized"           => ["integer", 1],
                        "passed"                => ["integer", 1],
                        "progressed"            => ["integer", 1],
                        "satisfied"             => ["integer", 1],
                        "terminated"            => ["integer", 1],
                        "hide_data"             => ["integer", 0],
                        "timestamp"             => ["integer", 0],
                        "duration"              => ["integer", 1],
                        "no_substatements"      => ["integer", 0]
                    ],
                    [
                        "obj_id" => ["integer", $objId]
                    ]
                );
            }
        }
    }
}
?>
<#19>
<?php
if ($ilDB->tableColumnExists('xxcf_data_settings', 'terminated')) {
    $ilDB->renameTableColumn('xxcf_data_settings', 'terminated', 'c_terminated');
}
if ($ilDB->tableColumnExists('xxcf_data_settings', 'timestamp')) {
    $ilDB->renameTableColumn('xxcf_data_settings', 'timestamp', 'c_timestamp');
}
?>
<#20>
<?php
if($ilDB->tableExists('xxcf_data_types'))
{
    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'force_privacy_settings') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'force_privacy_settings', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'only_moveon') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'only_moveon', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'achieved') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'achieved', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'answered') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'answered', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'completed') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'completed', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'failed') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'failed', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'initialized') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'initialized', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'passed') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'passed', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'progressed') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'progressed', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'satisfied') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'satisfied', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'c_terminated') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'c_terminated', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'hide_data') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'hide_data', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'c_timestamp') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'c_timestamp', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'duration') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'duration', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }

    if ( !$ilDB->tableColumnExists('xxcf_data_types', 'no_substatements') ) {
        $ilDB->addTableColumn('xxcf_data_types', 'no_substatements', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
}
?>
<#21>
<?php
// deleted
?>
<#22>
<?php

if (!$ilDB->tableExists('xxcf_users')) {
    $ilDB->createTable('xxcf_users', array(
        'obj_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),
        'usr_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),
		'privacy_ident' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 0
		),
        'usr_ident' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false
        )
    ));
    
    $ilDB->addPrimaryKey('xxcf_users', array('obj_id', 'usr_id', 'privacy_ident'));
}
?>
<#23>
<?php
$iliasDomain = "";
if (IL_INST_ID == 0) {
	$iliasDomain = substr(ILIAS_HTTP_PATH,7);
	if (substr($iliasDomain,0,1) == "\/") $iliasDomain = substr($iliasDomain,1);
	if (substr($iliasDomain,0,4) == "www.") $iliasDomain = substr($iliasDomain,4);
	$iliasDomain = '_' . str_replace('/','_',$iliasDomain).'_'.CLIENT_ID;
}

$set = $ilDB->query("SELECT * FROM xxcf_usrobjuuid_map");
while ($row = $ilDB->fetchAssoc($set)) {
	$ident = IL_INST_ID . '_' . $row['uuid'] . $iliasDomain . '@iliassecretuser.de';
	try {
		$ilDB->insert('xxcf_users', array(
			'obj_id' => array('integer', $row['obj_id']),
			'usr_id' => array('integer', $row['usr_id']),
			'privacy_ident' => array('integer', 4),
			'usr_ident' => array('text', $ident)
			)
		);
	} catch (Exception $e) {}
}

$set = $ilDB->query("select count(*) as UUIDs from xxcf_usrobjuuid_map group by obj_id, usr_id");
$counter_uuid = 0;
while ($row = $ilDB->fetchAssoc($set)) {
	$counter_uuid++;
}

$set = $ilDB->query("SELECT count(*) cnt FROM xxcf_users");
$row = $ilDB->fetchAssoc($set);
$counter_users = $row['cnt'];

if ($counter_uuid == $counter_users) {
	//$ilDB->dropTable("xxcf_usrobjuuid_map");
} else {
	die("migration of users with uuid not successful");
}
?>
<#24>
<?php
// PRIVACY_IDENT_EMAIL = 3;
$set = $ilDB->query("SELECT ut_lp_marks.obj_id, ut_lp_marks.usr_id, usr_data.email "
."FROM ut_lp_marks, usr_data, xxcf_data_settings "
."WHERE xxcf_data_settings.privacy_ident=3 "
."AND ut_lp_marks.obj_id = xxcf_data_settings.obj_id "
."AND usr_data.usr_id = ut_lp_marks.usr_id");

while ($row = $ilDB->fetchAssoc($set)) {
	$ilDB->insert('xxcf_users', array(
		'obj_id' => array('integer', $row['obj_id']),
		'usr_id' => array('integer', $row['usr_id']),
		'privacy_ident' => array('integer', 3),
		'usr_ident' => array('text', $row['email'])
		)
	);
}
?>
<#25>
<?php
// PRIVACY_IDENT_LOGIN = 2;
$set = $ilDB->query("SELECT ut_lp_marks.obj_id, ut_lp_marks.usr_id, usr_data.login "
."FROM ut_lp_marks, usr_data, xxcf_data_settings "
."WHERE xxcf_data_settings.privacy_ident=2 "
."AND ut_lp_marks.obj_id = xxcf_data_settings.obj_id "
."AND usr_data.usr_id = ut_lp_marks.usr_id");

while ($row = $ilDB->fetchAssoc($set)) {
	$ilDB->insert('xxcf_users', array(
		'obj_id' => array('integer', $row['obj_id']),
		'usr_id' => array('integer', $row['usr_id']),
		'privacy_ident' => array('integer', 2),
		'usr_ident' => array('text', $row['login'])
		)
	);
}
?>
<#26>
<?php
// PRIVACY_IDENT_NUMERIC = 1;
$set = $ilDB->query("SELECT ut_lp_marks.obj_id, ut_lp_marks.usr_id "
."FROM ut_lp_marks, xxcf_data_settings "
."WHERE xxcf_data_settings.privacy_ident=1 "
."AND ut_lp_marks.obj_id = xxcf_data_settings.obj_id");

while ($row = $ilDB->fetchAssoc($set)) {
	$ilDB->insert('xxcf_users', array(
		'obj_id' => array('integer', $row['obj_id']),
		'usr_id' => array('integer', $row['usr_id']),
		'privacy_ident' => array('integer', 1),
		'usr_ident' => array('text', ''.$row['usr_id'].'@iliassecretuser.de')
		)
	);
}
?>
<#27>
<?php
// PRIVACY_IDENT_CODE = 0;
$iliasDomain = substr(ILIAS_HTTP_PATH,7);
if (substr($iliasDomain,0,1) == "\/") $iliasDomain = substr($iliasDomain,1);
if (substr($iliasDomain,0,4) == "www.") $iliasDomain = substr($iliasDomain,4);

$set = $ilDB->query("SELECT ut_lp_marks.obj_id, ut_lp_marks.usr_id "
."FROM ut_lp_marks, xxcf_data_settings "
."WHERE xxcf_data_settings.privacy_ident=0 "
."AND ut_lp_marks.obj_id = xxcf_data_settings.obj_id");

while ($row = $ilDB->fetchAssoc($set)) {
	$ident = ''.$row['usr_id'].'_'.str_replace('/','_',$iliasDomain).'_'.CLIENT_ID.'@iliassecretuser.de';
	$ilDB->insert('xxcf_users', array(
		'obj_id' => array('integer', $row['obj_id']),
		'usr_id' => array('integer', $row['usr_id']),
		'privacy_ident' => array('integer', 0),
		'usr_ident' => array('text', $ident)
		)
	);
}
?>
<#28>
<?php
if($ilDB->tableExists('xxcf_data_settings'))
{
    if ( $ilDB->tableColumnExists('xxcf_data_settings', 'show_debug') ) 
    {
        $ilDB->renameTableColumn('xxcf_data_settings', "show_debug", 'show_statements');
    }
}
?>
<#29>
<?php
/**
 * Plugin refactoring
 */
if ( !$ilDB->tableColumnExists('xxcf_users', 'registration') ) {
    $ilDB->addTableColumn('xxcf_users', 'registration', array(
        'type' => 'text',
        'length' => 255,
        'notnull' => true,
        'default' => ''
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'publisher_id') ) {
    $ilDB->addTableColumn('xxcf_data_settings', 'publisher_id', array(
        'type' => 'text',
        'length' => 255,
        'notnull' => true,
        'default' => ''
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'anonymous_homepage') ) {
    $ilDB->addTableColumn('xxcf_data_settings', 'anonymous_homepage', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 1
    ));
}
?>
<#30>
<?php
if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'moveon') ) {
    $ilDB->addTableColumn('xxcf_data_settings', 'moveon', array(
        'type' => 'text',
        'length' => 32,
        'notnull' => true,
        'default' => ''
    ));
}
?>
<#31>
<?php
if (!$ilDB->tableColumnExists('xxcf_data_token','cmi5_session')) {
    $ilDB->addTableColumn("xxcf_data_token", "cmi5_session", [
        'type' => 'text',
        'length' => 255,
        'notnull' => true,
        'default' => ''
    ]);
}
?>
<#32>
<?php
if (!$ilDB->tableColumnExists('xxcf_data_token','returned_for_cmi5_session')) {
    $ilDB->addTableColumn("xxcf_data_token", "returned_for_cmi5_session", [
        'type' => 'text',
        'length' => 255,
        'notnull' => true,
        'default' => ''
    ]);
}
?>
<#33>
<?php
if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'launch_parameters') ) {
    $ilDB->addTableColumn('xxcf_data_settings', 'launch_parameters', array(
        'type' => 'text',
        'length' => 255,
        'notnull' => true,
        'default' => ''
    ));
}
?>
<#34>
<?php
if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'entitlement_key') ) {
    $ilDB->addTableColumn('xxcf_data_settings', 'entitlement_key', array(
        'type' => 'text',
        'length' => 255,
        'notnull' => true,
        'default' => ''
    ));
}
?>
<#35>
<?php
if (!$ilDB->tableColumnExists('xxcf_data_token','cmi5_session_data')) {
    $ilDB->addTableColumn("xxcf_data_token", "cmi5_session_data", [
        'type' => 'clob'
    ]);
}
?>
<#36>
<?php
if ( !$ilDB->tableColumnExists('xxcf_users', 'satisfied') ) {
    $ilDB->addTableColumn('xxcf_users', 'satisfied', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ));
}
?>
<#37>
<?php
if ( !$ilDB->tableColumnExists('xxcf_data_settings', 'switch_to_review') ) {
    $ilDB->addTableColumn('xxcf_data_settings', 'switch_to_review', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 1
    ));
}
?>
<#38>
<?php
if ($ilDB->tableExists('xxcf_data_settings')) {
	$ilDB->renameTable('xxcf_data_settings', 'xxcf_settings');
}
if ($ilDB->tableExists('xxcf_data_token')) {
	$ilDB->renameTable('xxcf_data_token', 'xxcf_token');
}
if ($ilDB->tableExists('xxcf_data_types')) {
	$ilDB->renameTable('xxcf_data_types', 'xxcf_lrs_types');
}
?>
<#39>
<?php
if ($ilDB->tableColumnExists('xxcf_settings', 'use_fetch')) {
    $ilDB->renameTableColumn('xxcf_settings', "use_fetch", 'auth_fetch_url');
}
if ($ilDB->tableColumnExists('xxcf_settings', 'auth_fetch_url')) {
    $ilDB->modifyTableColumn('xxcf_settings','auth_fetch_url', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        )
    );
}
?>
<#40>
<?php
if ( !$ilDB->tableColumnExists('xxcf_settings', 'bypass_proxy') ) {
    $ilDB->addTableColumn('xxcf_settings', 'bypass_proxy', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_lrs_types', 'bypass_proxy') ) {
    $ilDB->addTableColumn('xxcf_lrs_types', 'bypass_proxy', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ));
}

?>
<#41>
<?php
if ( !$ilDB->tableColumnExists('xxcf_settings', 'content_type') ) {
    $ilDB->addTableColumn('xxcf_settings', 'content_type', array(
            'type' => 'text',
            'length' => 32,
            'notnull' => false
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'highscore_enabled') ) {
    $ilDB->addTableColumn('xxcf_settings', 'highscore_enabled', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'highscore_achieved_ts') ) {
    $ilDB->addTableColumn('xxcf_settings', 'highscore_achieved_ts', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'highscore_percentage') ) {
    $ilDB->addTableColumn('xxcf_settings', 'highscore_percentage', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'highscore_wtime') ) {
    $ilDB->addTableColumn('xxcf_settings', 'highscore_wtime', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'highscore_own_table') ) {
    $ilDB->addTableColumn('xxcf_settings', 'highscore_own_table', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'highscore_top_table') ) {
    $ilDB->addTableColumn('xxcf_settings', 'highscore_top_table', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'highscore_top_num') ) {
    $ilDB->addTableColumn('xxcf_settings', 'highscore_top_num', array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'keep_lp') ) {
    $ilDB->addTableColumn('xxcf_settings', 'keep_lp', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'launch_mode') ) {
    $ilDB->addTableColumn('xxcf_settings', 'launch_mode', array(
            'type' => 'text',
            'length' => 32,
            'notnull' => false
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_settings', 'launch_method') ) {
    $ilDB->addTableColumn('xxcf_settings', 'launch_method', array(
            'type' => 'text',
            'length' => 32,
            'notnull' => false
    ));
}



// migration of xxcf offline status
// $query = 'update object_data od set offline = ' .
    // '(select if( availability_type = 0,1,0) from xxcf_settings ' .
    // 'where obj_id = od.obj_id) where type = ' . $ilDB->quote('xxcf', 'text');
// $ilDB->manipulate($query);
?>
<#42>
<?php
//content_type -> generic
$query = 'update xxcf_settings set content_type = ' . $ilDB->quote('generic', 'text');
$ilDB->manipulate($query);
//launch_mode
$query = 'update xxcf_settings set launch_mode = ' . $ilDB->quote('Normal', 'text');
$ilDB->manipulate($query);
//launch_method
$query = 'update xxcf_settings set launch_method = ' . $ilDB->quote('iframe', 'text') . ' WHERE open_mode = 1';
$ilDB->manipulate($query);
$query = 'update xxcf_settings set launch_method = ' . $ilDB->quote('newWin', 'text') . ' WHERE open_mode = 0';
$ilDB->manipulate($query);

if ($ilDB->tableColumnExists('xxcf_settings', 'lp_threshold')) {
    $ilDB->renameTableColumn('xxcf_settings', 'lp_threshold', 'mastery_score');
}

if ($ilDB->tableColumnExists('xxcf_settings', 'mastery_score')) {
    $ilDB->modifyTableColumn('xxcf_settings','mastery_score', array(
            'type' => 'float',
            'notnull' => true,
            'default' => 0.0
        )
    );
}

if ($ilDB->tableColumnExists('xxcf_settings', 'type_id')) {
    $ilDB->renameTableColumn('xxcf_settings', 'type_id', 'lrs_type_id');
}

if ($ilDB->tableColumnExists('xxcf_settings', 'privacy_comment')) {
    $ilDB->renameTableColumn('xxcf_settings', 'privacy_comment', 'usr_privacy_comment');
}

if ( !$ilDB->tableColumnExists('xxcf_settings', 'source_type') ) {
    $ilDB->addTableColumn('xxcf_settings', 'source_type', array(
            'type' => 'text',
            'length' => 32,
            'notnull' => false
    ));
}
$query = 'update xxcf_settings set source_type = ' . $ilDB->quote('remoteSource', 'text');
$ilDB->manipulate($query);

if ( !$ilDB->tableColumnExists('xxcf_settings', 'xml_manifest') ) {
    $ilDB->addTableColumn('xxcf_settings', 'xml_manifest', array(
            'type' => 'clob'
    ));
}
if ($ilDB->tableColumnExists('xxcf_token', 'time')) {
    $ilDB->renameTableColumn('xxcf_token', 'time', 'valid_until');
}
if ( !$ilDB->tableColumnExists('xxcf_token', 'lrs_type_id') ) {
    $ilDB->addTableColumn('xxcf_token', 'lrs_type_id', array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true,
        'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_token', 'ref_id') ) {
    $ilDB->addTableColumn('xxcf_token', 'ref_id', array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true,
        'default' => 0
    ));
}
?>
<#43>
<?php
if ($ilDB->tableColumnExists('xxcf_lrs_types', 'lrs_endpoint_1')) {
    $ilDB->renameTableColumn('xxcf_lrs_types', 'lrs_endpoint_1', 'lrs_endpoint');
};
if ($ilDB->tableColumnExists('xxcf_lrs_types', 'lrs_key_1')) {
    $ilDB->renameTableColumn('xxcf_lrs_types', 'lrs_key_1', 'lrs_key');
};
if ($ilDB->tableColumnExists('xxcf_lrs_types', 'lrs_secret_1')) {
    $ilDB->renameTableColumn('xxcf_lrs_types', 'lrs_secret_1', 'lrs_secret');
}
?>
<#44>
<?php
if ($ilDB->tableColumnExists('xxcf_lrs_types', 'log_level')) {
    $ilDB->dropTableColumn('xxcf_lrs_types', 'log_level');
}
?>
<#45>
<?php
if ( !$ilDB->tableColumnExists('xxcf_users', 'proxy_success') ) {
    $ilDB->addTableColumn('xxcf_users', 'proxy_success', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ));
}
if ( !$ilDB->tableColumnExists('xxcf_users', 'fetched_until') ) {
    $ilDB->addTableColumn('xxcf_users', 'fetched_until', array(
        'type' => 'timestamp',
        'notnull' => false
    ));
}
?>
<#46>
<?php
if ($ilDB->tableColumnExists('xxcf_results', 'result')) {
    $ilDB->renameTableColumn('xxcf_results', 'result', 'score');
}
if ($ilDB->tableColumnExists('xxcf_results', 'time')) {
    $ilDB->renameTableColumn('xxcf_results', 'time', 'last_update');
}
if ($ilDB->tableColumnExists('xxcf_results', 'status')) {
    $ilDB->modifyTableColumn('xxcf_results','status', array(
        'type' => 'text',
        'length' => 255,
        'notnull' => true,
        'default' => ''
        )
    );
}
?>
<#47>
<?php
if ( !$ilDB->tableColumnExists('xxcf_settings', 'no_unallocatable_statements') ) {
    $ilDB->addTableColumn('xxcf_settings', 'no_unallocatable_statements', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ));
}

if ( !$ilDB->tableColumnExists('xxcf_lrs_types', 'no_unallocatable_statements') ) {
    $ilDB->addTableColumn('xxcf_lrs_types', 'no_unallocatable_statements', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ));
}
?>