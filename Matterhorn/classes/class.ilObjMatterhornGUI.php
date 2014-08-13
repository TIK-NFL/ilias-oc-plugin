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


include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");

/**
* User Interface class for Matterhorn repository object.
*
* User interface classes process GET and POST parameter and call
* application classes to fulfill certain tasks.
*
* @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
*
* $Id$
*
* Integration into control structure:
* - The GUI class is called by ilRepositoryGUI
* - GUI classes used by this class are ilPermissionGUI (provides the rbac
*   screens) and ilInfoScreenGUI (handles the info screen).
*
* @ilCtrl_isCalledBy ilObjMatterhornGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjMatterhornGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
* @ilCtrl_Calls ilObjMatterhornGUI: ilCommonActionDispatcherGUI
*
*/
class ilObjMatterhornGUI extends ilObjectPluginGUI
{
	/**
	* Initialisation
	*/
	protected function afterConstructor()
	{
		
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilMatterhornConfig.php");
		$this->configObject = new ilMatterhornConfig();
	}
	
	/**
	* Get type.
	*/
	final function getType()
	{
		return "xmh";
	}
	
	/**
	* Handles all commmands of this class, centralizes permission checks
	*/
	function performCommand($cmd)
	{
		switch ($cmd)
		{
			case "editProperties":		// list all commands that need write permission here
			case "updateProperties":
			//case "...":
				$this->checkPermission("write");
				$this->$cmd();
				break;
			
			case "showSeries":			// list all commands that need read permission here
			//case "...":
			//case "...":
				$this->checkPermission("read");
				$this->$cmd();
				break;
			case "showEpisode":			// list all commands that need read permission here
			//case "...":
			//case "...":
				$this->checkPermission("read");
				$this->$cmd();
				break;
				
		}
	}

	/**
	* After object has been created -> jump to this command
	*/
	function getAfterCreationCmd()
	{
		return "editProperties";
	}

	/**
	* Get standard command
	*/
	function getStandardCmd()
	{
		return "showSeries";
	}
	
//
// DISPLAY TABS
//
	
	/**
	* Set tabs
	*/
	function setTabs()
	{
		global $ilTabs, $ilCtrl, $ilAccess;
		
		// tab for the "show content" command
		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showSeries"));
		}

		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}
	


//
// Edit properties form
//

	/**
	* Edit Properties. This commands uses the form class to display an input form.
	*/
	function editProperties()
	{
		global $tpl, $ilTabs;
		
		$ilTabs->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$tpl->setContent($this->form->getHTML());
	}
	
	/**
	* Init  form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPropertiesForm()
	{
		global $ilCtrl;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
	
		// title
		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);
		
		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);
		
		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$this->form->addItem($cb);
		
		// option 1
		$ti = new ilTextInputGUI($this->txt("option_one"), "op1");
		$ti->setMaxLength(10);
		$ti->setSize(10);
		$this->form->addItem($ti);
		
		// option 2
		$ti = new ilTextInputGUI($this->txt("option_two"), "op2");
		$ti->setMaxLength(10);
		$ti->setSize(10);
		$this->form->addItem($ti);

		$this->form->addCommandButton("updateProperties", $this->txt("save"));
	                
		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}
	
	/**
	* Get values for edit properties form
	*/
	function getPropertiesValues()
	{
		$values = array();
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["online"] = $this->object->getOnline();
		$values["op1"] = $this->object->getOptionOne();
		$values["op2"] = $this->object->getOptionTwo();
		$this->form->setValuesByArray($values);
	}
	
	/**
	* Update properties
	*/
	public function updateProperties()
	{
		global $tpl, $lng, $ilCtrl;
	
		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setOptionOne($this->form->getInput("op1"));
			$this->object->setOptionTwo($this->form->getInput("op2"));
			$this->object->setOnline($this->form->getInput("online"));
			$this->object->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}
	
//
// Show content
//

	/**
	* Show content
	*/
	function showEpisode()
	{                
		global $tpl, $ilTabs;
		$theodulbase = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul";

		$player = new ilTemplate("tpl.player.html", true, false, "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");

		$player->setVariable("INITJS",$theodulbase );
		
		$tpl->setContent($player->get());
		$ilTabs->activateTab("content");
		
	}
	
	function showSeries()
	{     

		global $tpl, $lng, $ilAccess, $ilTabs, $ilToolbar;
		
		$this->checkPermission("read");
		
	
		$this->plugin->includeClass("class.ilObjMatterhornTableGUI.php");
		
		$table_gui = new ilObjMatterhornTableGUI($this, "listItems");

		$med_items = array();
		foreach($this->object->searchResult['search-results']['result'] as $key => $value) {		
				$med_items[$key] = [
		    "title" => $value['dcTitle'],
		    "nr" => $key+1,
		    "mhid" => $this->obj_id."/".$value['id']
				];
		}		

		$table_gui->setDefaultOrderField("nr");
		$table_gui->setDefaultOrderDirection("asc");
		
		$table_gui->setData($med_items);
		
		$tpl->setContent($table_gui->getHTML());
		
		$tpl->setPermanentLink($this->object->getType(), $this->object->getRefId());
		$ilTabs->activateTab("content");
	}
}
?>
