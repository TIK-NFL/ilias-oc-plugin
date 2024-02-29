<?php
namespace TIK_NFL\ilias_oc_plugin\api;

use JsonException;

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
    public function getMyInfo(): array
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
     * @throws JsonException
     */
    public function listPlugins(): array
    {
        $staticPluginsList = __DIR__. "../../../templates/theodul/manager/list.json";
        return json_decode(file_get_contents($staticPluginsList), true, 512, JSON_THROW_ON_ERROR);
    }
}