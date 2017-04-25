<?php
/*
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

include_once "./Services/Repository/classes/class.ilObjectPluginListGUI.php";

/**
* ListGUI implementation for Matterhorn object plugin. This one
* handles the presentation in container items (categories, courses, ...)
* together with the corresponfing ...Access class.
*
* PLEASE do not create instances of larger classes here. Use the
* ...Access class to get DB data and keep it small.
*
* @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
*/
class ilObjMatterhornListGUI extends ilObjectPluginListGUI
{
    
    /**
    * Init type
    */
    public function initType()
    {
        $this->setType("xmh");
    }
    
    /**
    * Get name of gui class handling the commands
    */
    public function getGuiClass()
    {
        return "ilObjMatterhornGUI";
    }
    
    /**
    * Get commands
    */
    public function initCommands()
    {
        return array(
            array(
                "permission" => "read",
                "cmd" => "showSeries",
                "default" => true),
            array(
                "permission" => "write",
                "cmd" => "editProperties",
                "txt" => $this->txt("edit"),
                "default" => false),
        );
    }

    /**
    * Get item properties
    *
    * @return	array		array of property arrays:
    *						"alert" (boolean) => display as an alert property (usually in red)
    *						"property" (string) => property name
    *						"value" (string) => property value
    */
    public function getProperties()
    {
        global $ilUser, $ilAccess;

        $props = array();

        $this->plugin->includeClass("class.ilObjMatterhornAccess.php");
        if (!ilObjMatterhornAccess::checkOnline($this->obj_id)) {
            $props[] = array("alert" => true, "property" => $this->txt("status"),
                "value" => $this->txt("offline"));

        }

        if ($a_user_id == "") {
            $a_user_id = $ilUser->getId();
        }

        if ($ilAccess->checkAccessOfUser($a_user_id, "write", "", $this->ref_id)) {
            $this->plugin->includeClass("class.ilObjMatterhorn.php");
            $this->object = new ilObjMatterhorn($this->ref_id);
            $onHoldEpisodes = $this->object->getOnHoldEpisodes();
            $tempEpisodes = $onHoldEpisodes['workflows'];
            if ( 0 < $tempEpisodes['totalCount']) {
                $props[] = array("alert" => false, "property" => $this->txt("to_edit"),
                        "value" =>  $tempEpisodes['totalCount']);
            }
        }

        return $props;
    }
}
