<?php
namespace TIK_NFL\ilias_oc_plugin\api;

use ilPlugin;

/**
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastInfo
{

    /**
     * Information about the current user for the player.
     *
     * @return array
     */
    public function getMyInfo()
    {
        return array(
            "username" => "anonymous",
            "roles" => array(
                "ROLE_ANONYMOUS"
            ),
            "org" => array(
                "id" => "mh_default_org",
                "name" => "Opencast Project",
                "adminRole" => "ROLE_ADMIN",
                "anonymousRole" => "ROLE_ANONYMOUS"
            )
        );
    }

    /**
     * Returns the list of all registered Engage Player plugins.
     *
     * @return array
     */
    public function listPlugins()
    {
        $staticPluginsList = ilPlugin::_getDirectory(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast') . "/templates/theodul/manager/list.json";
        return json_decode(file_get_contents($staticPluginsList));
    }
}