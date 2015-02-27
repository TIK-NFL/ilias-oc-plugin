<#1>
<?php
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'is_online' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'series' => array(
		'type' => 'text',
		'length' => 1000,
		'fixed' => false,
		'notnull' => false
	),
	'mhretval' => array(
		'type' => 'text',
		'length' => 10,
		'fixed' => false,
		'notnull' => false
	)
);

$ilDB->createTable("rep_robj_xmh_data", $fields);
$ilDB->addPrimaryKey("rep_robj_xmh_data", array("id"));
?>
<#2>
<?php
$fields = array(
		'cfgkey' => array(
				'type' => 'text',
				'length' => 20,
				'notnull' => true
		),
		'cfgvalue' => array(
				'type' => 'text',
				'length' => 100,
				'fixed' => false,
				'notnull' => false
		)
);

$ilDB->createTable("rep_robj_xmh_config", $fields);
$ilDB->addPrimaryKey("rep_robj_xmh_config", array("cfgkey"));
?>
<#3>
<?php
	$ilDB->addTableColumn('rep_robj_xmh_data', 'lectureid', array(
		'type' => 'text',
		'length' => 20,
		'fixed' => false,
		'notnull' => false
));
?>
<#4>
<#5>
<?php
	$ilDB->addTableColumn('rep_robj_xmh_data', 'viewmode', array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false,
	        'default' => 0
));
?>
<#6>
<?php
  $ilDB->addTableColumn('rep_robj_xmh_data', 'manualrelease', array(
          'type' => 'integer',
          'length' => 1,
          'notnull' => false,
          'default' => 0
));
?>
<#7>
<?php
$fields = array(
                'episode_id' => array(
                                'type' => 'text',
                                'length' => 50,
                                'fixed' => false,
                                'notnull' => true
                ),
                'series_id' => array(
                                'type' => 'text',
                                'length' => 150,
                                'fixed' => false,
                                'notnull' => true
                )
);

$ilDB->createTable("rep_robj_xmh_rel_ep", $fields);
$ilDB->addPrimaryKey("rep_robj_xmh_rel_ep", array("episode_id"));
$ilDB->addIndex("rep_robj_xmh_rel_ep", array("series_id"),'ser');
?>
<#8>
<?php
  $ilDB->addTableColumn('rep_robj_xmh_data', 'fsinodupdate', array(
          'type' => 'integer',
          'length' => 8,
          'notnull' => false,
          'default' => 0
));
?>
