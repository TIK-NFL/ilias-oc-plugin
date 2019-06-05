<?php

/**
 * 
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilMatterhornInfo
{

    /**
     * Information about the curent user
     *
     * @return array
     */
    public function getMyInfo()
    {
        $json = array();

        $jsonUser = array();
        $jsonUser["username"] = "anonymous";
        $json["user"] = $jsonUser;

        $roles = array();
        $roles[] = "ROLE_ANONYMOUS";
        $json["rules"] = $roles;

        $jsonOrg = array();
        $jsonOrg["id"] = "mh_default_org";
        $jsonOrg["name"] = "Opencast Project";
        $jsonOrg["adminRole"] = "ROLE_ADMIN";
        $jsonOrg["anonymousRole"] = "ROLE_ANONYMOUS";
        $json["org"] = $jsonOrg;

        return $json;
    }

    /**
     * Returns the list of all registered Engage Player plugins.
     *
     * @return array
     */
    public function listPlugins()
    {
        $staticPluginsList = ilPlugin::_getDirectory(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn') . "/templates/theodul/manager/list.json";
        $json = json_decode(file_get_contents($staticPluginsList));

        return $json;
    }
}