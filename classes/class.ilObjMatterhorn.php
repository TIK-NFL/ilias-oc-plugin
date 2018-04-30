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
require_once 'Services/Repository/classes/class.ilObjectPlugin.php';
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn')->includeClass('class.ilMatterhornConfig.php');

/**
 * Application class for matterhorn repository object.
 *
 * @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilObjMatterhorn extends ilObjectPlugin
{

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
        $this->getPlugin()->includeClass("opencast/class.ilOpencastAPI.php");
        $seriesxml = ilOpencastAPI::getInstance()->createSeries($this->getTitle(), $this->getDescription(), $this->getId(), $this->getRefId());
        
        ilLoggerFactory::getLogger('xmh')->info("Created new opencast object on server: $seriesxml");
        $ilDB->manipulate("INSERT INTO rep_robj_xmh_data (obj_id, is_online, viewmode,manualrelease,download,fsinodupdate) VALUES (" . $ilDB->quote($this->getId(), "integer") . "," . $ilDB->quote(0, "integer") . "," . $ilDB->quote(0, "integer") . "," . $ilDB->quote(1, "integer") . "," . $ilDB->quote(0, "integer") . "," . $ilDB->quote(0, "integer") . ")");
        $this->createMetaData();
    }

    /**
     * Read data from db
     */
    public function doRead()
    {
        global $ilDB;
        
        $set = $ilDB->query("SELECT * FROM rep_robj_xmh_data WHERE obj_id = " . $ilDB->quote($this->getId(), "integer"));
        while ($rec = $ilDB->fetchAssoc($set)) {
            $this->setOnline($rec["is_online"]);
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
        $this->getPlugin()->includeClass("opencast/class.ilOpencastAPI.php");
        $httpCode = ilOpencastAPI::getInstance()->updateSeries($this->getTitle(), $this->getDescription(), $this->getId(), $this->getRefId());
        
        ilLoggerFactory::getLogger('xmh')->info("Updated opencast object on server: $httpCode");
        if (204 == $httpCode) {
            $ilDB->manipulate("UPDATE rep_robj_xmh_data SET is_online = " . $ilDB->quote($this->getOnline(), "integer") . ", viewmode = " . $ilDB->quote($this->getViewMode(), "integer") . ", manualrelease = " . $ilDB->quote($this->getManualRelease(), "integer") . ", download = " . $ilDB->quote($this->getDownload(), "integer") . " WHERE obj_id = " . $ilDB->quote($this->getId(), "text"));
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
        
        foreach ($this->getReleasedEpisodeIds() as $episode_id) {
            ilMatterhornUserTracking::removeViews($this->getEpisode($episode_id));
        }
        
        $ilDB->manipulate("DELETE FROM rep_robj_xmh_rel_ep " . " WHERE series_id = " . $ilDB->quote($this->getId(), "text"));
        
        $ilDB->manipulate("DELETE FROM rep_robj_xmh_data WHERE " . " obj_id = " . $ilDB->quote($this->getId(), "integer"));
    }

    /**
     *
     * @return array
     */
    public function getSeriesInformationFromOpencast()
    {
        $this->getPlugin()->includeClass("opencast/class.ilOpencastAPI.php");
        $seriesxml = ilOpencastAPI::getInstance()->getSeries($this->getId());
        $xml = new SimpleXMLElement($seriesxml);
        $children = $xml->children("http://purl.org/dc/terms/");
        $series = array(
            "series" => $seriesxml,
            "title" => (string) $children->title,
            "description" => (string) $children->description,
            "publisher" => (string) $children->publisher,
            "identifier" => (string) $children->identifier
        );
        return $series;
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

    /**
     * The series information returned by matterhorn
     *
     * @return array the episodes by matterhorn for the this series
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
    public function getReleasedEpisodeIds()
    {
        global $DIC;
        
        $set = $DIC->database()->query("SELECT episode_id FROM rep_robj_xmh_rel_ep WHERE series_id = " . $DIC->database()
            ->quote($this->getId(), "integer"));
        $released = array();
        while ($rec = $DIC->database()->fetchAssoc($set)) {
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
        $this->getPlugin()->includeClass("opencast/class.ilOpencastAPI.php");
        return ilOpencastAPI::getInstance()->getScheduledEpisodes($this->getId());
    }

    /**
     * Get the episodes which are on hold for this series
     *
     * @return array the episodes which are on hold for this series returned by matterhorn
     */
    public function getOnHoldEpisodes()
    {
        $this->getPlugin()->includeClass("opencast/class.ilOpencastAPI.php");
        return ilOpencastAPI::getInstance()->getOnHoldEpisodes($this->getId());
    }

    /**
     * Get the episodes which are on hold for this series
     *
     * @return array the episodes which are on hold for this series returned by matterhorn
     */
    public function getProcessingEpisodes()
    {
        $this->getPlugin()->includeClass("opencast/class.ilOpencastAPI.php");
        return ilOpencastAPI::getInstance()->getProcessingEpisodes($this->getId());
    }

    /**
     * Trims the tracks of a workflow
     *
     * @param Integer $eventid
     *            the workflow id
     * @param String $keeptrack
     *            the id of the track to be removed
     * @param Float $trimin
     *            the start time of the new tracks
     * @param Float $trimout
     *            the endtime of the video
     */
    public function trim($eventid, $keeptracks, $trimin, $trimout)
    {
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
