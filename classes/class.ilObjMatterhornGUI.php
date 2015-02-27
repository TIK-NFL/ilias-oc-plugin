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
			case "editEpisodes":
				$this->checkPermission("write");
				$this->$cmd();
				break;
			
			case "showSeries":			// list all commands that need read permission here
			case "showEpisode":
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
                        $ilTabs->addTab("manage", $this->txt("manage"), $ilCtrl->getLinkTarget($this, "editEpisodes"));
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

		// vorlesungsnummer
		$tl = new ilTextAreaInputGUI($this->txt("lectureID"), "lectureID");
		$this->form->addItem($tl);

		// viewmode
		$vm = new ilCheckboxInputGUI($this->txt("viewmode"), "viewMode");
		$this->form->addItem($vm);
		
		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$this->form->addItem($cb);
		
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
		$values["lectureID"] = $this->object->getLectureID();
		$values["online"] = $this->object->getOnline();
		$values["viewMode"] = $this->object->getViewMode();
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
			$this->object->setLectureID($this->form->getInput("lectureID"));				
			$this->object->setOnline($this->form->getInput("online"));
			$this->object->setViewMode($this->form->getInput("viewMode"));
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
		$this->checkPermission("read");
		$theodulbase = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul";

		
		$player = new ilTemplate("tpl.player.html", true, false, "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");

		$player->setVariable("INITJS",$theodulbase );
		
		$tpl->setContent($player->get());
		$ilTabs->activateTab("content");
		
	}
	
	function showSeries()
	{     

		global $tpl, $lng, $ilAccess, $ilTabs, $ilToolbar,$ilLog, $ilCtrl;
		
		$this->checkPermission("read");
		
	
		$this->plugin->includeClass("class.ilObjMatterhornTableSeriesGUI.php");
		

		$med_items = array();
		$totals = $this->object->getSearchResult()['total'];
		#$ilLog->write("Total:".print_r($this->object->getSearchResult(),true));
		
		$released  = $this->object->getReleasedEpisodes();
		
		
        foreach($this->object->getSearchResult()->mediapackage as $value) {
            if ($this->object->getManualRelease()){
                    if(! in_array($released, $value['id'])){
                            continue;
                    }
            }
            $previewurl = "unset";
            foreach ($value->attachments->attachment as $attachment){
                $ilLog->write("Attachment: ".print_r($attachment,true));
                if ('presentation/search+preview' ==  $attachment['type'] || 'presenter/search+preview' ==  $attachment['type'] ){
                    $ilLog->write("Setting preview url: ". $attachment->url);
                    $previewurl = $attachment->url;
                }
            }
            $ilLog->write("adding item result list:".$value['id']);

            $replacementString = substr($this->plugin->getStyleSheetLocation("css/xmh.css"),0,-21);
            $previewurl = str_replace($this->configObject->getMatterhornEngageServer()."/static/engage-player/",$replacementString."MHData/".CLIENT_ID."/".$this->object->getId()."/",$previewurl);
            $med_items[(string)$value['id']] = array(
                "title" => (string)$value->title,
                "date" => (string)$value['start'],
                "nr" => $key+1,
                "mhid" => $this->obj_id."/".(string)$value['id'],
                "previewurl" => $previewurl
            );
        }
		#$ilLog->write("Total:".print_r($med_items,true));
		uasort($med_items,array($this, 'sortbydate'));
		if ( ! $this->object->getViewMode() ) {
            $table_gui = new ilObjMatterhornTableSeriesGUI($this, "listItems");
			$table_gui->setDefaultOrderField("nr");
			$table_gui->setDefaultOrderDirection("asc");
			$table_gui->setData($med_items);
			$table_gui->setExternalSorting(true);
			$tpl->setContent($table_gui->getHTML());
		} else {		
			$tpl->addCss($this->plugin->getStyleSheetLocation("css/xmh.css"));
			$seriestpl = new ilTemplate("tpl.series.html", true, true,  "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
            foreach($med_items as $key => $item)
            {
                $ilLog->write("Adding: ".$item["title"]);
                $seriestpl->setCurrentBlock("variable");
					$ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $item['mhid']);
			                $seriestpl->setVariable("CMD_DOWNLOAD", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode"));
					$seriestpl->setVariable("PREVIEWURL", $item["previewurl"]);
			                $seriestpl->setVariable("TXT_TITLE", $item["title"]);
			                $seriestpl->setVariable("TXT_DATE", ilDatePresentation::formatDate(new ilDateTime($item["date"],IL_CAL_DATETIME)));
			                $seriestpl->setVariable("TXT_NR", $date["nr"]);
                                        $seriestpl->parseCurrentBlock();
                                }

			$html = $seriestpl->get();
			$tpl->setContent($html);
		}
		$tpl->setPermanentLink($this->object->getType(), $this->object->getRefId());
		$ilTabs->activateTab("content");
	}


	function sortbydate($a, $b) {
	    if ($a["date"] == $b["date"]) {
        	return 0;
	    }
	    return ($a["date"] < $b["date"]) ? -1 : 1;
	}

	
	function editEpisodes(){
          global $tpl, $lng, $ilAccess, $ilTabs, $ilToolbar,$ilLog;

          $this->checkPermission("write");

          $this->plugin->includeClass("class.ilObjMatterhornTableUpcommingGUI.php");

          $table_gui = new ilObjMatterhornTableUpcommingGUI($this, "listItems");

          $med_items = array();
          $totals = $this->object->searchResult['search-results']['total'];
#          $ilLog->write("Total:".print_r($this->object->searchResult,true));     
          
          foreach($this->object->searchResult['search-results']['result'] as $key => $value) {
                  $previewurl = "unset";
                  foreach ($value['mediapackage']['attachments']['attachment'] as $attachment){
                          if ('presentation/search+preview' ==  $attachment['type']){
                                  $previewurl = $attachment['url'];
                          }
                  }                                               
  //              $ilLog->write("adding item result list:".$key);         
                  $med_items[$key] = array(
                      "title" => $value['dcTitle'],
                      "date" => $value['dcCreated'],
                      "nr" => $key+1,
                      "mhid" => $this->obj_id."/".$value['id'],
                          "previewurl" => $previewurl
                  );
          }
          $table_gui->setDefaultOrderField("nr");
          $table_gui->setDefaultOrderDirection("asc");
          
          $table_gui->setData($med_items);
          
          $tpl->setContent($table_gui->getHTML());
          
          $tpl->setPermanentLink($this->object->getType(), $this->object->getRefId());
          $ilTabs->activateTab("manage");
	}
}
?>
