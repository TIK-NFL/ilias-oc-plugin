<?php
/**
 +-----------------------------------------------------------------------------+
 | ILIAS open source                                                           |
 +-----------------------------------------------------------------------------+
 | Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
 |                                                                             |
 | This program is free software; you can redistribute it and/or               |
 | modify it under the terms of the GNU General Public License                 |
 | as published by the Free Software Foundation; either version 2              |
 | of the License, or (at your option) any later version.                      |
 |                                                                             |
 | This program is distributed in the hope that it will be useful,             |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
 | GNU General Public License for more details.                                |
 |                                                                             |
 | You should have received a copy of the GNU General Public License           |
 | along with this program; if not, write to the Free Software                 |
 | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
 +-----------------------------------------------------------------------------+
 */
use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;
use TIK_NFL\ilias_oc_plugin\model\ilOpencastEpisode;

//include_once ("./Services/Repository/classes/class.ilObjectPluginAccess.php");

/**
 * Access/Condition checking for Opencast object
 *
 * Please do not create instances of large application classes (like ilObjOpencast)
 * Write small methods within this class to determin the status.
 *
 * @author Per Pascal Seeland <pascal.seeland@tik.uni-stuttgart.de>
 */
class ilObjOpencastAccess extends ilObjectPluginAccess
{

    /**
     * Checks wether a user may invoke a command or not
     * (this method is called by ilAccessHandler::checkAccess)
     *
     * Please do not check any preconditions handled by
     * ilConditionHandler here. Also don't do usual RBAC checks.
     *
     * @param string $cmd
     *            command (not permission!)
     * @param string $permission
     *            permission
     * @param int $ref_id
     *            reference id
     * @param int $obj_id
     *            object id
     * @param ?int $user_id
     *            user id (if not provided, current user is taken)
     *
     * @return boolean true, if everything is ok
     */
    public function _checkAccess(string $cmd, string $permission, int $ref_id, int $obj_id, ?int $user_id = null) : bool
    {
        global $DIC;
        $ilUser = $DIC->user();
        $ilAccess = $DIC->access();

        if ($user_id === 0) {
            $user_id = $ilUser->getId();
        }

        switch ($permission) {
            case "visible":
            case "read":
                if (! self::checkOnline($obj_id) && ! $ilAccess->checkAccessOfUser($user_id, "write", "", $ref_id)) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Check online status of example object
     */
    public static function checkOnline(int $a_id) : bool
    {
        global $DIC;
        $ilDB = $DIC->database();

        $set = $ilDB->query("SELECT is_online FROM " . ilOpencastConfig::DATABASE_TABLE_DATA . " WHERE obj_id = " . $ilDB->quote($a_id, "integer"));
        $rec = $ilDB->fetchAssoc($set);
        return (boolean) $rec["is_online"];
    }

    private static function lookupOpencastObjectForSeries(string $series_id)
    {
        return (new ilOpencastConfig())->lookupOpencastObjectForSeries($series_id);
    }

    /**
     * Check access rights of the requested file
     *
     * @param ilOpencastEpisode $episode
     * @param string $permission
     * @throws Exception if user have no $permission access for the file of the episode
     */
    public static function checkEpisodeAccess(ilOpencastEpisode $episode, string $permission = "read"): void
    {
        global $DIC;
        if (self::checkAccessObject(self::lookupOpencastObjectForSeries($episode->getSeriesId()), $permission)) {
            return;
        }
        // none of the checks above gives access
        throw new Exception($DIC->language()->txt('msg_no_perm_read'), 403);
    }

    /**
     * Check access rights of the requested preview of the file
     *
     * @param ilOpencastEpisode $episode
     * @throws Exception if user have no access rights for the preview
     */
    public static function checkPreviewAccess(ilOpencastEpisode $episode): void
    {
        self::checkFileAccess($episode);
    }

    /**
     * Check access rights of the requested file
     *
     * @param ilOpencastEpisode $episode
     * @throws Exception if user have no access rights for the file
     */
    public static function checkFileAccess(ilOpencastEpisode $episode): void
    {
        global $DIC;
        if (self::checkAccessObject(self::lookupOpencastObjectForSeries($episode->getSeriesId()))) {
            return;
        }
        // none of the checks above gives access
        throw new Exception($DIC->language()->txt('msg_no_perm_read'), 403);
    }

    /**
     * Check access rights for an object by its object id
     *
     * @param int $obj_id
     *            object id
     * @param string $permission
     *            read/write
     * @return boolean access given (true/false)
     */
    private static function checkAccessObject(int $obj_id, string $permission = 'read'): bool
    {
        global $DIC;
        $ilUser = $DIC->user();
        $ilAccess = $DIC->access();

        $obj_type = ilObject::_lookupType($obj_id);
        $ref_ids = ilObject::_getAllReferences($obj_id);
        foreach ($ref_ids as $ref_id) {
            if ($ilAccess->checkAccessOfUser($ilUser->getId(), $permission, 'view', $ref_id, $obj_type, $obj_id)) {
                return true;
            }
        }
        return false;
    }
}
