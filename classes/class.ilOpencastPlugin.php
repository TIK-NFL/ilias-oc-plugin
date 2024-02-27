<?php
use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Opencast repository object plugin
 *
 * @author Per Pascal Seeland <pascal.seeland@tik.uni-stuttgart.de>
 */
class ilOpencastPlugin extends ilRepositoryObjectPlugin
{

    public function getPluginName() : string
    {
        return "Opencast";
    }

    protected function uninstallCustom() : void
    {
        global $DIC;
        $ilDB = $DIC->database();
        $this->includeClass('class.ilOpencastConfig.php');
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_CONFIG);
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_DATA);
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_RELEASED_EPISODES);
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_SLIDETEXT);
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_VIEWS);
    }
}
