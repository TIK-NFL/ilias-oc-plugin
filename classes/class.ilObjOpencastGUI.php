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
use ILIAS\FileUpload\Location;
use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;

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
 * @author Per Pascal Seeland <pascal.seeland@tik.uni-stuttgart.de>
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 *
 * @ilCtrl_isCalledBy ilObjOpencastGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjOpencastGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
 * @ilCtrl_Calls ilObjOpencastGUI: ilCommonActionDispatcherGUI
 */
class ilObjOpencastGUI extends ilObjectPluginGUI
{

    const QUERY_EPISODE_IDENTIFIER = "id";

    const QUERY_MEDIAPACKAGE_ID = "id";

    const POST_EPISODENAME = "episodename";

    const POST_PRESENTER = "presenter";

    const POST_EPISODEDATETIME = "episodendatetime";

    const POST_USETRIMEDITOR = "usetrimeditor";

    const ILIAS_TEMP_DIR = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';

    const UPLOAD_DIR = "xoc_upload";

    const UPLOAD_SUFFIXES = [
        'mp4',
        'webm'
    ];

    /**
     * Initialisation
     */
    protected function afterConstructor()
    {
        $this->getPlugin()->includeClass("class.ilOpencastConfig.php");
        $this->configObject = new ilOpencastConfig();
    }

    /**
     * Get the ilObjOpencast for the GUI.
     *
     * @return ilObjOpencast
     */
    private function getOCObject()
    {
        return $this->object;
    }

    /**
     * Get type.
     */
    final public function getType()
    {
        return "xoc";
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
                ilLoggerFactory::getLogger('xoc')->debug("setting subtabs");
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
        $series = $this->getOCObject()->getSeries()->getSeriesInformationFromOpencast();
        $values = array();
        $values["title"] = $this->getOCObject()->getTitle();
        $values["desc"] = $series["description"];
        $values["online"] = $this->getOCObject()->getOnline();
        $values["viewMode"] = $this->getOCObject()->getViewMode();
        $values["manualRelease"] = $this->getOCObject()->getManualRelease();
        $values["download"] = $this->getOCObject()->getDownload();
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
            $object = $this->getOCObject();
            $object->setTitle($form->getInput("title"));
            $object->setDescription($form->getInput("desc"));
            $object->setOnline($form->getInput("online"));
            $object->setViewMode($form->getInput("viewMode"));
            $object->setManualRelease($form->getInput("manualRelease"));
            $object->setDownload($form->getInput("download"));
            $object->update();
            ilUtil::sendSuccess($DIC->language()->txt("msg_obj_modified"), true);
            $DIC->ctrl()->redirect($this, "editProperties");
        }
        
        $form->setValuesByPost();
        $tpl->setContent($form->getHtml());
    }

    public function publish()
    {
        global $DIC;
        $episodeId = $_GET[self::QUERY_EPISODE_IDENTIFIER];
        ilLoggerFactory::getLogger('xoc')->debug("ID:" . $episodeId);
        $episode = $this->getOCObject()->getEpisode($episodeId);
        
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
            ilLoggerFactory::getLogger('xoc')->debug("ID does not match in publish episode:" . $episode);
        }
        $DIC->ctrl()->redirect($this, "editFinishedEpisodes");
    }

    public function retract()
    {
        global $DIC;
        $episodeId = $_GET[self::QUERY_EPISODE_IDENTIFIER];
        ilLoggerFactory::getLogger('xoc')->debug("ID:" . $episodeId);
        $episode = $this->getOCObject()->getEpisode($episodeId);
        
        if ($episode) {
            $episode->retract();
            ilUtil::sendSuccess($this->txt("msg_episode_retracted"), true);
        } else {
            ilLoggerFactory::getLogger('xoc')->debug("ID does not match in retract episode:" . $episodeId);
        }
        $DIC->ctrl()->redirect($this, "editFinishedEpisodes");
    }

    public function deletescheduled()
    {
        global $DIC;
        $episodeId = $_GET[self::QUERY_EPISODE_IDENTIFIER];
        ilLoggerFactory::getLogger('xoc')->debug("ID:$episodeId");
        $episode = $this->getOCObject()->getEpisode($episodeId);
        
        if ($episode) {
            $episode->delete();
            ilUtil::sendSuccess($this->txt("msg_scheduling_deleted"), true);
        } else {
            ilLoggerFactory::getLogger('xoc')->debug("ID does not match in deleteschedule:$episodeId");
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
        $tpl = $DIC->ui()->mainTemplate();
        $factory = $DIC->ui()->factory();
        
        $this->checkPermission("read");
        
        $released_episodes = $this->getReadyEpisodes(true);
        if (! $this->getOCObject()->getViewMode()) {
            $seriestpl = $this->getPlugin()->getTemplate("default/tpl.series.html", true, true);
            $seriestpl->setCurrentBlock($this->getOCObject()->getDownload() ? "headerdownload" : "header");
            $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
            $seriestpl->setVariable("TXT_PREVIEW", $this->getText("preview"));
            $seriestpl->setVariable("TXT_DATE", $this->getText("date"));
            if ($this->getOCObject()->getDownload()) {
                $seriestpl->setVariable("TXT_ACTION", $this->getText("action"));
            }
            $seriestpl->parseCurrentBlock();
            foreach ($released_episodes as $item) {
                $seriestpl->setCurrentBlock($this->getOCObject()->getDownload() ? "episodedownload" : "episode");
                $seriestpl->setVariable("CMD_PLAYER", $this->getLinkForShowEpisode($item['series_id'], $item['episode_id'], true));
                $seriestpl->setVariable("PREVIEWURL", $item["previewurl"]);
                $seriestpl->setVariable("TXT_TITLE", $item["title"]);
                $seriestpl->setVariable("TXT_DATE", ilDatePresentation::formatDate(new ilDateTime($item["date"], IL_CAL_ISO_8601)));
                if ($this->getOCObject()->getDownload()) {
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
                $url = $this->getLinkForShowEpisode($item['series_id'], $item['episode_id'], true);
                
                $image = $factory->image()->responsive($item["previewurl"], $this->getText("preview"));
                $content = $factory->listing()->descriptive(array(
                    $this->getText("date") => ilDatePresentation::formatDate(new ilDateTime($item["date"], IL_CAL_ISO_8601))
                ));
                $sections = array(
                    $content
                );
                if ($this->getOCObject()->getDownload()) {
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
        $tpl->setPermanentLink($this->getOCObject()->getType(), $this->getOCObject()->getRefId());
        $DIC->tabs()->activateTab("content");
    }

    private function getReadyEpisodes(bool $onlyPublished)
    {
        $releasedEpisodeIds = $this->getOCObject()->getReleasedEpisodeIds();
        $episodes = array();

        foreach ($this->getOCObject()
            ->getSeries()
            ->getReadyEpisodes() as $readyEpisode) {
            $published = in_array($readyEpisode->identifier, $releasedEpisodeIds);
            if ($onlyPublished && ! $published && $this->getOCObject()->getManualRelease()) {
                continue;
            }

            $apiPublication = null;
            foreach ($readyEpisode->publications as $publication) {
                if ($publication->channel == "api") {
                    $apiPublication = $publication;
                }
            }

            $previewurl = null;
            foreach ($apiPublication->attachments as $attachment) {
                //TODO check flavors
                if ('presentation/search+preview' == $attachment->flavor) {
                    $previewurl = $attachment->url;
                    // prefer presentation/search+preview over presenter/search+preview
                    break;
                } else if ('presenter/search+preview' == $attachment->flavor) {
                    $previewurl = $attachment->url;
                    // continue searching for a presentation/search+preview
                }
            }

            $nonPreviewTracks = array_filter($apiPublication->media, function ($track) {
                return ! in_array("preview", $track->tags);
            });

            $downloadurl = null;
            foreach ($nonPreviewTracks as $track) {
                //TODO check flavors
                if ('composite/sbs' == $track->flavor) {
                    $downloadurl = $track->url;
                    break;
                }
                if ('presentation/delivery' == $track->flavor && 'video/mp4' == $track->mimetype) {
                    $downloadurl = $track->url;
                }
            }
            
            $episode = array(
                "title" => $readyEpisode->title,
                "date" => $readyEpisode->start,
                "series_id" => $readyEpisode->is_part_of,
                "episode_id" => $readyEpisode->identifier,
                "previewurl" => $previewurl,
                "downloadurl" => $downloadurl,
                "viewurl" => $this->getLinkForShowEpisode($this->getOCObject()->getSeriesId(), $readyEpisode->identifier, false)
            );
            if ($this->getOCObject()->getManualRelease()) {
                $episode["publishurl"] = $this->getLinkForEpisodeUnescaped($published ? "retract" : "publish", $readyEpisode->identifier);
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
            'title' => $event->title,
            'episode_id' => $event->identifier,
            'deletescheduledurl' => $this->getLinkForEpisodeUnescaped("deletescheduled", (string) $event->identifier)
        );
        $scheduled_episode['startdate'] = $event->start;
        $scheduled_episode['duration'] = $event->duration;
        $scheduled_episode['location'] = $event->location;
        return $scheduled_episode;
    }

    private function extractOnholdEpisode($event)
    {
        $onhold_episode = array(
            'title' => $event->title,
            'trimurl' => $this->getLinkForEpisodeUnescaped("showTrimEditor", (string) $event->identifier),
            'date' => $event->start
        );
        return $onhold_episode;
    }

    /**
     *
     * @param string $cmd
     * @param string $id
     * @return string
     */
    private function getLinkForEpisodeUnescaped(string $cmd, string $id)
    {
        global $DIC;
        $DIC->ctrl()->setParameterByClass("ilobjopencastgui", self::QUERY_EPISODE_IDENTIFIER, $id);
        $link = $DIC->ctrl()->getLinkTargetByClass("ilobjopencastgui", $cmd, "", false, false);
        $DIC->ctrl()->clearParameterByClass("ilobjopencastgui", self::QUERY_EPISODE_IDENTIFIER);
        return $link;
    }

    /**
     *
     * @param string $series_id
     * @param string $episode_id
     * @param bool $escaped
     * @return string
     */
    private function getLinkForShowEpisode(string $series_id, string $episode_id, bool $escaped)
    {
        global $DIC;
        $DIC->ctrl()->setParameterByClass("ilobjopencastgui", self::QUERY_MEDIAPACKAGE_ID, $series_id . "/" . $episode_id);
        $link = $DIC->ctrl()->getLinkTargetByClass("ilobjopencastgui", "showEpisode", "", false, $escaped);
        $DIC->ctrl()->clearParameterByClass("ilobjopencastgui", self::QUERY_MEDIAPACKAGE_ID);
        return $link;
    }

    /**
     * Get Episodes as json
     */
    public function getEpisodes()
    {
        $series = $this->getOCObject()->getSeries();
        $process_items = $series->getProcessingEpisodes();

        usort($process_items, array(
            $this,
            'sortbydate'
        ));
        foreach ($process_items as $key => $value) {
            $process_items[$key]["date"] = ilDatePresentation::formatDate(new ilDateTime($value["date"], IL_CAL_ISO_8601));
        }

        $finished_episodes = $this->getReadyEpisodes(false);
        foreach ($finished_episodes as $key => $value) {
            $finished_episodes[$key]["date"] = ilDatePresentation::formatDate(new ilDateTime($value["date"], IL_CAL_ISO_8601));
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
            $scheduled_items[$key]["startdate"] = ilDatePresentation::formatDate(new ilDateTime($value["startdate"], IL_CAL_ISO_8601));
            $stopDate = new ilDateTime($value["startdate"], IL_CAL_ISO_8601);
            $stopDate->increment(iLDateTime::MINUTE, $value["duration"] / 60000);
            $scheduled_items[$key]["stopdate"] = ilDatePresentation::formatDate($stopDate);
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
            $onhold_items[$key]["date"] = ilDatePresentation::formatDate(new ilDateTime($value["date"], IL_CAL_ISO_8601));
        }
        
        $data = array();
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

    /**
     * Init Upload form.
     *
     * @return ilPropertyFormGUI
     */
    private function initUploadForm()
    {
        global $DIC;

        $form = new ilPropertyFormGUI();
        $form->setId('episode_upload');
        $form->setTitle($this->txt("add_new_episode"));

        $form->setPreventDoubleSubmission(false);
        $flag = new ilHiddenInputGUI('submitted');
        $flag->setValue('1');
        $form->addItem($flag);

        // title
        $ti = new ilTextInputGUI($this->txt("track_title"), self::POST_EPISODENAME);
        $ti->setRequired(true);
        $form->addItem($ti);

        // presenter
        $presenter = new ilTextInputGUI($this->txt("track_presenter"), self::POST_PRESENTER);
        $presenter->setRequired(false);
        $form->addItem($presenter);

        // datetime
        $datetime = new ilDateTimeInputGUI($this->txt("track_datetime"), self::POST_EPISODEDATETIME);
        $datetime->setShowTime(true);
        $datetime->setRequired(true);
        $form->addItem($datetime);

        // usetrimeditor
        $usetrimeditor = new ilCheckboxInputGUI($this->txt("usetrimeditor"), self::POST_USETRIMEDITOR);
        $form->addItem($usetrimeditor);

        $item = new ilFileStandardDropzoneInputGUI('Files', 'files');
        $item->setUploadUrl($form->getFormAction());
        $item->setSuffixes(self::UPLOAD_SUFFIXES);
        $item->setInfo('Allowed file types: ' . implode(', ', $item->getSuffixes()));
        $form->addItem($item);

        $form->addCommandButton("editUpload", $this->txt("upload_file"));
        $form->setFormAction($DIC->ctrl()
            ->getFormAction($this));
        return $form;
    }

    /**
     * Handle a upload request from the form.
     * Move the uploaded files to UPLOAD_DIR and upload them to opencast.
     *
     * @param ilPropertyFormGUI $form
     *            the upload form
     */
    private function handleUpload(ilPropertyFormGUI $form)
    {
        global $DIC;
        $ilCtrl = $DIC->ctrl();
        if ($form->checkInput()) {
            $upload = $DIC->upload();
            $filesystem = $DIC->filesystem()->temp();
            if (! $upload->hasUploads()) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'No file uploaded'
                ));
                exit();
            }

            try {
                $upload->process();
                $results = $upload->getResults();
                if (count($results) != 1) {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'Only one file is supported'
                    ));
                    exit();
                }
                $file = current($results);
                $filename = uniqid() . $file->getName();
                $upload->moveOneFileTo($file, self::UPLOAD_DIR, Location::TEMPORARY, $filename);
                $filepath = self::UPLOAD_DIR . "/" . $filename;
                // check if the file was store with the correct path
                if (! $filesystem->has($filepath)) {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'Cloud not store file'
                    ));
                    exit();
                }
                $title = $form->getInput(self::POST_EPISODENAME);
                $creator = $form->getInput(self::POST_PRESENTER);
                $datetime = new ilDateTime($form->getInput(self::POST_EPISODEDATETIME), IL_CAL_DATETIME);
                $flagForCutting = isset($_POST[self::POST_USETRIMEDITOR]) && $_POST[self::POST_USETRIMEDITOR];

                $this->getOCObject()
                    ->getSeries()
                    ->createEpisode($title, $creator, $datetime->get(IL_CAL_ISO_8601), $flagForCutting, self::ILIAS_TEMP_DIR . "/" . $filepath);

                $filesystem->delete($filepath);

                ilUtil::sendSuccess($this->txt("msg_episode_uploaded"), true);
                $ilCtrl->redirect($this, "editTrimProcess");
            } catch (Exception $e) {
                ilLoggerFactory::getLogger('xoc')->debug("Exception while uploading to opencast: " . $e->getMessage());
                echo json_encode(array(
                    'success' => false
                ));
            }
            exit();
        } else {
            $form->setValuesByPost();
        }
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
        $seriestpl->setVariable("CMD_PROCESSING", $ilCtrl->getLinkTarget($this, "getEpisodes", "", true));
        $seriestpl->setVariable("SERIES_ID", $this->getOCObject()->getSeriesId());
        $seriestpl->setVariable("MANUAL_RELEASE", $this->getOCObject()->getManualRelease());
        $seriestpl->parseCurrentBlock();
        $jsConfig = $seriestpl->get();
        ilLoggerFactory::getLogger('xoc')->debug($section);
        switch ($section) {
            case 'finished':
                $ilTabs->activateSubTab('finishedepisodes');
                
                $colums = array(
                    $this->getText("title"),
                    $this->getText("preview"),
                    $this->getText("date")
                );
                if ($this->getOCObject()->getManualRelease()) {
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
                $form = $this->initUploadForm();

                // Check for submission
                if (isset($_POST['submitted']) && $_POST['submitted']) {
                    $this->handleUpload($form);
                }

                $content = $factory->legacy($form->getHTML());
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
        $episode = $this->getOCObject()->getEpisode($_GET[self::QUERY_EPISODE_IDENTIFIER]);
        if ($episode) {
            $id = $episode->getEpisodeId();
            ilLoggerFactory::getLogger('xoc')->debug("Trimming episode: $id");
            $episodeInfo = $episode->getEpisode();
            $media = $episode->getMedia();
            if (count($media) != 1) {
                throw new Exception("something is wrong with media.");
            }
            $previewTrack = null;
            foreach ($media as $track) {
                if (in_array("preview",$track->tags)) {
                    $previewTrack = $track;
                    break;
                }
            }
            if ($previewTrack == null) {
                throw new Exception("There is no preview Track.");
            }
            
            $streamType = null;
            switch ($previewTrack->flavor) {
                case "presentation/preview":
                    $streamType = "presentation";
                    ilLoggerFactory::getLogger('xoc')->debug("Found presentation track");
                    break;
                case "presenter/preview":
                    $streamType = "presenter";
                    break;
                case "composite/preview":
                    $streamType = "dual";
                    break;
                default:
                    throw new Exception("Unknown media flavor for preview: " . $previewTrack->flavor);
                    break;
            }
            
            $trimview = $this->getPlugin()->getTemplate("default/tpl.trimview.html", true, true);
            $trimview->setCurrentBlock("formstart");
            $trimview->setVariable("TXT_TRACK_TITLE", $this->getText("track_title"));
            $trimview->setVariable("TRACKTITLE", $episodeInfo->title);
            $trimview->setVariable("INITJS", $trimbase);
            $trimview->setVariable("CMD_TRIM", $ilCtrl->getFormAction($this, "trimEpisode"));
            $trimview->setVariable("WFID", $id);
            $trimview->parseCurrentBlock();
            if ($streamType == "dual") {
                $trimview->setCurrentBlock("dualstream");
                $trimview->setVariable("TXT_LEFT_TRACK", $this->getText("keep_left_side"));
                $trimview->setVariable("TXT_RIGHT_TRACK", $this->getText("keep_right_side"));
                $trimview->parseCurrentBlock();
            } else {
                $trimview->setCurrentBlock("singlestream");
                $trimview->setVariable("SINGLETRACK", $streamType);
                $trimview->parseCurrentBlock();
            }
            $trimview->setCurrentBlock("video");
            $trimview->setVariable("TXT_DOWNLOAD_PREVIEW", $this->getText("download_preview"));
            $downloadurlmp4 = $previewTrack->url;
            $trimview->setVariable("DOWNLOAD_PREVIEW_URL_MP4", $downloadurlmp4);
            
            $duration = $previewTrack->duration;
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
            $DIC->tabs()->activateTab("manage");
        } else {
            $ilCtrl->redirect($this, "editTrimProcess");
        }
    }

    public function trimEpisode()
    {
        global $ilCtrl;
        $episode = $this->getOCObject()->getEpisode($_POST["eventid"]);
        if ($episode) {
            $title = (string) ilUtil::stripScriptHTML($_POST["tracktitle"]);
            if ($title) {
                $episode->setTitle($title);
            }
            $keeptracks = [];
            if (isset($_POST["lefttrack"])) {
                $keeptracks[] = "presenter";
            }
            if (isset($_POST["righttrack"])) {
                $keeptracks[] = "presentation";
            }
            if (isset($_POST["singletrack"])) {
                $keeptracks[] = (string) ilUtil::stripScriptHTML($_POST["singletrack"]);
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
            ilLoggerFactory::getLogger('xoc')->debug("ID does not match an episode:" . $_POST["eventid"]);
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
        $info->addProperty($this->getText("series_id"), $this->getOCObject()->getSeriesId());
    }
}
