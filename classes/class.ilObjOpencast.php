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
use TIK_NFL\ilias_oc_plugin\api\ilOpencastUserTracking;
use TIK_NFL\ilias_oc_plugin\model\ilOpencastEpisode;
use TIK_NFL\ilias_oc_plugin\model\ilOpencastSeries;
use TIK_NFL\ilias_oc_plugin\opencast\ilOpencastAPI;
use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;

require_once 'Services/Repository/classes/class.ilObjectPlugin.php';
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast')->includeClass('class.ilOpencastConfig.php');

/**
 * Application class for Opencast repository object.
 *
 * @author Per Pascal Seeland <pascal.seeland@tik.uni-stuttgart.de>
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilObjOpencast extends ilObjectPlugin
{

    /**
     * The Opencast series id
     *
     * @var string
     */
    private $series_id;

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
     * Constructor
     *
     * @access public
     */
    public function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);
    }

    /**
     * Get type.
     */
    final public function initType()
    {
        $this->setType("xoc");
    }

    protected function beforeCreate()
    {
        $this->getPlugin()->includeClass("opencast/class.ilOpencastAPI.php");
        return ilOpencastAPI::getInstance()->checkOpencast();
    }

    /**
     * Create object
     */
    protected function doCreate()
    {
        global $ilDB;
        $new_series_id = ilOpencastAPI::getInstance()->createSeries($this->getTitle(), $this->getDescription(), $this->getId(), 0);

        ilLoggerFactory::getLogger('xoc')->info("Created new opencast object on server: $new_series_id");
        $ilDB->manipulate("INSERT INTO " . ilOpencastConfig::DATABASE_TABLE_DATA . " (obj_id, series_id, is_online, viewmode,manualrelease,download) VALUES (" . $ilDB->quote($this->getId(), "integer") . "," . $ilDB->quote($new_series_id, "string") . "," . $ilDB->quote(0, "integer") . "," . $ilDB->quote(0, "integer") . "," . $ilDB->quote(1, "integer") . "," . $ilDB->quote(0, "integer") . ")");
        $this->createMetaData();
    }

    /**
     * Read data from db
     */
    public function doRead()
    {
        global $ilDB;

        $set = $ilDB->query("SELECT * FROM " . ilOpencastConfig::DATABASE_TABLE_DATA . " WHERE obj_id = " . $ilDB->quote($this->getId(), "integer"));
        while ($rec = $ilDB->fetchAssoc($set)) {
            $this->setSeriesId((string) $rec["series_id"]);
            $this->setOnline((bool) $rec["is_online"]);
            $this->setViewMode((int) $rec["viewmode"]);
            $this->setManualRelease((bool) $rec["manualrelease"]);
            $this->setDownload((bool) $rec["download"]);
        }
    }

    /**
     * Update data
     */
    public function doUpdate()
    {
        global $ilDB;
        $this->getPlugin()->includeClass("opencast/class.ilOpencastAPI.php");
        ilOpencastAPI::getInstance()->updateSeries($this->getSeriesId(), $this->getTitle(), $this->getDescription(), $this->getId(), $this->getRefId());

        $ilDB->manipulate("UPDATE " . ilOpencastConfig::DATABASE_TABLE_DATA . " SET is_online = " . $ilDB->quote($this->getOnline(), "integer") . ", viewmode = " . $ilDB->quote($this->getViewMode(), "integer") . ", manualrelease = " . $ilDB->quote($this->getManualRelease(), "integer") . ", download = " . $ilDB->quote($this->getDownload(), "integer") . " WHERE obj_id = " . $ilDB->quote($this->getId(), "text"));
        $this->updateMetaData();
        $this->doRead();
    }

    /**
     * Delete data from db
     */
    public function doDelete()
    {
        global $ilDB;

        $this->getPlugin()->includeClass("api/class.ilOpencastUserTracking.php");

        foreach ($this->getReleasedEpisodeIds() as $episode_id) {
            ilOpencastUserTracking::removeViews($this->getEpisode($episode_id));
        }

        $ilDB->manipulate("DELETE FROM " . ilOpencastConfig::DATABASE_TABLE_RELEASED_EPISODES . " WHERE series_id = " . $ilDB->quote($this->getSeriesId(), "text"));

        $ilDB->manipulate("DELETE FROM " . ilOpencastConfig::DATABASE_TABLE_DATA . " WHERE obj_id = " . $ilDB->quote($this->getId(), "integer"));

        // The series is not deleted in opencast
    }

    //
    // Set/Get Methods for the properties
    //

    /**
     * The Opencast series id associated with this ilias object.
     *
     * @param string $series_id
     */
    private function setSeriesId(string $series_id)
    {
        $this->series_id = $series_id;
    }

    /**
     * The Opencast series id associated with this ilias object.
     *
     * @return string
     */
    public function getSeriesId()
    {
        return $this->series_id;
    }

    /**
     * Set online
     *
     * @param boolean $a_val
     */
    public function setOnline(bool $a_val)
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
    public function setViewMode(int $a_val)
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
    public function setManualRelease(bool $a_val)
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
    public function setDownload(bool $a_val)
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
     *
     * @return ilOpencastSeries
     */
    public function getSeries()
    {
        $this->getPlugin()->includeClass("model/class.ilOpencastSeries.php");
        return new ilOpencastSeries($this->getSeriesId());
    }

    /**
     * Get the episode object from id
     *
     * @param string $episodeId
     * @return ilOpencastEpisode
     * @throws InvalidArgumentException
     */
    public function getEpisode($episodeId)
    {
        $this->getPlugin()->includeClass("model/class.ilOpencastEpisode.php");
        if (preg_match('/^[0-9a-f\-]+/', $episodeId)) {
            return new ilOpencastEpisode($this->getSeriesId(), $episodeId);
        }
        throw new InvalidArgumentException();
    }

    /**
     * Returns a list of the Episodes that have been made public available by the lecturer
     *
     * @return array containing the ids of the episodes that have been made public available.
     */
    public function getReleasedEpisodeIds()
    {
        global $DIC;
        // TODO move to OpencastSeries
        $set = $DIC->database()->query("SELECT episode_id FROM " . ilOpencastConfig::DATABASE_TABLE_RELEASED_EPISODES . " WHERE series_id = " . $DIC->database()
            ->quote($this->getSeriesId(), "text"));
        $released = array();
        while ($rec = $DIC->database()->fetchAssoc($set)) {
            array_push($released, ($rec["episode_id"]));
        }
        return $released;
    }
}
