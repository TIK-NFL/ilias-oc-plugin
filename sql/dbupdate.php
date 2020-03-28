<#1>
<?php

const TABLE_DATA = 'rep_robj_xmh_data';

const TABLE_CONFIG = 'rep_robj_xmh_config';

const TABLE_RELEASED_EPISODES = 'rep_robj_xmh_rel_ep';

const TABLE_SLIDETEXT = 'rep_robj_xmh_slidetext';

const TABLE_VIEWS = 'rep_robj_xmh_views';

$fields = array(
    'obj_id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'is_online' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ),
    'viewmode' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ),
    'manualrelease' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ),
    'download' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ),
    'series_id' => array(
        'type' => 'text',
        'length' => 50,
        'fixed' => false,
        'notnull' => true
    )
);

$ilDB->createTable(TABLE_DATA, $fields);
$ilDB->addPrimaryKey(TABLE_DATA, array(
    "obj_id"
));

$ilDB->addIndex(TABLE_DATA, array(
    'series_id'
), 'ser');

// config
$fields = array(
    'cfgkey' => array(
        'type' => 'text',
        'length' => 30,
        'notnull' => true
    ),
    'cfgvalue' => array(
        'type' => 'text',
        'length' => 100,
        'fixed' => false,
        'notnull' => false
    )
);

$ilDB->createTable(TABLE_CONFIG, $fields);
$ilDB->addPrimaryKey(TABLE_CONFIG, array(
    "cfgkey"
));

// rel_ep
$fields = array(
    'episode_id' => array(
        'type' => 'text',
        'length' => 50,
        'fixed' => false,
        'notnull' => true
    ),
    'series_id' => array(
        'type' => 'text',
        'length' => 50,
        'fixed' => false,
        'notnull' => true
    )
);

$ilDB->createTable(TABLE_RELEASED_EPISODES, $fields);
$ilDB->addPrimaryKey(TABLE_RELEASED_EPISODES, array(
    "episode_id"
));
$ilDB->addIndex(TABLE_RELEASED_EPISODES, array(
    "series_id"
), 'ser');

// slidetext
$fields = array(
    'episode_id' => array(
        'type' => 'text',
        'length' => 50,
        'fixed' => false,
        'notnull' => true
    ),
    'slidetime' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true
    ),
    'slidetext' => array(
        'type' => 'text',
        'length' => 4000,
        'fixed' => false,
        'notnull' => true
    )
);

$ilDB->createTable(TABLE_SLIDETEXT, $fields);
$ilDB->addPrimaryKey(TABLE_SLIDETEXT, array(
    "episode_id",
    "slidetime"
));

// views
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true
    ),
    'user_id' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true
    ),
    'episode_id' => array(
        'type' => 'text',
        'length' => 50,
        'fixed' => false,
        'notnull' => true
    ),
    'intime' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true
    ),
    'outtime' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true
    )
);

$ilDB->createTable(TABLE_VIEWS, $fields);
$ilDB->manipulate("ALTER TABLE " . TABLE_VIEWS . " MODIFY COLUMN `id` BIGINT AUTO_INCREMENT primary key;");
?>
