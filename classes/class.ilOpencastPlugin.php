<?php
include_once ("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");

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
        /**
         *
         * @var $ilDB ilDB
         */
        global $ilDB;
        $ilDB->query('DROP TABLE  rep_robj_xoc_config, 
                                  rep_robj_xoc_data,
                                  rep_robj_xoc_rel_ep,
                                  rep_robj_xoc_slidetext,
                                  rep_robj_xoc_views');
    }
}
