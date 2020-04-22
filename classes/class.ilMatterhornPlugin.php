<?php
require_once __DIR__ . '/../vendor/autoload.php';


/**
 * Matterhorn repository object plugin
 *
 * @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
 */
class ilMatterhornPlugin extends ilRepositoryObjectPlugin
{

    public function getPluginName()
    {
        return "Matterhorn";
    }

    protected function uninstallCustom()
    {
        /**
         *
         * @var $ilDB ilDB
         */
        global $ilDB;
        $ilDB->query('DROP TABLE  rep_robj_xmh_config, 
                                  rep_robj_xmh_data,
                                  rep_robj_xmh_rel_ep,
                                  rep_robj_xmh_slidetext,
                                  rep_robj_xmh_views');
    }
}
