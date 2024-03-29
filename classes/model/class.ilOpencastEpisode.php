<?php
namespace TIK_NFL\ilias_oc_plugin\model;

use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;
use TIK_NFL\ilias_oc_plugin\opencast\ilOpencastAPI;
use Exception;

/**
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastEpisode
{
    use \TIK_NFL\ilias_oc_plugin\opencast\ilDeliveryUrlTrait;

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
     * @var ilOpencastConfig
     */
    private $configObject;

    /**
     *
     * @param string $series_id
     * @param string $episode_id
     */
    public function __construct($series_id, $episode_id)
    {
        $this->series_id = $series_id;
        $this->episode_id = $episode_id;
        $this->configObject = new ilOpencastConfig();
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

    /**
     * Check if the episode exists in Opencast and is part of the correct series
     *
     * @return boolean
     */
    public function exists()
    {
        try {
            $episode = ilOpencastAPI::getInstance()->getEpisode($this->episode_id);
            return $episode->is_part_of == $this->series_id;
        } catch (Exception $e) {
            return false;
        }
    }

    public function setTitle($title)
    {
        if (strcmp($this->getEpisode()->title, $title) !== 0) {
            ilOpencastAPI::getInstance()->setEpisodeMetadata($this->getEpisodeId(), array(
                "title" => $title
            ));
        }
    }

    public function setPresenter($presenter)
    {
        ilOpencastAPI::getInstance()->setEpisodeMetadata($this->getEpisodeId(), array(
            "presenter" => [
                $presenter
            ]
        ));
    }

    public function setStartdate(\DateTime $startdate)
    {
        if (new \DateTime($this->getEpisode()->start) != $startdate) {
            ilOpencastAPI::getInstance()->setEpisodeMetadata($this->getEpisodeId(), array(
                "startDate" => $startdate
            ));
        }
    }

    public function setMetadata($entries)
    {
        $oc_entries = array();
        if (array_key_exists("title", $entries) && strcmp($this->getEpisode()->title, $entries["title"]) !== 0) {
            $oc_entries["title"] = $entries["title"];
        }
        if (array_key_exists("startDate", $entries) && new \DateTime($this->getEpisode()->start) != $entries["startDate"]) {
            $oc_entries["startDate"] = $entries["startDate"]->format("Y-m-d");
            $oc_entries["startTime"] = $entries["startDate"]->format("H:i:s");
        }
        if (count($oc_entries) > 0) {
            ilOpencastAPI::getInstance()->setEpisodeMetadata($this->getEpisodeId(), $oc_entries);
        }
    }

    /**
     * publish this episode
     */
    public function publish()
    {
        global $DIC;
        $affected_rows = $DIC->database()->manipulate("INSERT INTO " . ilOpencastConfig::DATABASE_TABLE_RELEASED_EPISODES . " (episode_id, series_id) VALUES (" . $this->getQuoteEpisodeId() . "," . $this->getQuoteSeriesId() . ") ON DUPLICATE KEY UPDATE episode_id = episode_id");
        if ($affected_rows != 1) {
            throw new Exception("Episode " . $this->episode_id . " already published!");
        }
        //$this->addTextToDB();
    }

    private function addTextToDB()
    {
        global $ilDB;
        $publication = $this->getPublication();
        $textCatalogUrl = null;
        foreach ($publication->metadata as $catalog) {
            if (0 == strcmp($catalog->flavor, 'mpeg-7/text')) {
                $textCatalogUrl = $this->getDeliveryUrl($catalog->url);
            }
        }
        if ($textCatalogUrl) {
            $segmentsxml = new \SimpleXMLElement($textCatalogUrl, null, true);
            $currentidx = 0;
            $currenttime = 0;
            foreach ($segmentsxml->Description->MultimediaContent->Video->TemporalDecomposition->VideoSegment as $segmentxml) {
                $regmatches = array();
                preg_match("/PT(\d+M)?(\d+S)?(\d+)?N1000F/", (string) $segmentxml->MediaTime->MediaDuration, $regmatches);
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
                        $ilDB->manipulate("INSERT INTO " . ilOpencastConfig::DATABASE_TABLE_SLIDETEXT . " (episode_id, slidetext, slidetime) VALUES (" . $this->getQuoteEpisodeId() . "," . $ilDB->quote($text, "text") . "," . $ilDB->quote($currenttime, "integer") . ")");
                    }
                }
                $currentidx ++;
                $currenttime += $duration;
            }
        }
    }

    /**
     * retract this episode
     */
    public function retract()
    {
        global $ilDB;
        $ilDB->manipulate("DELETE FROM " . ilOpencastConfig::DATABASE_TABLE_RELEASED_EPISODES . " WHERE episode_id=" . $this->getQuoteEpisodeId() . " AND series_id=" . $this->getQuoteSeriesId());
        $this->removeTextFromDB();
    }

    private function removeTextFromDB()
    {
        global $ilDB;
        $ilDB->manipulate("DELETE FROM " . ilOpencastConfig::DATABASE_TABLE_SLIDETEXT . " WHERE episode_id = " . $this->getQuoteEpisodeId());
    }

    public function delete()
    {
        ilOpencastAPI::getInstance()->delete($this->getEpisodeId());
    }

    /**
     * Get Episode information from the Opencast API
     *
     * @return object the Opencast object from the api
     */
    public function getEpisode()
    {
        return ilOpencastAPI::getInstance()->getEpisode($this->getEpisodeId());
    }

    /**
     * Get Episode publication from the Opencast API
     *
     * @return object the Opencast api channel publication from the api or null
     */
    public function getPublication()
    {
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
            throw new Exception("no publication for " . $this->episode_id . " on the 'api' channel", 404);
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
        ilOpencastAPI::getInstance()->trim($this->getEpisodeId(), $keeptracks, $trimin, $trimout);
    }
}