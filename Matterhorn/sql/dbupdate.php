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
	'option_one' => array(
		'type' => 'text',
		'length' => 10,
		'fixed' => false,
		'notnull' => false
	),
	'option_two' => array(
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
<#3>
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
