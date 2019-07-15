<?php
use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;

include_once ("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast')->includeClass('class.ilOpencastConfig.php');

/**
 * Opencast repository object plugin
 *
 * @author Per Pascal Seeland <pascal.seeland@tik.uni-stuttgart.de>
 */
class ilOpencastPlugin extends ilRepositoryObjectPlugin
{

    public function getPluginName()
    {
        return "Opencast";
    }

    protected function uninstallCustom()
    {
        global $DIC;
        $ilDB = $DIC->database();
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_CONFIG);
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_DATA);
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_RELEASED_EPISODES);
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_SLIDETEXT);
        $ilDB->dropTable(ilOpencastConfig::DATABASE_TABLE_VIEWS);
    }
}
