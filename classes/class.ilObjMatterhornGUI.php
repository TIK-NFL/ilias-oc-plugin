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
include_once ("./Services/Repository/classes/class.ilObjectPluginGUI.php");

/**
 * User Interface class for Opencast repository object.
 *
 * User interface classes process GET and POST parameter and call
 * application classes to fulfill certain tasks.
 *
 * Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 * screens) and ilInfoScreenGUI (handles the info screen).
 *
 * @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 *
 * @ilCtrl_isCalledBy ilObjMatterhornGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjMatterhornGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
 * @ilCtrl_Calls ilObjMatterhornGUI: ilCommonActionDispatcherGUI
 */
class ilObjMatterhornGUI extends ilObjectPluginGUI
{

    /**
     * Initialisation
     */
    protected function afterConstructor()
    {
        $this->getPlugin()->includeClass("class.ilMatterhornConfig.php");
        $this->configObject = new ilMatterhornConfig();
    }

    /**
     * Get type.
     */
    final public function getType()
    {
        return "xmh";
    }

    /**
     * Handles all commmands of this class, centralizes permission checks
     */
    public function performCommand($cmd)
    {
        switch ($cmd) {
            case "editProperties": // list all commands that need write permission here
            case "updateProperties":
            case "trimEpisode":
            case "deletescheduled":
            case "publish":
            case "retract":
            case "getEpisodes":
                $this->checkPermission("write");
                $this->$cmd();
                break;
            case "showTrimEditor":
            case "editFinishedEpisodes":
            case "editTrimProcess":
            case "editUpload":
            case "editSchedule":
                $this->checkPermission("write");
                $this->setSubTabs('manage');
                $this->$cmd();
                break;
            
            case "showSeries": // list all commands that need read permission here
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
    public function getAfterCreationCmd()
    {
        return "editProperties";
    }

    /**
     * Get standard command
     */
    public function getStandardCmd()
    {
        return "showSeries";
    }

    /**
     * @override
     */
    protected function supportsCloning()
    {
        return false;
    }

    //
    // DISPLAY TABS
    //
    
    /**
     * Set tabs
     */
    public function setTabs()
    {
        global $ilTabs, $ilCtrl, $ilAccess;
        
        // tab for the "show content" command
        if ($ilAccess->checkAccess("read", "", $this->object->getRefId())) {
            $ilTabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showSeries"));
        }
        
        // standard info screen tab
        $this->addInfoTab();
        
        // a "properties" tab
        if ($ilAccess->checkAccess("write", "", $this->object->getRefId())) {
            $ilTabs->addTab("manage", $this->txt("manage"), $ilCtrl->getLinkTarget($this, "editFinishedEpisodes"));
            $ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
        }
        
        // standard permission tab
        $this->addPermissionTab();
    }

    /**
     * Set the sub tabs
     *
     * @param
     *            string main tab identifier
     */
    public function setSubTabs($a_tab)
    {
        global $ilTabs, $ilCtrl;
        
        switch ($a_tab) {
            case "manage":
                ilLoggerFactory::getLogger('xmh')->debug("setting subtabs");
                $ilTabs->addSubTab("finishedepisodes", $this->txt('finished_recordings'), $ilCtrl->getLinkTarget($this, 'editFinishedEpisodes'));
                $ilTabs->addSubTab("processtrim", $this->txt('processtrim'), $ilCtrl->getLinkTarget($this, 'editTrimProcess'));
                $ilTabs->addSubTab("schedule", $this->txt('scheduled_recordings'), $ilCtrl->getLinkTarget($this, 'editSchedule'));
                $ilTabs->addSubTab("upload", $this->txt('add_new_episode'), $ilCtrl->getLinkTarget($this, 'editUpload'));
                break;
        }
    }

    //
    // Edit properties form
    //
    
    /**
     * Edit Properties.
     * This commands uses the form class to display an input form.
     */
    public function editProperties()
    {
        global $tpl, $ilTabs;
        
        $ilTabs->activateTab("properties");
        $form = $this->initPropertiesForm();
        $values = $this->getPropertiesValues();
        $form->setValuesByArray($values);
        $tpl->setContent($form->getHTML());
    }

    /**
     * Init form.
     *
     * @return ilPropertyFormGUI
     */
    private function initPropertiesForm()
    {
        global $DIC;
        
        include_once ("Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        
        // title
        $ti = new ilTextInputGUI($this->txt("title"), "title");
        $ti->setRequired(true);
        $form->addItem($ti);
        
        // description
        $ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
        $form->addItem($ta);
        
        // viewmode
        $vm = new ilCheckboxInputGUI($this->txt("viewmode"), "viewMode");
        $form->addItem($vm);
        
        // release episodes individually
        $mr = new ilCheckboxInputGUI($this->txt("manualRelease"), "manualRelease");
        $form->addItem($mr);
        
        // download
        $download = new ilCheckboxInputGUI($this->txt("enable_download"), "download");
        $form->addItem($download);
        
        // online
        $cb = new ilCheckboxInputGUI($this->txt("online"), "online");
        
        $form->addItem($cb);
        $form->addCommandButton("updateProperties", $this->txt("save"));
        
        $form->setTitle($this->txt("edit_properties"));
        $form->setFormAction($DIC->ctrl()
            ->getFormAction($this));
        
        return $form;
    }

    /**
     * Get values for edit properties form
     *
     * @return array values
     */
    private function getPropertiesValues()
    {
        $series = $this->object->getSeries()->getSeriesInformationFromOpencast();
        $values = array();
        $values["title"] = $this->object->getTitle();
        $values["desc"] = $series["description"];
        $values["online"] = $this->object->getOnline();
        $values["viewMode"] = $this->object->getViewMode();
        $values["manualRelease"] = $this->object->getManualRelease();
        $values["download"] = $this->object->getDownload();
        return $values;
    }

    /**
     * Update properties
     */
    public function updateProperties()
    {
        global $DIC;
        $tpl = $DIC->ui()->mainTemplate();
        
        $form = $this->initPropertiesForm();
        if ($form->checkInput()) {
            $this->object->setTitle($form->getInput("title"));
            $this->object->setDescription($form->getInput("desc"));
            $this->object->setOnline($form->getInput("online"));
            $this->object->setViewMode($form->getInput("viewMode"));
            $this->object->setManualRelease($form->getInput("manualRelease"));
            $this->object->setDownload($form->getInput("download"));
            $this->object->update();
            ilUtil::sendSuccess($DIC->language()->txt("msg_obj_modified"), true);
            $DIC->ctrl()->redirect($this, "editProperties");
        }
        
        $form->setValuesByPost();
        $tpl->setContent($form->getHtml());
    }

    public function publish()
    {
        global $DIC;
        $episodeId = $_GET["id"];
        ilLoggerFactory::getLogger('xmh')->debug("ID:" . $episodeId);
        $episode = $this->object->getEpisode($episodeId);
        
        if ($episode) {
            try {
                $episode->publish();
                ilUtil::sendSuccess($this->txt("msg_episode_published"), true);
            } catch (Exception $e) {
                if (strpos($e->getMessage(), "already published") == false) {
                    throw $e;
                }
            }
        } else {
            ilLoggerFactory::getLogger('xmh')->debug("ID does not match in publish episode:" . $episode);
        }
        $DIC->ctrl()->redirect($this, "editFinishedEpisodes");
    }

    public function retract()
    {
        global $DIC;
        $episodeId = $_GET["id"];
        ilLoggerFactory::getLogger('xmh')->debug("ID:" . $episodeId);
        $episode = $this->object->getEpisode($episodeId);
        
        if ($episode) {
            $episode->retract();
            ilUtil::sendSuccess($this->txt("msg_episode_retracted"), true);
        } else {
            ilLoggerFactory::getLogger('xmh')->debug("ID does not match in retract episode:" . $episodeId);
        }
        $DIC->ctrl()->redirect($this, "editFinishedEpisodes");
    }

    public function deletescheduled()
    {
        global $DIC;
        $episodeId = $_GET["id"];
        ilLoggerFactory::getLogger('xmh')->debug("ID:$episodeId");
        $episode = $this->object->getEpisode($episodeId);
        
        if ($episode) {
            $episode->delete();
            ilUtil::sendSuccess($this->txt("msg_scheduling_deleted"), true);
        } else {
            ilLoggerFactory::getLogger('xmh')->debug("ID does not match in deleteschedule:$episodeId");
        }
        $DIC->ctrl()->redirect($this, "editSchedule");
    }

    //
    // Show content
    //
    
    /**
     * Show content
     */
    public function showEpisode()
    {
        global $DIC;
        $ilTabs = $DIC->tabs();
        $tpl = $DIC->ui()->mainTemplate();
        
        $this->checkPermission("read");
        $theodulbase = $this->getPlugin()->getDirectory() . "/templates/theodul";
        
        $player = $this->getPlugin()->getTemplate("default/tpl.player.html", true, false);
        $player->setVariable("INITJS", $theodulbase);
        
        $tpl->setContent($player->get());
        $ilTabs->activateTab("content");
    }

    public function showSeries()
    {
        global $DIC;
        $ilCtrl = $DIC->ctrl();
        $tpl = $DIC->ui()->mainTemplate();
        $factory = $DIC->ui()->factory();
        
        $this->checkPermission("read");
        
        $released_episodes = $this->extractReleasedEpisodes(true);
        if (! $this->object->getViewMode()) {
            $seriestpl = $this->getPlugin()->getTemplate("default/tpl.series.html", true, true);
            $seriestpl->setCurrentBlock($this->object->getDownload() ? "headerdownload" : "header");
            $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
            $seriestpl->setVariable("TXT_PREVIEW", $this->getText("preview"));
            $seriestpl->setVariable("TXT_DATE", $this->getText("date"));
            if ($this->object->getDownload()) {
                $seriestpl->setVariable("TXT_ACTION", $this->getText("action"));
            }
            $seriestpl->parseCurrentBlock();
            foreach ($released_episodes as $item) {
                $seriestpl->setCurrentBlock($this->object->getDownload() ? "episodedownload" : "episode");
                // ilLoggerFactory::getLogger('xmh')->debug("Adding: ".$item["title"]);
                
                $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $item['opencast_id']);
                $seriestpl->setVariable("CMD_PLAYER", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode"));
                $seriestpl->setVariable("PREVIEWURL", $item["previewurl"]);
                $seriestpl->setVariable("TXT_TITLE", $item["title"]);
                $seriestpl->setVariable("TXT_DATE", ilDatePresentation::formatDate(new ilDateTime($item["date"], IL_CAL_DATETIME)));
                if ($this->object->getDownload()) {
                    $seriestpl->setVariable("DOWNLOADURL", $item["downloadurl"]);
                    $seriestpl->setVariable("TXT_DOWNLOAD", $DIC->language()
                        ->txt("download"));
                }
                $seriestpl->parseCurrentBlock();
            }
            $seriestpl->touchblock("footer");
            $html = $seriestpl->get();
            $tpl->setContent($html);
        } else {
            $cards = array();
            foreach ($released_episodes as $item) {
                $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $item['opencast_id']);
                $url = $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode");
                
                $image = $factory->image()->responsive($item["previewurl"], $this->getText("preview"));
                $content = $factory->listing()->descriptive(array(
                    $this->getText("date") => ilDatePresentation::formatDate(new ilDateTime($item["date"], IL_CAL_DATETIME))
                ));
                $sections = array(
                    $content
                );
                if ($this->object->getDownload()) {
                    $sections[] = $factory->link()->standard($DIC->language()
                        ->txt("download"), $item["downloadurl"]);
                }
                
                $cards[] = $factory->card($item["title"], $image)
                    ->withSections($sections)
                    ->withTitleAction($url);
            }
            
            $deck = $factory->deck($cards);
            $html = $DIC->ui()
                ->renderer()
                ->render($deck);
            $tpl->setContent($html);
        }
        $tpl->setPermanentLink($this->object->getType(), $this->object->getRefId());
        $DIC->tabs()->activateTab("content");
    }

    private function extractReleasedEpisodes($skipUnreleased = false)
    {
        $releasedEpisodeIds = $this->object->getReleasedEpisodeIds();
        $episodes = array();
        
        foreach ($this->object->getSearchResult()->mediapackage as $value) {
            if ($skipUnreleased && $this->object->getManualRelease()) {
                if (! in_array($value['id'], $releasedEpisodeIds)) {
                    continue;
                }
            }
            $previewurl = "unset";
            foreach ($value->attachments->attachment as $attachment) {
                if ('presentation/search+preview' == $attachment['type']) {
                    $previewurl = $attachment->url;
                    // prefer presentation/search+preview over presenter/search+preview
                    break 1;
                } elseif ('presenter/search+preview' == $attachment['type']) {
                    $previewurl = $attachment->url;
                    // continue searching for a presentation/search+preview
                }
            }
            $downloadurl = "unset";
            foreach ($value->media->track as $track) {
                if ('composite/sbs' == $track['type']) {
                    $downloadurl = $track->url;
                    break;
                }
                if ('presentation/delivery' == $track['type'] && 'video/mp4' == $track->mimetype) {
                    $downloadurl = $track->url;
                }
            }
            
            $published = in_array($value['id'], $releasedEpisodeIds);
            
            $episode = array(
                "title" => (string) $value->title,
                "date" => (string) $value['start'],
                "opencast_id" => $this->object->getSeriesId() . "/" . (string) $value['id'],
                "previewurl" => (string) $previewurl,
                "downloadurl" => (string) $downloadurl,
                "viewurl" => $this->getLinkForEpisodeUnescaped("showEpisode", $this->object->getSeriesId() . "/" . (string) $value['id'])
            );
            if ($this->object->getManualRelease()) {
                $episode["publishurl"] = $this->getLinkForEpisodeUnescaped($published ? "retract" : "publish", (string) $value['id']);
                $episode["txt_publish"] = $this->getText($published ? "retract" : "publish");
            }
            $episodes[] = $episode;
        }
        
        usort($episodes, array(
            $this,
            'sortbydate'
        ));
        return $episodes;
    }

    private function extractScheduledEpisode($event)
    {
        $scheduled_episode = array(
            'title' => $event["title"],
            'episode_id' => $event["identifier"],
            'deletescheduledurl' => $this->getLinkForEpisodeUnescaped("deletescheduled", (string) $event['identifier'])
        );
        $scheduled_episode['startdate'] = $event['start'];
        $scheduled_episode['stopdate'] = $event['end'];//new api ????
        $scheduled_episode['location'] = $event['location'];
        return $scheduled_episode;
    }

    private function extractOnholdEpisode($event)
    {
        $onhold_episode = array(
            'title' => $event["title"],
            'trimurl' => $this->getLinkForEpisodeUnescaped("showTrimEditor", (string) $event['id']),
            'date' => $event["start"]
        );
        return $onhold_episode;
    }

    /**
     *
     * @param string $cmd
     * @param string $id
     * @return string
     */
    private function getLinkForEpisodeUnescaped($cmd, $id)
    {
        global $DIC;
        $DIC->ctrl()->setParameterByClass("ilobjmatterhorngui", "id", $id);
        $link = $DIC->ctrl()->getLinkTargetByClass("ilobjmatterhorngui", $cmd, "", false, false);
        $DIC->ctrl()->clearParameterByClass("ilobjmatterhorngui", "id");
        return $link;
    }

    /**
     * Get Episodes as json
     */
    public function getEpisodes()
    {
        $series = $this->object->getSeries();
        $process_items = $series->getProcessingEpisodes();

        usort($process_items, array(
            $this,
            'sortbydate'
        ));
        foreach ($process_items as $key => $value) {
            $process_items[$key]["date"] = ilDatePresentation::formatDate(new ilDateTime($value["date"], IL_CAL_DATETIME));
        }

        $finished_episodes = $this->extractReleasedEpisodes();
        foreach ($finished_episodes as $key => $value) {
            $finished_episodes[$key]["date"] = ilDatePresentation::formatDate(new ilDateTime($value["date"], IL_CAL_DATETIME));
        }
        $scheduledEpisodes = $series->getScheduledEpisodes();
        $scheduled_items = array_map(array(
            $this,
            'extractScheduledEpisode'
        ), $scheduledEpisodes);
        
        usort($scheduled_items, array(
            $this,
            'sortbystartdate'
        ));
        foreach ($scheduled_items as $key => $value) {
            $scheduled_items[$key]["startdate"] = ilDatePresentation::formatDate(new ilDateTime($value["startdate"], IL_CAL_DATETIME));
            $scheduled_items[$key]["stopdate"] = ilDatePresentation::formatDate(new ilDateTime($value["stopdate"], IL_CAL_DATETIME));
        }
        
        $onHoldEpisodes = $series->getOnHoldEpisodes();
        $onhold_items = array_map(array(
            $this,
            'extractOnholdEpisode'
        ), $onHoldEpisodes);

        usort($onhold_items, array(
            $this,
            'sortbydate'
        ));
        foreach ($onhold_items as $key => $value) {
            $onhold_items[$key]["date"] = ilDatePresentation::formatDate(new ilDateTime($value["date"], IL_CAL_DATETIME));
        }
        
        $data = array();
        $data['lastupdate'] = $this->object->getLastFSInodeUpdate();
        $data['finished'] = $finished_episodes;
        $data['processing'] = $process_items;
        $data['onhold'] = $onhold_items;
        $data['scheduled'] = $scheduled_items;
        header('Vary: Accept');
        header('Content-type: application/json');
        echo json_encode($data);
        // no further processing!
        exit();
    }

    private function sortbydate($a, $b, $field = "date")
    {
        if ($a[$field] == $b[$field]) {
            return 0;
        }
        return ($a[$field] > $b[$field]) ? - 1 : 1;
    }

    private function sortbystartdate($a, $b)
    {
        return $this->sortbydate($a, $b, "startdate");
    }

    public function editFinishedEpisodes()
    {
        $this->editEpisodes('finished');
    }

    public function editTrimProcess()
    {
        $this->editEpisodes('trimprocess');
    }

    public function editUpload()
    {
        $this->editEpisodes('upload');
    }

    public function editSchedule()
    {
        $this->editEpisodes('scheduled');
    }

    private function editEpisodes($section)
    {
        global $DIC;
        $ilTabs = $DIC->tabs();
        $ilCtrl = $DIC->ctrl();
        $tpl = $DIC->ui()->mainTemplate();
        $factory = $DIC->ui()->factory();
        
        $ilTabs->activateTab("manage");
        $this->checkPermission("write");
        
        $seriestpl = $this->getPlugin()->getTemplate("default/tpl.series.edit.js.html");
        $seriestpl->setCurrentBlock("pagestart");
        
        $seriestpl->setVariable("TXT_NONE_FINISHED", $this->getText("none_finished"));
        $seriestpl->setVariable("TXT_NONE_PROCESSING", $this->getText("none_processing"));
        $seriestpl->setVariable("TXT_NONE_ONHOLD", $this->getText("none_onhold"));
        $seriestpl->setVariable("TXT_NONE_SCHEDULED", $this->getText("none_scheduled"));
        $seriestpl->setVariable("TXT_DELETE", $this->getText("delete"));
        $seriestpl->setVariable("TXT_UPLOADING", $this->getText("uploading"));
        $seriestpl->setVariable("TXT_DONE_UPLOADING", $this->getText("done_uploading"));
        $seriestpl->setVariable("TXT_UPLOAD_CANCELED", $this->getText("upload_canceled"));
        $seriestpl->setVariable("CMD_PROCESSING", $ilCtrl->getLinkTarget($this, "getEpisodes", "", true));
        $seriestpl->setVariable("SERIES_ID", $this->object->getSeriesId());
        $seriestpl->setVariable("MANUAL_RELEASE", $this->object->getManualRelease());
        $seriestpl->parseCurrentBlock();
        $jsConfig = $seriestpl->get();
        ilLoggerFactory::getLogger('xmh')->debug($section);
        switch ($section) {
            case 'finished':
                $ilTabs->activateSubTab('finishedepisodes');
                
                $colums = array(
                    $this->getText("title"),
                    $this->getText("preview"),
                    $this->getText("date")
                );
                if ($this->object->getManualRelease()) {
                    $colums[] = $this->getText("action");
                }
                
                $finishedTable = $this->getTableWithId("iliasopencast_finishedtable", $colums);
                
                $content = $factory->panel()->standard($this->getText("finished_recordings"), $finishedTable);
                break;
            case 'trimprocess':
                $ilTabs->activateSubTab('processtrim');
                
                $processingTable = $this->getTableWithId("iliasopencast_processingtable", array(
                    $this->getText("title"),
                    $this->getText("recorddate"),
                    $this->getText("progress"),
                    $this->getText("running")
                ), "fixed");
                
                $onholdTable = $this->getTableWithId("iliasopencast_onholdtable", array(
                    $this->getText("title"),
                    $this->getText("recorddate")
                ));
                
                $content = array(
                    $factory->panel()->standard($this->getText("processing"), $processingTable),
                    $factory->panel()->standard($this->getText("onhold_recordings"), $onholdTable)
                );
                break;
            case 'upload':
                $ilTabs->activateSubTab('upload');
                
                $seriestpl = $this->getPlugin()->getTemplate("default/tpl.upload.html");
                $seriestpl->setCurrentBlock("upload");
                $seriestpl->setVariable("TXT_TRACK_TITLE", $this->getText("track_title"));
                $seriestpl->setVariable("TXT_TRACK_PRESENTER", $this->getText("track_presenter"));
                $seriestpl->setVariable("TXT_TRACK_DATE", $this->getText("track_date"));
                $seriestpl->setVariable("TXT_TRACK_TIME", $this->getText("track_time"));
                $seriestpl->setVariable("TXT_SELECT_FILE", $this->getText("select_file"));
                $seriestpl->setVariable("TXT_NO_FILES", $this->getText("no_files"));
                $seriestpl->setVariable("TXT_UPLOAD_FILE", $this->getText("upload_file"));
                $seriestpl->setVariable("TXT_CANCEL_UPLOAD", $this->getText("cancel_upload"));
                $seriestpl->setVariable("TXT_TRIMEDITOR", $this->getText("usetrimeditor"));
                $seriestpl->parseCurrentBlock();
                
                $content = $factory->panel()->standard($this->getText("add_new_episode"), $factory->legacy($seriestpl->get()));
                $tpl->addCss($this->plugin->getStyleSheetLocation("css/bootstrap-datepicker3.min.css"));
                $tpl->addCss($this->plugin->getStyleSheetLocation("css/bootstrap-timepicker.min.css"));
                $tpl->addCss($this->plugin->getStyleSheetLocation("css/xmh.css"));
                $tpl->addJavaScript($this->plugin->getDirectory() . "/templates/edit/resumable.js");
                $tpl->addJavaScript($this->plugin->getDirectory() . "/templates/edit/bootstrap-datepicker.min.js");
                $tpl->addJavaScript($this->plugin->getDirectory() . "/templates/edit/bootstrap-timepicker.min.js");
                $tpl->addJavaScript($this->plugin->getDirectory() . "/templates/edit/upload.js");
                $tpl->addOnLoadCode("initUpload(iliasopencast);");
                break;
            case 'scheduled':
                $ilTabs->activateSubTab('schedule');
                
                $scheduledTable = $this->getTableWithId("iliasopencast_scheduledtable", array(
                    $this->getText("title"),
                    $this->getText("startdate"),
                    $this->getText("enddate"),
                    $this->getText("location"),
                    $this->getText("action")
                ));
                
                $content = $factory->panel()->standard($this->getText("scheduled_recordings"), $scheduledTable);
                break;
        }
        $html = $DIC->ui()
            ->renderer()
            ->render($content);
        
        $tpl->setContent($jsConfig . $html);
        $tpl->addJavaScript($this->plugin->getDirectory() . "/templates/edit/mustache.min.js");
        $tpl->addJavaScript($this->plugin->getDirectory() . "/templates/edit/edit.js");
        $tpl->addOnLoadCode("initEdit(iliasopencast);");
        $tpl->setPermanentLink($this->object->getType(), $this->object->getRefId());
    }

    /**
     *
     * @param $id string
     * @param $columns array
     * @return \ILIAS\UI\Component\Legacy\Legacy
     */
    private function getTableWithId($id, $columns, $layout = "auto")
    {
        global $DIC;
        $tableTpl = $this->getPlugin()->getTemplate("default/tpl.empty_table.html");
        $tableTpl->setCurrentBlock("headercolumn");
        foreach ($columns as $column) {
            $tableTpl->setVariable("TXT_HEAD", $column);
            $tableTpl->parseCurrentBlock();
        }
        
        $tableTpl->setCurrentBlock("table");
        $tableTpl->setVariable("TABLE_STYLE", "table-layout: $layout;");
        $tableTpl->setVariable("ID", $id);
        $tableTpl->parseCurrentBlock();
        return $DIC->ui()
            ->factory()
            ->legacy($tableTpl->get());
    }

    /**
     * Show the trim episode Page
     */
    public function showTrimEditor()
    {
        global $DIC;
        $tpl = $DIC->ui()->mainTemplate();
        $ilCtrl = $DIC->ctrl();
        $factory = $DIC->ui()->factory();
        $this->checkPermission("write");
        $trimbase = $this->getPlugin()->getDirectory() . "/templates/trim";
        $episode = $this->object->getEpisode($_GET["id"]);
        if ($episode) {
            $id = $episode->getEpisodeId();
            ilLoggerFactory::getLogger('xmh')->debug("Trimming episode: $id");
            $editor = $episode->getEditor();
            $previewtracks = array();
            $worktracks = array();
            $media = $episode->getMedia();
            foreach ($media as $track) {
                switch ($track->type) {
                    case "presentation/source":
                        ilLoggerFactory::getLogger('xmh')->debug("Found presentation track");
                        $worktracks['presentation'] = $track;
                        break;
                    case "presenter/source":
                        $worktracks['presenter'] = $track;
                        break;
                }
            }
            
            $trimview = $this->getPlugin()->getTemplate("default/tpl.trimview.html", true, true);
            $trimview->setCurrentBlock("formstart");
            $trimview->setVariable("TXT_TRACK_TITLE", $this->getText("track_title"));
            $trimview->setVariable("TRACKTITLE", $editor->title);
            $trimview->setVariable("INITJS", $trimbase);
            $trimview->setVariable("CMD_TRIM", $ilCtrl->getFormAction($this, "trimEpisode"));
            $trimview->setVariable("WFID", $id);
            $trimview->parseCurrentBlock();
            if (2 == count($worktracks)) {
                $trimview->setCurrentBlock("dualstream");
                $trimview->setVariable("TXT_LEFT_TRACK", $this->getText("keep_left_side"));
                $trimview->setVariable("TXT_RIGHT_TRACK", $this->getText("keep_right_side"));
                $presenterattributes = $worktracks['presenter'];
                $trimview->setVariable("LEFTTRACKID", $presenterattributes->id);
                $trimview->setVariable("LEFTTRACKTYPE", $presenterattributes->type);
                $presentationattributes = $worktracks['presentation'];
                $trimview->setVariable("RIGHTTRACKID", $presentationattributes->id);
                $trimview->setVariable("RIGHTTRACKTYPE", $presentationattributes->type);
                $trimview->setVariable("FLAVORUNSET", $this->getText("flavor_unset"));
                $trimview->setVariable("FLAVORPRESENTER", $this->getText("flavor_presenter"));
                $trimview->setVariable("FLAVORPRESENTATION", $this->getText("flavor_presentation"));
                $trimview->parseCurrentBlock();
            } else {
                $trackkeys = array_keys($worktracks);
                $trackkey = $trackkeys[0];
                $trimview->setCurrentBlock("singlestream");
                $trimview->setVariable("TXT_LEFT_TRACK_SINGLE", $this->getText("left_side_single"));
                $attributes = $worktracks[$trackkey];
                $trimview->setVariable("LEFTTRACKID", $attributes->id);
                $trimview->setVariable("LEFTTRACKTYPE", $attributes->flavor);
                $trimview->setVariable("FLAVORUNSET", $this->getText("flavor_unset"));
                $trimview->setVariable("FLAVORPRESENTER", $this->getText("flavor_presenter"));
                $trimview->setVariable("FLAVORPRESENTATION", $this->getText("flavor_presentation"));
                $trimview->parseCurrentBlock();
            }
            $trimview->setCurrentBlock("video");
            $trimview->setVariable("TXT_DOWNLOAD_PREVIEW", $this->getText("download_preview"));
            // if there are two tracks, there is also a sbs track. Otherwise use the only track present.
            $downloadurlmp4 = $this->getPlugin()->getDirectory() . "/MHData/" . CLIENT_ID . "/" . trim($editor->series->id) . "/$id/previewsbs.mp4";
            $trimview->setVariable("DOWNLOAD_PREVIEW_URL_MP4", $downloadurlmp4);
            
            $duration = $editor->duration;
            $trimview->setVariable("TRACKLENGTH", $duration / 1000);
            $trimview->parseCurrentBlock();
            $trimview->setCurrentBlock("formend");
            $hours = floor($duration / 3600000);
            $duration = $duration % 3600000;
            $min = floor($duration / 60000);
            $duration = $duration % 60000;
            $sec = floor($duration / 1000);
            $trimview->setVariable("TXT_TRIMIN", $this->getText("trimin"));
            $trimview->setVariable("TXT_TRIMOUT", $this->getText("trimout"));
            $trimview->setVariable("TXT_CONTINUE", $this->getText("continue"));
            $trimview->setVariable("TXT_SET_TO_CURRENT_TIME", $this->getText("set_to_current_time"));
            $trimview->setVariable("TXT_PREVIEW_INPOINT", $this->getText("preview_inpoint"));
            $trimview->setVariable("TXT_PREVIEW_OUTPOINT", $this->getText("preview_outpoint"));
            $trimview->setVariable("TXT_INPOINT", $this->getText("inpoint"));
            $trimview->setVariable("TXT_OUTPOINT", $this->getText("outpoint"));
            $trimview->setVariable("TRACKLENGTH", sprintf("%d:%02d:%02d", $hours, $min, $sec));
            $trimview->parseCurrentBlock();
            $editorHtml = $trimview->get();
            $content = $factory->panel()->standard($this->getText("ilias_trim_editor"), $factory->legacy($editorHtml));
            $html = $DIC->ui()
                ->renderer()
                ->render($content);
            $tpl->setContent($html);
            $tpl->addCss("$trimbase/video-js/video-js.css");
            $tpl->addCss("./libs/bower/bower_components/jquery-ui/themes/base/jquery-ui.min.css");
            $tpl->addCss($this->plugin->getStyleSheetLocation("css/xmh.css"));
            $DIC->tabs()->activateTab("manage");
        } else {
            $ilCtrl->redirect($this, "editTrimProcess");
        }
    }

    public function trimEpisode()
    {
        global $ilCtrl;
        $episode = $this->object->getEpisode($_POST["eventid"]);
        if ($episode) {
            $title = (string) ilUtil::stripScriptHTML($_POST["tracktitle"]);
            if ($title) {
                $episode->setTitle($title);
            }
            $editor = $episode->getEditor();
            ilLoggerFactory::getLogger('xmh')->debug("eventid " . print_r($editor, true));
            $tracks = array();
            if (isset($_POST["lefttrack"])) {
                $track = array();
                $track['id'] = ilUtil::stripScriptHTML($_POST["lefttrack"]);
                $track['flavor'] = ilUtil::stripScriptHTML($_POST["lefttrackflavor"]);
                array_push($tracks, $track);
            }
            if (isset($_POST["righttrack"])) {
                $track = array();
                $track['id'] = ilUtil::stripScriptHTML($_POST["righttrack"]);
                $track['flavor'] = ilUtil::stripScriptHTML($_POST["righttrackflavor"]);
                array_push($tracks, $track);
            }
            $keeptracks = [];
            foreach ($tracks as $track) {
                array_push($keeptracks, $track['id']);
            }
            $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", ilUtil::stripScriptHTML($_POST["trimin"]));
            list ($hours, $minutes, $seconds) = sscanf($str_time, "%d:%d:%d");
            $trimin = $hours * 3600 + $minutes * 60 + $seconds;
            
            $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", ilUtil::stripScriptHTML($_POST["trimout"]));
            sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
            $trimout = $hours * 3600 + $minutes * 60 + $seconds;
            
            $episode->trim($keeptracks, $trimin, $trimout);
            
            ilUtil::sendSuccess($this->txt("msg_episode_send_to_triming"), true);
        } else {
            ilLoggerFactory::getLogger('xmh')->debug("ID does not match an episode:" . $_POST["eventid"]);
        }
        $ilCtrl->redirect($this, "editTrimProcess");
    }

    public function getText($a_text)
    {
        return $this->txt($a_text);
    }

    public function addInfoItems($info)
    {
        $info->addSection($this->getText("opencast_information"));
        $info->addProperty($this->getText("series_id"), $this->object->getSeriesId());
    }
}
