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
$ilDB->addPrimaryKey("rep_robj_xmh_data", array(
    "id"
));
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
$ilDB->addPrimaryKey("rep_robj_xmh_config", array(
    "cfgkey"
));
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
$ilDB->addPrimaryKey("rep_robj_xmh_rel_ep", array(
    "episode_id"
));
$ilDB->addIndex("rep_robj_xmh_rel_ep", array(
    "series_id"
), 'ser');
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
<#9>
<?php

$ilDB->modifyTableColumn('rep_robj_xmh_data', 'manualrelease', array(
    'type' => 'integer',
    'length' => 1,
    'notnull' => true,
    'default' => 0
));
$ilDB->modifyTableColumn('rep_robj_xmh_data', 'fsinodupdate', array(
    'type' => 'integer',
    'length' => 8,
    'notnull' => true,
    'default' => 0
));
?>
<#10>
<?php
$ilDB->addTableColumn('rep_robj_xmh_data', 'download', array(
    'type' => 'integer',
    'length' => 8,
    'notnull' => false,
    'default' => 0
));
?>
<#11>
<?php
$ilDB->renameTableColumn('rep_robj_xmh_data', 'id', 'obj_id');
?>
<#12>
<#13>
<?php
$fields = array(
    'episode_id' => array(
        'type' => 'text',
        'length' => 50,
        'fixed' => false,
        'notnull' => true
    ),
    'series_id' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true
    ),
    'slidetime' => array(
        'type' => 'text',
        'length' => 100,
        'fixed' => false,
        'notnull' => true
    ),
    'slidetext' => array(
        'type' => 'text',
        'length' => 4000,
        'fixed' => false,
        'notnull' => true
    )
);

$ilDB->createTable("rep_robj_xmh_slidetext", $fields);
$ilDB->addPrimaryKey("rep_robj_xmh_slidetext", array(
    "episode_id",
    "slidetime"
));
?>

<#14>
<?php
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true
    ),
    'series_id' => array(
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
    'user_id' => array(
        'type' => 'integer',
        'length' => 8,
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

$ilDB->createTable("rep_robj_xmh_usrtrack", $fields);
// ignore that ILIAS does wont to have an extra autoinc table. This will only work in mysql, but I don't care about Oracle.
$ilDB->manipulate(" ALTER TABLE rep_robj_xmh_usrtrack MODIFY COLUMN `id` BIGINT AUTO_INCREMENT primary key; ");

?>
<#15>
<?php
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

const viewsTable = 'rep_robj_xmh_views';

$ilDB->createTable(viewsTable, $fields);
$ilDB->manipulate(" ALTER TABLE " . viewsTable . " MODIFY COLUMN `id` BIGINT AUTO_INCREMENT primary key; ");

ilLoggerFactory::getLogger('xmh')->info("Convert data from rep_robj_xmh_usrtrack table to rep_robj_xmh_views table data");
$tempTable = 'rep_robj_xmh_usrtrack';
$blocksize = 10000;

function getLastView($user_id, $episode_id)
{
    global $ilDB;
    
    $query = $ilDB->query("SELECT id, intime, outtime FROM " . viewsTable . " WHERE user_id = " . $ilDB->quote($user_id, "integer") . " AND episode_id LIKE " . $ilDB->quote($episode_id, "text") . " ORDER BY id DESC");
    
    if ($ilDB->numRows($query) == 0) {
        return [
            "intime" => - 1,
            "outtime" => 0
        ];
    } else {
        return $ilDB->fetchAssoc($query);
    }
}

function addViews($user_id, $episode_id, $views)
{
    global $ilDB;
    
    foreach ($views as $view) {
        if (array_key_exists("id", $view)) {
            $sql = "UPDATE " . viewsTable . " SET intime = " . $ilDB->quote($view["intime"], "integer") . ", outtime = " . $ilDB->quote($view["outtime"], "integer") . " WHERE id = " . $ilDB->quote($view["id"], "integer");
        } else {
            $sql = "INSERT INTO " . viewsTable . " (user_id, episode_id, intime, outtime) VALUES (" . $ilDB->quote($user_id, "integer") . ", " . $ilDB->quote($episode_id, "text") . ", " . $ilDB->quote($view["intime"], "integer") . ", " . $ilDB->quote($view["outtime"], "integer") . ")";
        }
        $ilDB->manipulate($sql);
    }
}

$paredRows = 0;

$sqlSelect = "SELECT * FROM `" . $tempTable . "` ORDER BY `id` ASC LIMIT " . $blocksize;
$sqlDelete = "DELETE FROM `" . $tempTable . "` ORDER BY `id` ASC LIMIT " . $blocksize;
while ($query = $ilDB->query($sqlSelect)) {
    $rowsNum = $ilDB->numRows($query);
    
    if ($rowsNum === 0) {
        ilLoggerFactory::getLogger('xmh')->info($paredRows . ' rows parsed');
        break;
    }
    ilLoggerFactory::getLogger('xmh')->info('Parsing ' . $rowsNum . ' rows. ' . $paredRows . ' rows parsed');
    
    $videos = array();
    
    while ($row = $ilDB->fetchAssoc($query)) {
        $episode_id = $row['episode_id'];
        $user_id = $row['user_id'];
        
        $videos[$episode_id][$user_id][] = $row;
    }
    
    foreach ($videos as $episode_id => $users) {
        foreach ($users as $user_id => $times) {
            // get last entry from DB
            $temp = getLastView($user_id, $episode_id);
            
            $userViews = array();
            
            foreach ($times as $time) {
                $intime = $time['intime'];
                $outtime = $time['outtime'];
                
                if ($intime < 0) {
                    // do nothing, if this is the first view of this episode from the user, -1 is added automatically
                } else {
                    if ($temp['intime'] < 0) {
                        // first FOOTPRINT after -1
                        $temp['intime'] = $intime;
                        $temp['outtime'] = $outtime;
                    } else {
                        if ($temp['outtime'] == $intime) {
                            // same view
                            $temp['outtime'] = $outtime;
                        } else {
                            $userViews[] = $temp;
                            
                            $temp = [
                                'intime' => $intime,
                                'outtime' => $outtime
                            ];
                        }
                    }
                }
            }
            $userViews[] = $temp;
            
            // add the new view to DB
            addViews($user_id, $episode_id, $userViews);
        }
    }
    
    $queryDelete = $ilDB->manipulate($sqlDelete);
    
    $paredRows += $rowsNum;
}

// delete rep_robj_xmh_usrtrack table
$ilDB->dropTable("rep_robj_xmh_usrtrack");
?>
<#16>
<?php
$ilDB->manipulate('UPDATE rep_robj_xmh_config SET cfgkey = ' . $ilDB->quote('distribution_directory', 'text') . ' WHERE cfgkey = ' . $ilDB->quote('xsendfile_basedir', 'text'));
$ilDB->manipulate('UPDATE rep_robj_xmh_config SET cfgkey = ' . $ilDB->quote('mh_directory', 'text') . ', cfgvalue = REPLACE(cfgvalue, "/files", "") WHERE cfgkey = ' . $ilDB->quote('mh_files_directory', 'text'));
$ilDB->modifyTableColumn('rep_robj_xmh_slidetext', 'slidetime', array(
    'type' => 'integer',
    'length' => 8,
    'notnull' => true
));
?>
<#17>
<?php
$ilDB->manipulate("ALTER TABLE rep_robj_xmh_config CHANGE `cfgkey` `cfgkey` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
$ilDB->manipulate('UPDATE rep_robj_xmh_config SET cfgkey = ' . $ilDB->quote('distribution_directory', 'text') . ' WHERE cfgkey = ' . $ilDB->quote('distribution_direto', 'text'));
?>
