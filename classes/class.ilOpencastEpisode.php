<?php

/**
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastEpisode
{

    /**
     * The Opencast series id, not the ilias object id
     *
     * @var string
     */
    private $series_id;

    /**
     *
     * @var string
     */
    private $episode_id;

    /**
     *
     * @var SimpleXMLElement
     */
    private $manifest;

    /**
     *
     * @param string $series_id
     * @param string $episode_id
     */
    public function __construct($series_id, $episode_id)
    {
        $this->series_id = $series_id;
        $this->episode_id = $episode_id;
    }

    /**
     * The Opencast series id, not the ilias object id
     *
     * @return string
     */
    public function getSeriesId()
    {
        return $this->series_id;
    }

    /**
     *
     * @return string
     */
    public function getQuoteSeriesId()
    {
        global $ilDB;
        return $ilDB->quote($this->getSeriesId(), "text");
    }

    /**
     *
     * @return string
     */
    public function getEpisodeId()
    {
        return $this->episode_id;
    }

    /**
     *
     * @return string
     */
    public function getQuoteEpisodeId()
    {
        global $ilDB;
        return $ilDB->quote($this->getEpisodeId(), "text");
    }

    public function setTitle($title)
    {
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast');
        $plugin->includeClass("opencast/class.ilOpencastAPI.php");
        ilOpencastAPI::getInstance()->setEpisodeMetadata($this->getEpisodeId(), array(
            "title" => $title
        ));
    }

    /**
     * publish this episode
     */
    public function publish()
    {
        global $DIC;
        $affected_rows = $DIC->database()->manipulate("INSERT INTO rep_robj_xoc_rel_ep (episode_id, series_id) VALUES (" . $this->getQuoteEpisodeId() . "," . $this->getQuoteSeriesId() . ") ON DUPLICATE KEY UPDATE episode_id = episode_id");
        if ($affected_rows != 1) {
            throw new Exception("Episode " . $this->episode_id . " already published!");
        }
        $this->addTextToDB();
    }

    private function addTextToDB()
    {
        global $ilDB;
        $publication = $this->getPublication();
        $textCatalogUrl = null;
        foreach ($publication->metadata as $catalog) {
            if (0 == strcmp($catalog->flavor, 'mpeg-7/text')) {
                $textCatalogUrl = $catalog->url;
            }
        }
        if ($textCatalogUrl) {
            $segmentsxml = new SimpleXMLElement($textCatalogUrl, null, true);
            $currentidx = 0;
            $currenttime = 0;
            foreach ($segmentsxml->Description->MultimediaContent->Video->TemporalDecomposition->VideoSegment as $segmentxml) {
                $regmatches = array();
                // preg_match("/PT(\d+M)?(\d+S)?N1000F/", (string) $segmentxml->MediaTime->MediaDuration, $regmatches);
                preg_match("/PT(\d+M)?(\d+S)?(\d+)?(0)?N1000F/", (string) $segmentxml->MediaTime->MediaDuration, $regmatches);
                $ms = $regmatches[3];
                $sec = 0;
                if (0 != strcmp('', $regmatches[2])) {
                    $sec = substr($regmatches[2], 0, - 1);
                }

                $min = 0;
                if (0 != strcmp('', $regmatches[1])) {
                    $min = substr($regmatches[1], 0, - 1);
                }
                $duration = ($min * 60 + $sec) * 1000 + $ms;
                if ($segmentxml->SpatioTemporalDecomposition) {
                    $text = "";
                    foreach ($segmentxml->SpatioTemporalDecomposition->VideoText as $textxml) {
                        $text = $text . " " . (string) $textxml->Text;
                    }
                    if ($text != "") {
                        $ilDB->manipulate("INSERT INTO rep_robj_xoc_slidetext (episode_id, slidetext, slidetime) VALUES (" . $this->getQuoteEpisodeId() . "," . $ilDB->quote($text, "text") . "," . $ilDB->quote($currenttime, "integer") . ")");
                    }
                }
                $currentidx ++;
                $currenttime = $currenttime + $duration;
            }
        }
    }

    /**
     * retract this episode
     */
    public function retract()
    {
        global $ilDB;
        $ilDB->manipulate("DELETE FROM rep_robj_xoc_rel_ep WHERE episode_id=" . $this->getQuoteEpisodeId() . " AND series_id=" . $this->getQuoteSeriesId());
        $this->removeTextFromDB();
    }

    private function removeTextFromDB()
    {
        global $ilDB;
        $ilDB->manipulate("DELETE FROM rep_robj_xoc_slidetext WHERE episode_id = " . $this->getQuoteEpisodeId());
    }

    public function delete()
    {
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast');
        $plugin->includeClass("opencast/class.ilOpencastAPI.php");
        ilOpencastAPI::getInstance()->delete($this->getEpisodeId());
    }

    /**
     * Get Episode information from the Opencast API
     *
     * @return object the Opencast object from the api
     */
    public function getEpisode()
    {
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast');
        $plugin->includeClass("opencast/class.ilOpencastAPI.php");
        return ilOpencastAPI::getInstance()->getEpisode($this->getEpisodeId());
    }

    /**
     * Get Episode publication from the Opencast API
     *
     * @return object the Opencast api channel publication from the api or null
     */
    public function getPublication()
    {
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast');
        $plugin->includeClass("opencast/class.ilOpencastAPI.php");
        return ilOpencastAPI::getInstance()->getEpisodePublication($this->getEpisodeId());
    }

    /**
     * Get the media objects json from opencast for this episode
     *
     * @return array the media json from the Opencast api
     */
    public function getMedia()
    {
        $publication = $this->getPublication();
        if ($publication == null) {
            throw new Exception("no publication for $episodeid on the 'api' channel", 404);
        }
        return $publication->media;
    }

    /**
     * Trims the tracks of this episode
     *
     * @param array $keeptrack
     *            the id of the tracks to be not removed
     * @param float $trimin
     *            the starttime of the new tracks
     * @param float $trimout
     *            the endtime of of the new tracks
     */
    public function trim(array $keeptracks, float $trimin, float $trimout)
    {
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast');
        $plugin->includeClass("opencast/class.ilOpencastAPI.php");
        ilOpencastAPI::getInstance()->trim($this->getEpisodeId(), $keeptracks, $trimin, $trimout);
    }
}