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
    public function getEpisodeId()
    {
        return $this->episode_id;
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
            $this->manifest = new SimpleXMLElement($configObject->getXSendfileBasedir() . 'ilias_xmh_' . $this->getSeriesId() . '/' . $this->getEpisodeId() . '/manifest.xml', null, true);
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
}