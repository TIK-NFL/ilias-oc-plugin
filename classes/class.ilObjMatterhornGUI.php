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
			case "trimEpisode":
			case "showTrimEditor":
			case "publish":
			case "retract":
				$this->checkPermission("write");
				$this->$cmd();
				break;
			
			case "showSeries":			// list all commands that need read permission here
			case "showEpisode":
				$this->checkPermission("read");
				$this->$cmd();
				break;
            default:
                $this->checkPermission("read");
                $this->showSeries();
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

		// release episodes individually
        $mr = new ilCheckboxInputGUI($this->txt("manualRelease"), "manualRelease");
        $this->form->addItem($mr);

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
        global $ilLog;
		$values = array();
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["lectureID"] = $this->object->getLectureID();
		$values["online"] = $this->object->getOnline();
		$values["viewMode"] = $this->object->getViewMode();
        $values["manualRelease"] = $this->object->getManualRelease();
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
			$this->object->setManualRelease($this->form->getInput("manualRelease"));
			$this->object->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}

    public function publish()
    {
        global $tpl, $lng, $ilCtrl, $ilLog;
        $ilLog->write("ID:".$_GET["id"]);
        if (preg_match('/^[0-9a-f\-]+/', $_GET["id"])) {            
            $this->object->publish($_GET["id"]);
            ilUtil::sendSuccess($this->txt("msg_episode_published"), true);
        } else {
            $ilLog->write("ID does not match in publish episode:".$_GET["id"]);
        }
        $ilCtrl->redirect($this, "editEpisodes");
    }

    public function retract()
    {
        global $tpl, $lng, $ilCtrl, $ilLog;
        $ilLog->write("ID:".$_GET["id"]);
        if (preg_match('/^[0-9a-f\-]+/', $_GET["id"])) {            
            $this->object->retract($_GET["id"]);
            ilUtil::sendSuccess($this->txt("msg_episode_retracted"), true);
        } else {
            $ilLog->write("ID does not match in retract episode:".$_GET["id"]);
        }
        $ilCtrl->redirect($this, "editEpisodes");
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
		$temptotals = $this->object->getSearchResult();
		$totals = $temptotals['total'];
		#$ilLog->write("Total:".print_r($this->object->getSearchResult(),true));
		
		$released  = $this->object->getReleasedEpisodes();
		
        foreach($this->object->getSearchResult()->mediapackage as $value) {
            if ($this->object->getManualRelease()){
                    if(! in_array($value['id'],$released)){
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

	    function sortbystartdate($a, $b) {
        if ($a["startdate"] == $b["startdate"]) {
            return 0;
        }
        return ($a["startdate"] < $b["startdate"]) ? -1 : 1;
    }

	
	function editEpisodes(){
        global $tpl, $lng, $ilAccess, $ilTabs, $ilToolbar,$ilLog, $ilCtrl;

        $this->checkPermission("write");

        $released  = $this->object->getReleasedEpisodes();
        
        $med_items = array();
        $temptotals = $this->object->getSearchResult();
        $totals = $temptotals['total'];
        foreach($this->object->getSearchResult()->mediapackage as $value) {
            $previewurl = "unset";
            foreach ($value->attachments->attachment as $attachment){
          #      $ilLog->write("Attachment: ".print_r($attachment,true));
                if ('presentation/search+preview' ==  $attachment['type'] || 'presenter/search+preview' ==  $attachment['type'] ){
                    $ilLog->write("Setting preview url: ". $attachment->url);
                    $previewurl = $attachment->url;
                }
            }
            $ilLog->write("adding item to edit list:".$value['id']);         
            $med_items[(string)$value['id']] = array(
                "title" => (string)$value->title,
                "date" => (string)$value['start'],
                "nr" => $key+1,
                "mhid" => $this->obj_id."/".(string)$value['id'],
                "published" => in_array($value['id'],$released),
                "previewurl" => $previewurl
            );
        }
        uasort($med_items,array($this, 'sortbydate'));
        $scheduled_items = array();
        #$ilLog->write(print_r($this->object->getUpcommingEpisodes(),true));
        $scheduledEpisodes = $this->object->getScheduledEpisodes();
        $tempEpisodes = $scheduledEpisodes['workflows'];
        if(is_array($tempEpisodes) && 0 < $tempEpisodes['totalCount']){
            if(1 == $tempEpisodes['totalCount']){
                $workflow = $tempEpisodes['workflow'];
                $workflowid = $workflow['id'];
                $temparray = array( 
                    'title' => $workflow["mediapackage"]['title'],
                    'mhid' => $workflow['id'],
                    );
                $scheduled_items[$workflowid] = $temparray;
                $tempworkflow = $workflow['configurations']['configuration'];
                foreach($tempworkflow as $configuration){
                    switch ($configuration['key']) {
                        case 'schedule.start':
                            $scheduled_items[$workflow['id']]['startdate'] = $configuration['$']/1000;
                            continue;
                        case 'schedule.stop':
                            $scheduled_items[$workflow['id']]['stopdate'] = $configuration['$']/1000;
                            continue;
                        case 'schedule.location':
                            $scheduled_items[$workflow['id']]['location'] = $configuration['$'];
                            continue;
                    }
                }
            }else {
                foreach($tempEpisodes['workflow'] as $workflow) {
                    $ilLog->write("adding scheduled episodes to list:".$workflow['id']);         
                    $workflowid = $workflow['id'];
                    $temparray = array( 
                        'title' => $workflow["mediapackage"]['title'],
                        'mhid' => $workflow['id'],
                        );
                    $scheduled_items[$workflowid] = $temparray;
                    $tempworkflow = $workflow['configurations']['configuration'];
                    foreach($tempworkflow as $configuration){
                        switch ($configuration['key']) {
                            case 'schedule.start':
                                $scheduled_items[$workflow['id']]['startdate'] = $configuration['$']/1000;
                                continue;
                            case 'schedule.stop':
                                $scheduled_items[$workflow['id']]['stopdate'] = $configuration['$']/1000;
                                continue;
                            case 'schedule.location':
                                $scheduled_items[$workflow['id']]['location'] = $configuration['$'];
                                continue;
                        }
                    }

                }
            }
        }
        uasort($scheduled_items,array($this, 'sortbydate'));
        $onhold_items = array();
        #$ilLog->write(print_r($this->object->getUpcommingEpisodes(),true));
        $onHoldEpisodes = $this->object->getOnHoldEpisodes();
        $tempEpisodes = $onHoldEpisodes['workflows'];
        if(is_array($tempEpisodes) && 0 < $tempEpisodes['totalCount']){
            if(1 == $tempEpisodes['totalCount']){
                $workflow = $tempEpisodes['workflow'];
                $workflowid = $workflow['id'];
                $temparray = array( 
                    'title' => $workflow["mediapackage"]['title'],
                    'mhid' => $workflow['id'],
                    'recorddate' => $workflow["mediapackage"]['start'],
                    );
                //$ilLog->write(print_r($workflow["mediapackage"],true));                
                $onhold_items[$workflowid] = $temparray;
            }else {
                foreach($tempEpisodes['workflow'] as $workflow) {
                    $ilLog->write("adding scheduled episodes to list:".$workflow['id']);         
                    $workflowid = $workflow['id'];
                    $temparray = array( 
                        'title' => $workflow["mediapackage"]['title'],
                        'mhid' => $workflow['id'],
                        'recorddate' => $workflow["mediapackage"]['start'],
                        );                    
                    $onhold_items[$workflowid] = $temparray;
                }
            }
        }
        
        uasort($onhold_items,array($this, 'sortbydate'));

        $tpl->addCss($this->plugin->getStyleSheetLocation("css/xmh.css"));
        $seriestpl = new ilTemplate("tpl.series.edit.html", true, true,  "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
        $seriestpl->setCurrentBlock("headerfinished");
        $seriestpl->setVariable("TXT_FINISHED_RECORDINGS", $this->getText("finished_recordings"));
        $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
        $seriestpl->setVariable("TXT_PREVIEW", $this->getText("preview"));
        $seriestpl->setVariable("TXT_DATE", $this->getText("date"));
        $seriestpl->setVariable("TXT_ACTION", $this->getText("action"));
        $seriestpl->parseCurrentBlock();
        if(count($med_items) > 0 ){
            foreach($med_items as $key => $item)
            {
                $ilLog->write("Adding: ".$item["title"]);
                $seriestpl->setCurrentBlock("finished");
                $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $item['mhid']);
                $seriestpl->setVariable("CMD_DOWNLOAD", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode"));
                $seriestpl->setVariable("PREVIEWURL", $item["previewurl"]);
                $seriestpl->setVariable("TXT_EPISODE_TITLE", $item["title"]);            
                $seriestpl->setVariable("TXT_EPISODE_DATE", ilDatePresentation::formatDate(new ilDateTime($item["date"],IL_CAL_DATETIME)));
                $seriestpl->setVariable("TXT_NR", $date["nr"]);
                $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $key);
                $seriestpl->setVariable("CMD_PUBLISH", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", $item["published"]?"retract":"publish"));
                $seriestpl->setVariable("TXT_PUBLISH",$this->getText($item["published"]?"retract":"publish"));
                $seriestpl->parseCurrentBlock();
            }
        } else {
            $seriestpl->setCurrentBlock("nonefinished");
            $seriestpl->setVariable("TXT_NONE_FINISHED", $this->getText("none_finished"));
            $seriestpl->parseCurrentBlock();
        }
        $seriestpl->touchblock("footerfinished");

        $seriestpl->setCurrentBlock("headeronhold");
        $seriestpl->setVariable("TXT_ONHOLD_RECORDING", $this->getText("onhold_recordings"));
        $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
        $seriestpl->setVariable("TXT_RECORDDATE", $this->getText("recorddate"));
        $seriestpl->parseCurrentBlock();
        if(count($onhold_items) > 0 ){
            
            foreach($onhold_items as $key => $item)
            {
                $ilLog->write("Adding: ".$item["title"]);
                $seriestpl->setCurrentBlock("onhold");
                $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $item['mhid']);
                $seriestpl->setVariable("CMD_TRIM", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showTrimEditor"));
                $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $item['mhid']);
                $seriestpl->setVariable("TXT_EPISODE_TITLE", $item["title"]);
                $seriestpl->setVariable("TXT_EPISODE_RECORDDATE", ilDatePresentation::formatDate(new ilDateTime($item["recorddate"],IL_CAL_DATETIME)));
                $seriestpl->parseCurrentBlock();
            }
        } else {
            $seriestpl->setCurrentBlock("noneonhold");
            $seriestpl->setVariable("TXT_NONE_ONHOLD", $this->getText("none_onhold"));            
            $seriestpl->parseCurrentBlock();
        }
        $seriestpl->touchblock("footeronhold");

        
        $seriestpl->setCurrentBlock("headerscheduled");
        $seriestpl->setVariable("TXT_SCHEDULED_RECORDING", $this->getText("scheduled_recordings"));
        $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
        $seriestpl->setVariable("TXT_STARTDATE", $this->getText("startdate"));
        $seriestpl->setVariable("TXT_ENDDATE", $this->getText("enddate"));
        $seriestpl->setVariable("TXT_LOCATION", $this->getText("location"));          
        $seriestpl->parseCurrentBlock();
        if(count($scheduled_items) > 0 ){
            
            foreach($scheduled_items as $key => $item)
            {
                $ilLog->write("Adding: ".$item["title"]);
                $seriestpl->setCurrentBlock("scheduled");
                $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $item['mhid']);
                $seriestpl->setVariable("TXT_EPISODE_TITLE", $item["title"]);
                $seriestpl->setVariable("TXT_EPISODE_STARTDATE", ilDatePresentation::formatDate(new ilDateTime($item["startdate"],IL_CAL_UNIX)));
                $seriestpl->setVariable("TXT_EPISODE_STOPDATE", ilDatePresentation::formatDate(new ilDateTime($item["stopdate"],IL_CAL_UNIX)));
                $seriestpl->setVariable("TXT_EPISODE_LOCATION",$item["location"]);
                $seriestpl->parseCurrentBlock();
            }
        } else {
            $seriestpl->setCurrentBlock("nonescheduled");
            $seriestpl->setVariable("TXT_NONE_SCHEDULED", $this->getText("none_scheduled"));            
            $seriestpl->parseCurrentBlock();
        }
        $seriestpl->touchblock("footerscheduled");
        

        $html = $seriestpl->get();
        $tpl->setContent($html);
        
        $tpl->setPermanentLink($this->object->getType(), $this->object->getRefId());
        $ilTabs->activateTab("manage");
	}
	
    
    /**$trimview->setVariable("TXT_LEFT_TRACK", $this->getText("startdate"));
     * Show the trim episode Page
     */
    function showTrimEditor()
    {        
        global $tpl, $ilTabs, $ilCtrl,$ilLog, $ilUser;
        $this->checkPermission("write");
        $trimbase = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/trim";

        if (preg_match('/^[0-9a-f\-]+/', $_GET["id"])) {
            $workflow = $this->object->getWorkflow($_GET["id"]);

//            $ilLog->write("workflow:".print_r($workflow->workflow,true));
            $namespaces = $workflow->getNamespaces(true);
            $ilLog->write("namespaces: ". print_r($namespaces,true));
            $mediapackage = $workflow->children($namespaces['ns3'])->mediapackage; 
  //          $ilLog->write("mediapackage: ". print_r($mediapackage,true));
//             $series = simplexml_load_string($this->object->getSeries());
            if (!strpos($this->object->getSeries(),trim($mediapackage->series))) {
              $ilLog->write("series: ".$mediapackage->series);
              $ilCtrl->redirect($this, "editEpisodes");
            }
            $previewtrack;
            $worktracks = array();            
            foreach($mediapackage->media->track as $track){
//                $ilLog->write("mediapackage: ". print_r($track,true));
//                foreach($track->attributes() as $a => $b) {
//                    $ilLog->write("attributes: ". $a."=".$track[$a]);
//                }
                if("composite/iliaspreview" === (string)$track->attributes()->{'type'}){
                    $previewtrack = $track;
                }
                if(false !== strpos($track->attributes()->{'type'},"preview")){
                   if (!isset($previewtrack)){
                      $previewtrack = $track;
                   }
                }
                if(false !== strpos($track->attributes()->{'type'},"work")){
                    array_push($worktracks,$track);                    
                }
            }       
            //$ilLog->write("mediapackage: ". print_r($track,true ));
            $_SESSION["mhpreviewurl".$_GET["id"]] = (string)$previewtrack->url;
            
            $trimview = new ilTemplate("tpl.trimview.html", true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
            $trimview->setCurrentBlock("formstart");
            $trimview->setVariable("TXT_ILIAS_TRIM_EDITOR", $this->getText("ilias_trim_editor"));
            $trimview->setVariable("TXT_DOWNLOAD_PREVIEW", $this->getText("download_preview"));
            $trimview->setVariable("TXT_TRACK_TITLE", $this->getText("track_title"));
//            $ilCtrl->setParameter($this, "id", $_GET["id"]);
            $trimview->setVariable("WFID",$_GET["id"]);
            $trimview->setVariable("CMD_TRIM", $ilCtrl->getFormAction($this, "trimEpisode"));
            $trimview->setVariable("TRACKTITLE",$mediapackage->title);
            $downloadurl = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData/".CLIENT_ID."/".trim($mediapackage->series)."/".$_GET["id"]."/preview.mp4";
            $trimview->setVariable("DOWNLOAD_PREVIEW_URL", $downloadurl);
            $trimview->setVariable("INITJS",$trimbase );
            $trimview->parseCurrentBlock();            
            if(2 == count($worktracks)){
                $trimview->setCurrentBlock("dualstream");
                $trimview->setVariable("TXT_LEFT_TRACK", $this->getText("keep_left_side"));
                $trimview->setVariable("TXT_RIGHT_TRACK", $this->getText("keep_right_side"));
                $trimview->setVariable("LEFTTRACKID", $worktracks[0]->attributes()->{'id'});
                $trimview->setVariable("LEFTTRACKTYPE", $worktracks[0]->attributes()->{'type'});
                $trimview->setVariable("RIGHTTRACKID", $worktracks[1]->attributes()->{'id'});
                $trimview->setVariable("RIGHTTRACKTYPE", $worktracks[1]->attributes()->{'type'});
                $trimview->parseCurrentBlock();
            } else {
                $trimview->setCurrentBlock("singlestream");
                $trimview->setVariable("TXT_LEFT_TRACK_SINGLE", $this->getText("left_side_single"));
                $trimview->setVariable("LEFTTRACKID", $worktracks[0]->attributes()->{'id'});
                $trimview->setVariable("LEFTTRACKTYPE", $worktracks[0]->attributes()->{'type'});
                $trimview->parseCurrentBlock();
            }
            
            $trimview->setCurrentBlock("formend");
            $duration = (int)$mediapackage->attributes()->{'duration'};
            $ilLog->write(print_r($mediapackage->duration,true));
            $hours = floor($duration/3600000);
            $duration = $duration%3600000;
            $min = floor($duration/60000);
            $duration = $duration%60000;
            $sec = floor($duration/1000);            
            $trimview->setVariable("TXT_TRIMIN", $this->getText("trimin"));
            $trimview->setVariable("TXT_TRIMOUT", $this->getText("trimout"));          
            $trimview->setVariable("TXT_CONTINUE", $this->getText("continue"));              
            $trimview->setVariable("TRACKLENGTH", $duration/1000);
            #$trimview->setVariable("TRACKLENGTH", sprintf("%d:%02d:%02d",$hours,$min,$sec));
            $trimview->parseCurrentBlock();
            $tpl->setContent($trimview->get());
            $ilTabs->activateTab("manage");
        } else {
            $ilCtrl->redirect($this, "editEpisodes");
        }
        
    }

    public function trimEpisode()
    {
        global $tpl, $lng, $ilCtrl, $ilLog;
        $ilLog->write("ID:".$_POST["wfid"]);
        if (preg_match('/^[0-9a-f\-]+/', $_POST["wfid"])) {
        
            $workflow = $this->object->getWorkflow($_POST["wfid"]);
            $namespaces = $workflow->getNamespaces(true);
            $ilLog->write("namespaces: ". print_r($namespaces,true));
            $mediapackage = $workflow->children($namespaces['ns3'])->mediapackage; 
            if (!strpos($this->object->getSeries(),trim($mediapackage->series))) {
                $ilCtrl->redirect($this, "editEpisodes");
            }
            $mediapackagetitle = ilUtil::stripScriptHTML($_POST["tracktitle"]);
            $mediapackage["title"] = $mediapackagetitle;
            $tracks = array();
            if(isset($_POST["lefttrack"])){
                $track = array();
                $track['id'] = ilUtil::stripScriptHTML($_POST["lefttrack"]);
                $track['flavor'] = ilUtil::stripScriptHTML($_POST["lefttrackflavor"]);
                array_push($tracks,$track);
                
            }
            if(isset($_POST["righttrack"])){
                $track = array();
                $track['id'] = ilUtil::stripScriptHTML($_POST["righttrack"]);
                $track['flavor'] = ilUtil::stripScriptHTML($_POST["righttrackflavor"]);
                array_push($tracks,$track);
            }
            $ilLog->write("tracks: ". print_r($tracks,true));
            $removetrack;
            foreach($mediapackage->media->track as $track){
//                $ilLog->write("mediapackage: ". print_r($track,true));
//                foreach($track->attributes() as $a => $b) {
//                    $ilLog->write("attributes: ". $a."=".$track[$a]);
//                }
                if(false !== strpos($track->attributes()->{'type'},"work")){
                    $keeptrack = false;
                    foreach($tracks as $guitrack){
                    $ilLog->write("id1: ". $guitrack['id']);
                    $ilLog->write("id2: ". $track->attributes()->{'id'});
                        if($guitrack['id'] === (string)$track->attributes()->{'id'}){
                            $keeptrack = true;
                        }
                    }
                    if(!$keeptrack){
                      $removetrack = $track->attributes()->{'id'};
                    }
                }
            }
            $dom_sxe = dom_import_simplexml($mediapackage);

            $dom = new DOMDocument('1.0');
            $dom_sxe = $dom->importNode($dom_sxe, true);
            $dom_sxe = $dom->appendChild($dom_sxe);

            $ilLog->write("newmedia: ".$dom->saveXML());
            $trimin = ilUtil::stripScriptHTML($_POST["trimin"]);            
            $trimout = ilUtil::stripScriptHTML($_POST["trimout"]);

            $this->object->trim($_POST["wfid"], $dom->saveXML(), $removetrack, $trimin, $trimout);
            
            ilUtil::sendSuccess($this->txt("msg_episode_trimmed"), true);
        } else {
            $ilLog->write("ID does not match an episode:".$_POST["wfid"]);
        }                
        $ilCtrl->redirect($this, "editEpisodes");
    }

    
    function getText($a_text){
        return $this->txt($a_text);
    }

}
?>
