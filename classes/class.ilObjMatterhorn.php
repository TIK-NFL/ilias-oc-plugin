<?php
/*
 * +-----------------------------------------------------------------------------+
 * | ILIAS open source |
 * +-----------------------------------------------------------------------------+
 * | Copyright (c) 1998-2009 ILIAS open source, University of Cologne |
 * | |
 * | This program is free software; you can redistribute it and/or |
 * | modify it under the terms of the GNU General Public License |
 * | as published by the Free Software Foundation; either version 2 |
 * | of the License, or (at your option) any later version. |
 * | |
 * | This program is distributed in the hope that it will be useful, |
 * | but WITHOUT ANY WARRANTY; without even the implied warranty of |
 * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the |
 * | GNU General Public License for more details. |
 * | |
 * | You should have received a copy of the GNU General Public License |
 * | along with this program; if not, write to the Free Software |
 * | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
 * +-----------------------------------------------------------------------------+
 */
require_once 'Services/Repository/classes/class.ilObjectPlugin.php';
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn')->includeClass('class.ilMatterhornConfig.php');

/**
 * Application class for matterhorn repository object.
 *
 * @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
 *        
 *         $Id$
 *        
 */
class ilObjMatterhorn extends ilObjectPlugin
{

    /**
     * Stores the series
     *
     * @var string
     */
    private $series;

    /**
     * Stores the mhretval
     *
     * @var string
     */
    private $mhretval;

    /**
     * Stores the lectureID
     *
     * @var string
     */
    private $lectureID;

    /**
     * Stores the viewmode
     *
     * @var integer
     */
    private $viewMode;

    /**
     * Stores the manual release
     *
     * @var boolean
     */
    private $manualrelease;

    /**
     * Stores the download status
     *
     * @var boolean
     */
    private $download;

    /**
     * Stores the last time the fs was checked for new updates
     *
     * @unused
     *
     * @var integer
     */
    private $lastfsInodeUpdate;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);
        $this->configObject = new ilMatterhornConfig();
    }

    /**
     * Get type.
     */
    final public function initType()
    {
        $this->setType("xmh");
    }

    /**
     * Create object
     */
    public function doCreate()
    {
        global $ilDB;
        $url = $this->configObject->getMatterhornServer() . "/series/";
        $fields = $this->createPostFields();
        // url-ify the data for the POST
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        ilLoggerFactory::getLogger('xmh')->info("Created new opencast object on server: " + $httpCode);
        $ilDB->manipulate("INSERT INTO rep_robj_xmh_data " . "(obj_id, is_online, series, mhretval, lectureid,viewmode,manualrelease,download,fsinodupdate) VALUES (" . $ilDB->quote($this->getId(), "integer") . "," . $ilDB->quote(0, "integer") . "," . $ilDB->quote($result, "text") . "," . $ilDB->quote($httpCode, "text") . "," . $ilDB->quote($this->getLectureID(), "text") . "," . $ilDB->quote(0, "integer") . "," . $ilDB->quote(1, "integer") . "," . $ilDB->quote(0, "integer") . "," . $ilDB->quote(0, "integer") . ")");
        $this->createMetaData();
    }

    /**
     * Read data from db
     */
    public function doRead()
    {
        global $ilDB;
        
        $set = $ilDB->query("SELECT * FROM rep_robj_xmh_data " . " WHERE obj_id = " . $ilDB->quote($this->getId(), "integer"));
        while ($rec = $ilDB->fetchAssoc($set)) {
            $this->setOnline($rec["is_online"]);
            $this->setSeries($rec["series"]);
            $this->setMhRetVal($rec["mhretval"]);
            $this->setLectureID($rec["lectureid"]);
            $this->setViewMode($rec["viewmode"]);
            $this->setManualRelease($rec["manualrelease"]);
            $this->setDownload($rec["download"]);
            $this->setLastFSInodeUpdate($rec["fsinodupdate"]);
        }
    }

    /**
     * Update data
     */
    public function doUpdate()
    {
        global $ilDB;
        
        $url = $this->configObject->getMatterhornServer() . "/series/";
        $fields = $this->createPostFields();
        
        // url-ify the data for the POST
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        ilLoggerFactory::getLogger('xmh')->info("Updated opencast object on server: " . $httpCode);
        ilLoggerFactory::getLogger('xmh')->debug($result);
        if (204 == $httpCode) {
            $url = $this->configObject->getMatterhornServer() . "/series/" . $this->configObject->getSeriesPrefix() . $this->getId() . ".xml";
            // open connection
            $ch = curl_init();
            
            // set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "X-Requested-Auth: Digest",
                "X-Opencast-Matterhorn-Authorization: true"
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            ilLoggerFactory::getLogger('xmh')->info("Retrieve current series from server: " . $httpCode);
            ilLoggerFactory::getLogger('xmh')->debug($result);
            $ilDB->manipulate("UPDATE rep_robj_xmh_data SET " . " is_online = " . $ilDB->quote($this->getOnline(), "integer") . "," . " series = " . $ilDB->quote($result, "text") . "," . " lectureid = " . $ilDB->quote($this->getLectureID(), "text") . "," . " viewmode = " . $ilDB->quote($this->getViewMode(), "integer") . "," . " manualrelease = " . $ilDB->quote($this->getManualRelease(), "integer") . "," . " download = " . $ilDB->quote($this->getDownload(), "integer") . "," . " mhretval = " . $ilDB->quote($httpCode, "text") . " " . " WHERE obj_id = " . $ilDB->quote($this->getId(), "text"));
            $this->updateMetaData();
            $this->doRead();
        }
    }

    /**
     * Delete data from db
     */
    public function doDelete()
    {
        global $ilDB;
        
        $this->getPlugin()->includeClass("class.ilMatterhornUserTracking.php");
        
        foreach ($this->getReleasedEpisodes() as $episode_id) {
            ilMatterhornUserTracking::removeViews($this->getEpisode($episode_id));
        }
        
        $ilDB->manipulate("DELETE FROM rep_robj_xmh_rel_ep " . " WHERE series_id = " . $ilDB->quote($this->getId(), "text"));
        
        $ilDB->manipulate("DELETE FROM rep_robj_xmh_data WHERE " . " obj_id = " . $ilDB->quote($this->getId(), "integer"));
    }

    /**
     * Do Cloning
     */
    public function doCloneObject($neiw_obj, $a_target_id, $a_copy_id = null)
    {
        // $new_obj->setSeries($this->getSeries());
        // $new_obj->setMhRetVal($this->getMhRetVal());
        // $new_obj->setOnline($this->getOnline());
        // $new_obj->update();
    }

    private function createPostFields()
    {
        global $ilUser;
        
        $userid = $ilUser->getLogin();
        if (null != $ilUser->getExternalAccount) {
            $userid = $ilUser->getExternalAccount();
        }
        $acl = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><acl xmlns="http://org.opencastproject.security">
								<ace><role>' . $userid . '</role><action>read</action><allow>true</allow></ace>
								<ace><role>' . $userid . '</role><action>write</action><allow>true</allow></ace>
						</acl>';
        $fields = array(
            'series' => urlencode('<?xml version="1.0"?>
<dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.opencastproject.org http://www.opencastproject.org/schema.xsd" xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/" xmlns:oc="http://www.opencastproject.org/matterhorn/">
		
  <dcterms:title xml:lang="en">ILIAS-' . $this->getId() . ':' . $this->getRefId() . ':' . $this->getTitle() . '</dcterms:title>
  <dcterms:subject>
    </dcterms:subject>
  <dcterms:description xml:lang="en">' . $this->getDescription() . '</dcterms:description>
  <dcterms:publisher>
    University of Stuttgart, Germany
    </dcterms:publisher>
  <dcterms:identifier>
    ' . $this->configObject->getSeriesPrefix() . $this->getId() . '</dcterms:identifier>
  <dcterms:references>' . $this->getLectureID() . '</dcterms:references>
  <dcterms:modified xsi:type="dcterms:W3CDTF">' . date("Y-m-d") . '</dcterms:modified>
  <dcterms:format xsi:type="dcterms:IMT">
    video/mp4
    </dcterms:format>
  <oc:promoted>
   	false
  </oc:promoted>
</dublincore>'),
            'acl' => urlencode($acl)
        );
        return $fields;
    }

    /**
     * @unused
     */
    public function updateSearchRecords()
    {
        // ilLoggerFactory::getLogger('xmh')->debug("updating search for ".$this->getId());
        $manifest = new SimpleXMLElement($this->configObject->getDistributionDirectory() . $this->configObject->getSeriesPrefix() . $this->obj_id . '/' . $this->episode_id . '/manifest.xml', null, true);
    }

    //
    // Set/Get Methods for the properties
    //
    
    /**
     * Set online
     *
     * @param
     *            boolean online
     */
    public function setOnline($a_val)
    {
        $this->online = $a_val;
    }

    /**
     * Get online
     *
     * @return boolean online
     */
    public function getOnline()
    {
        return $this->online;
    }

    /**
     * Set series information
     *
     * @param String $a_val
     *            series
     */
    public function setSeries($a_val)
    {
        $this->series = $a_val;
    }

    /**
     * Get series information
     *
     * @return String series
     */
    public function getSeries()
    {
        return $this->series;
    }

    /**
     * Set the http return code when creating the series
     *
     * @param int $a_val
     *            mhretval
     */
    public function setMhRetVal($a_val)
    {
        $this->mhretval = $a_val;
    }

    /**
     * Get the http return code when creating the series
     *
     * @return int mhretval
     */
    public function getMhRetVal()
    {
        return $this->mhretval;
    }

    /**
     * Set the lectureID
     *
     * @param String $a_val
     *            lectureID
     */
    public function setLectureID($a_val)
    {
        $this->lectureID = $a_val;
    }

    /**
     * Get the lectureID
     *
     * @return string lectureID
     */
    public function getLectureID()
    {
        return $this->lectureID;
    }

    /**
     * Set the ViewMode
     *
     * @param Integer $a_val
     *            viewMode
     */
    public function setViewMode($a_val)
    {
        $this->viewMode = $a_val;
    }

    /**
     * Get the ViewMode
     *
     * @return Integer viewMode
     */
    public function getViewMode()
    {
        return $this->viewMode;
    }

    /**
     * Set manual release
     *
     * @param boolean $a_val
     *            manual release
     */
    public function setManualRelease($a_val)
    {
        $this->manualrelease = $a_val;
    }

    /**
     * Get manual release
     *
     * @return boolean manualrelease
     */
    public function getManualRelease()
    {
        return $this->manualrelease;
    }

    /**
     * Set enable download
     *
     * @param boolean $a_val
     *            enable download
     */
    public function setDownload($a_val)
    {
        $this->download = $a_val;
    }

    /**
     * Get download enabled
     *
     * @return boolean download enabled
     */
    public function getDownload()
    {
        return $this->download;
    }

    /**
     * Set lastfsInodeUpdate
     *
     * @param int $a_val
     *            the timestamp of the last inode update
     */
    public function setLastFSInodeUpdate($a_val)
    {
        $this->lastfsInodeUpdate = $a_val;
    }

    /**
     * Get lastfsInodeUpdate
     *
     * @return int the timestamp of the last inode update
     */
    public function getLastFSInodeUpdate()
    {
        $filename = $this->configObject->getDistributionDirectory() . $this->configObject->getSeriesPrefix() . $this->getId();
        if (file_exists($filename)) {
            return filemtime($filename);
        }
        return - 1;
    }

    /**
     * checks if the $episodeId exists and returns the Episode object
     *
     * @param string $episodeId            
     * @return ilMatterhornEpisode
     */
    public function getEpisode($episodeId)
    {
        $this->getPlugin()->includeClass("class.ilMatterhornEpisode.php");
        if (preg_match('/^[0-9a-f\-]+/', $episodeId)) {
            return new ilMatterhornEpisode($this->getId(), $episodeId);
        }
        return null;
    }

    public function deleteschedule($workflowid)
    {
        $url = $this->configObject->getMatterhornServer() . '/admin-ng/event/' . $workflowid;
        
        // open connection
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Requested-Auth: Digest',
            'X-Opencast-Matterhorn-Authorization: true'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        ilLoggerFactory::getLogger('xmh')->debug("delete code: " . $httpCode);
        return $httpCode;
    }

    /**
     * The series information returned by matterhorn
     *
     * @return array the episodes by matterhorn for the given seris
     */
    public function getSearchResult()
    {
        $basedir = $this->configObject->getDistributionDirectory() . $this->configObject->getSeriesPrefix() . $this->getId();
        $xmlstr = "<?xml version='1.0' standalone='yes'?>\n<results />";
        $resultcount = 0;
        $results = new SimpleXMLElement($xmlstr);
        $domresults = dom_import_simplexml($results);
        if (file_exists($basedir) && $handle = opendir($basedir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && file_exists($basedir . '/' . $entry . '/manifest.xml')) {
                    $manifest = new SimpleXMLElement($basedir . '/' . $entry . '/manifest.xml', null, true);
                    $dommanifest = dom_import_simplexml($manifest);
                    $dommanifest = $domresults->ownerDocument->importNode($dommanifest, true);
                    $domresults->appendChild($dommanifest);
                    $resultcount ++;
                }
            }
            closedir($handle);
        }
        $results->addAttribute("total", $resultcount);
        return $results;
    }

    /**
     * Returns a list of the Episodes that have been made public available by the lecturer
     *
     * @return array containing the ids of the episodes that have been made public available.
     */
    public function getReleasedEpisodes()
    {
        global $ilDB;
        
        $set = $ilDB->query("SELECT episode_id FROM rep_robj_xmh_rel_ep " . " WHERE series_id = " . $ilDB->quote($this->getId(), "integer"));
        $released = array();
        while ($rec = $ilDB->fetchAssoc($set)) {
            array_push($released, ($rec["episode_id"]));
        }
        return $released;
    }

    /**
     * The scheduled information for this series returned by matterhorn
     *
     * @return array the scheduled episodes for this series returned by matterhorn
     */
    public function getScheduledEpisodes()
    {
        $url = $this->configObject->getMatterhornServer() . "/admin-ng/event/events.json";
        /* $_GET Parameters to Send */
        $params = array(
            'filter' => 'status:EVENTS.EVENTS.STATUS.SCHEDULED,series:' . $this->configObject->getSeriesPrefix() . $this->getId(),
            'sort' => 'date:ASC'
        );
        
        /* Update URL to container Query String of Paramaters */
        $url .= '?' . http_build_query($params);
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
        $searchResult = json_decode($curlret, true);
        
        return $searchResult;
    }

    /**
     * Get the episodes which are on hold for this series
     *
     * @return array the episodes which are on hold for this series returned by matterhorn
     */
    public function getOnHoldEpisodes()
    {
        $url = $this->configObject->getMatterhornServer() . "/admin-ng/event/events.json";
        /* $_GET Parameters to Send */
        $params = array(
            'filter' => 'status:EVENTS.EVENTS.STATUS.PROCESSED,comments:OPEN,series:' . $this->configObject->getSeriesPrefix() . $this->getId(),
            'sort'   => 'date:ASC'
        );
        
        /* Update URL to container Query String of Paramaters */
        $url .= '?' . preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', http_build_query($params, null, '&'));
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
        $searchResult = json_decode($curlret, true);
        
        if (is_array($searchResult)) {
            return $searchResult['results'];
        } else {
            return [];
        }
    }

    /**
     * Get the episodes which are on hold for this series
     *
     * @return array the episodes which are on hold for this series returned by matterhorn
     */
    public function getProcessingEpisodes()
    {
        $url = $this->configObject->getMatterhornServer() . "/workflow/instances.json";
        $params = array(
            'seriesId' => $this->configObject->getSeriesPrefix() . $this->getId(),
            'state' => array(
                '-stopped',
                'running'
            ),
            'op' => array(
                '-schedule',
                '-capture'
            )
        );
        
        /* Update URL to container Query String of Paramaters */
        $url .= '?' . preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', http_build_query($params, null, '&'));
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
        $searchResult = json_decode($curlret, true);
        
        return $searchResult;
    }

    /**
     * Get workflow
     *
     * @param
     *            Integer workflowid the workflow id
     *            
     * @return the workflow as decode json object
     */
    public function getWorkflow($workflowid)
    {
        $url = $this->configObject->getMatterhornServer() . "/workflow/instance/" . $workflowid . ".xml";
        // open connection
        $ch = curl_init();
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
        $workflow = simplexml_load_string($curlret);
        if ($workflow === false) {
            ilLoggerFactory::getLogger('xmh')->debug("error loading workflow: " . $workflowid);
            foreach (libxml_get_errors() as $error) {
                ilLoggerFactory::getLogger('xmh')->debug("error : " . $error->message);
            }
        }
        return $workflow;
    }

    /**
     * Get editor tool json from admin-ng
     *
     * @param
     *            String the id of the epsidoe
     *            
     * @return the editor json from the admin ui
     */
    public function getEditor($episodeid)
    {
        $url = $this->configObject->getMatterhornServer() . "/admin-ng/tools/" . $episodeid . "/editor.json";
        ilLoggerFactory::getLogger('xmh')->info("loading: " . $url);
        // open connection
        $ch = curl_init();
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
        $editorjson = json_decode($curlret);
        if ($editorjson === false) {
            ilLoggerFactory::getLogger('xmh')->error("error loading editor.json for episode " . $episodeid);
        }
        return $editorjson;
    }

    /**
     * Get the media objects json from admin-ng
     *
     * @param
     *            String the id of the epsidoe
     *            
     * @return the media json from the admin ui
     */
    public function getMedia($episodeid)
    {
        $url = $this->configObject->getMatterhornServer() . "/admin-ng/event/" . $episodeid . "/asset/media/media.json";
        ilLoggerFactory::getLogger('xmh')->info("loading: " . $url);
        // open connection
        $ch = curl_init();
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
        $mediajson = json_decode($curlret);
        if ($mediajson === false) {
            ilLoggerFactory::getLogger('xmh')->error("error loading media for episode " . $episodeid);
        }
        return $mediajson;
    }

    /**
     * Get dublincore
     *
     * @param
     *            Integer workflowid the workflow id
     *            
     * @return the workflow as decode json object
     */
    public function getDublinCore($dublincoreurl)
    {
        // open connection
        $ch = curl_init();
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $dublincoreurl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
        $dublincore = simplexml_load_string($curlret);
        if ($dublincore === false) {
            ilLoggerFactory::getLogger('xmh')->error("error loading dublincore: " . $dublincoreurl);
            foreach (libxml_get_errors() as $error) {
                ilLoggerFactory::getLogger('xmh')->error("error : " . $error->message);
            }
        }
        return $dublincore;
    }

    /**
     * Set dublincore
     *
     * @param
     *            Integer workflowid the workflow id
     *            
     * @return the workflow as decode json object
     */
    public function setDublinCore($dublincoreurl, $content)
    {
        ilLoggerFactory::getLogger('xmh')->debug($httpCode . $content);
        // open connection
        $ch = curl_init();
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $dublincoreurl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // add episode.xml to media package
        $fields = array(
            'content' => urlencode($content)
        );
        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $curlret = curl_exec($ch);
        ilLoggerFactory::getLogger('xmh')->debug($httpCode . $curlret);
        // return $dublincore;
    }

    /**
     * Trims the tracks of a workflow
     *
     * @param
     *            Integer workflowid the workflow id
     * @param
     *            String keeptrack the id of the track to be removed
     * @param
     *            Float trimin the start time of the new tracks
     * @param
     *            Float trimout the endtime of the video
     */
    public function trim($eventid, $keeptracks, $trimin, $trimout)
    {
        $mp = $mediapackage;
        // open connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true",
            'Content-Type: application/json',
            'charset=UTF-8',
            'Connection: Keep-Alive'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $url = $this->configObject->getMatterhornServer() . "/admin-ng/tools/" . $eventid . "/editor.json";
        // ',"startTime":"00:00:02.818","endTime":"00:00:34.320","deleted":false}],'.
        
        $fields_string = '{"concat":{"segments":[{"start":' . (1000 * $trimin) . ',"end":' . (1000 * $trimout) . ',"deleted":false}],' . '"tracks":["' . implode('","', $keeptracks) . '"]},"workflow":"ilias-publish-after-cutting"}';
        
        ilLoggerFactory::getLogger('xmh')->debug("FIELDSTRING:" . $fields_string);
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $mp = curl_exec($ch);
        if (! curl_errno($ch)) {
            $info = curl_getinfo($ch);
            ilLoggerFactory::getLogger('xmh')->debug('Successful request to ' . $info['url'] . ' in ' . $info['total_time']);
        }
        ilLoggerFactory::getLogger('xmh')->debug($mp);
    }
}
