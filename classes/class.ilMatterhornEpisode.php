<?php

class ilMatterhornEpisode
{

    /**
     *
     * @var int
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
     * @param int $series_id
     * @param string $episode_id
     */
    public function __construct($series_id, $episode_id)
    {
        $this->series_id = $series_id;
        $this->episode_id = $episode_id;
    }

    /**
     *
     * @return int
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
        return $ilDB->quote($this->getSeriesId(), "integer");
    }

    /**
     * Adds Prefix to the SeriesId to match the Opencast Series Identifier for that Series.
     *
     * @return string
     */
    public function getOpencastSeriesId()
    {
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn');
        $plugin->includeClass("class.ilMatterhornConfig.php");
        $configObject = new ilMatterhornConfig();
        return $configObject->getSeriesPrefix() . $this->getSeriesId();
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
     *
     * @return SimpleXMLElement
     */
    public function getManifest()
    {
        if (! $this->manifest) {
            $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn');
            $plugin->includeClass("class.ilMatterhornConfig.php");
            $configObject = new ilMatterhornConfig();
            $this->manifest = new SimpleXMLElement($configObject->getUploadDirectory() . $this->getOpencastSeriesId() . '/' . $this->getEpisodeId() . '/manifest.xml', null, true);
        }
        return $this->manifest;
    }

    /**
     * Get the Duration of the episode in milliseconds as String
     *
     * @return string duration in milliseconds
     */
    public function getDuration()
    {
        $manifest = $this->getManifest();
        $duration = (string) $manifest['duration'];
        return $duration;
    }

    /**
     * Get the title of the episode
     *
     * @return string
     */
    public function getTitle()
    {
        $manifest = $this->getManifest();
        $title = (string) $manifest->title;
        return $title;
    }

    /**
     * publish this episode
     */
    public function publish()
    {
        global $DIC;
        $affected_rows = $DIC->database()->manipulate("INSERT INTO rep_robj_xmh_rel_ep (episode_id, series_id) VALUES (" . $this->getQuoteEpisodeId() . "," . $this->getQuoteSeriesId() . ") ON DUPLICATE KEY UPDATE episode_id = episode_id");
        if ($affected_rows != 1) {
            throw new Exception("Episode " . $this->episode_id . " already published!");
        }
        $this->addTextToDB();
    }

    private function addTextToDB()
    {
        global $ilDB;
        $manifest = $this->getManifest();
        $textcatalog = null;
        foreach ($manifest->metadata->catalog as $catalog) {
            $cat = array();
            if (isset($catalog['id'])) {
                $cat['id'] = (string) $catalog['id'];
            }
            if (isset($catalog['type'])) {
                $cat['type'] = (string) $catalog['type'];
            }
            if (isset($catalog['ref'])) {
                $cat['ref'] = (string) $catalog['ref'];
            }
            if (isset($catalog->mimetype)) {
                $cat['mimetype'] = (string) $catalog->mimetype;
            }
            if (isset($catalog->url)) {
                $cat['url'] = (string) $catalog->url;
            }
            if (isset($catalog->tags)) {
                $cat['tags'] = array(
                    'tag' => array()
                );
                foreach ($catalog->tags->tag as $tag) {
                    array_push($cat['tags']['tag'], (string) $tag);
                }
            }
            if (isset($catalog['type']) && 0 == strcmp((string) $catalog['type'], 'mpeg-7/text')) {
                $textcatalog = $cat;
            }
        }
        if ($textcatalog) {
            $segments = array_slice(explode("/", $textcatalog["url"]), - 2);
            $configObject = new ilMatterhornConfig();
            $segmentsxml = new SimpleXMLElement($configObject->getUploadDirectory() . $this->getOpencastSeriesId() . '/' . $this->getEpisodeId() . '/' . $segments[0] . '/' . $segments[1], null, true);
            $segments = array(
                "segment" => array()
            );
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
                        $ilDB->manipulate("INSERT INTO rep_robj_xmh_slidetext (episode_id, series_id, slidetext, slidetime) VALUES (" . $this->getQuoteEpisodeId() . "," . $this->getQuoteSeriesId() . "," . $ilDB->quote($text, "text") . "," . $ilDB->quote($currenttime, "text") . ")");
                    }
                }
                $currentidx ++;
                $currenttime = $currenttime + $duration;
            }
        }
        return $segments;
    }

    /**
     * retract this episode
     */
    public function retract()
    {
        global $ilDB;
        $ilDB->manipulate("DELETE FROM rep_robj_xmh_rel_ep WHERE episode_id=" . $this->getQuoteEpisodeId() . " AND series_id=" . $this->getQuoteSeriesId());
        $this->removeTextFromDB();
    }

    private function removeTextFromDB()
    {
        global $ilDB;
        
        $ilDB->manipulate("DELETE FROM rep_robj_xmh_slidetext WHERE episode_id = " . $this->getQuoteEpisodeId() . " AND series_id  = " . $this->getQuoteSeriesId());
    }
}