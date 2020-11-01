<?php
namespace TIK_NFL\ilias_oc_plugin\opencast;

use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;
use DateTime;
use ilPlugin;
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast')->includeClass('class.ilOpencastConfig.php');
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast')->includeClass('opencast/class.ilOpencastRESTClient.php');
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast')->includeClass('opencast/class.ilOpencastUtil.php');

/**
 * All Communication with the Opencast server should be implemented in this class
 *
 * Require Opencast API version 1.1.0 or higher
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastAPI
{

    private static $instance = null;

    const API_PUBLICATION_CHANNEL = "api";

    const TRACK_TYPE_PRESENTER = "presenter";

    const TRACK_TYPE_PRESENTATION = "presentation";

    /**
     *
     * @var ilOpencastConfig
     */
    private $configObject;

    /**
     *
     * @var ilOpencastRESTClient
     */
    private $opencastRESTClient;

    /**
     * Singleton constructor
     */
    private function __construct()
    {
        $this->configObject = new ilOpencastConfig();
        $this->opencastRESTClient = new ilOpencastRESTClient($this->configObject->getOpencastServer(), $this->configObject->getOpencastAPIUser(), $this->configObject->getOpencastAPIPassword());
    }

    /**
     * Get singleton instance
     *
     * @return ilOpencastAPI
     */
    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private static function title(string $title, int $id, int $refId)
    {
        return "ILIAS-$id:$refId:$title";
    }

    public function checkOpencast()
    {
        return $this->opencastRESTClient->checkOpencast();
    }

    /**
     *
     * @param string $title
     * @param string $description
     * @param integer $obj_id
     * @param integer $refId
     * @return string the series id
     */
    public function createSeries(string $title, string $description, int $obj_id, int $refId)
    {
        $url = "/api/series";
        $fields = $this->createPostFields($title, $description, $obj_id, $refId);

        $series = json_decode($this->opencastRESTClient->post($url, $fields));
        # add a property containing the ilias object id
        $propurl = "/api/series/".$series->identifier."/properties";
        $propfields = array('properties' => json_encode(array(
            "ilobjid" => "$obj_id"
        )));
        $this->opencastRESTClient->put($propurl, $propfields);
        return $series->identifier;
    }

    /**
     *
     * @param string $series_id
     * @param string $title
     * @param string $description
     * @param int $obj_id
     * @param int $refId
     */
    public function updateSeries(string $series_id, string $title, string $description, int $obj_id, int $refId)
    {
        $this->setSeriesMetadata($series_id, array(
            "title" => self::title($title, $obj_id, $refId),
            "description" => $description
        ));
    }

    private function getAccessControl()
    {
        $userRole = "ROLE_USER_" . strtoupper($this->configObject->getOpencastAPIUser());

        return json_encode(array(
            array(
                "role" => $userRole,
                "action" => "read",
                "allow" => true
            ),
            array(
                "role" => $userRole,
                "action" => "write",
                "allow" => true
            )
        ));
    }

    /**
     *
     * @param string $series_id
     * @param string $title
     * @param string $description
     * @param integer $obj_id
     * @param integer $refId
     * @return string[]
     */
    private function createPostFields(string $title, string $description, int $obj_id, int $refId)
    {
        $metadata = array(
            "title" => self::title($title, $obj_id, $refId),
            "description" => $description
        );

        $publisher = $this->configObject->getPublisher();
        if ($publisher) {
            $metadata["publisher"] = array(
                $publisher
            );
        }

        return array(
            'metadata' => json_encode(array(
                array(
                    "flavor" => "dublincore/series",
                    "fields" => self::values($metadata)
                )
            )),
            'acl' => $this->getAccessControl()
        );
    }

    /**
     * Get the series with the default metadata catalog
     *
     * @param string $series_id
     * @return object the series object from opencast
     */
    public function getSeries(string $series_id)
    {
        $url = "/api/series/$series_id";
        return $this->opencastRESTClient->get($url);
    }

    /**
     *
     * @param string $title
     *            the title of the new created episode
     * @param string $creator
     *            the creator of the episode
     * @param DateTime $startDate
     *            the start date of the episode
     * @param string $series_id
     *            the series id the created episode should be part of
     * @param bool $flagForCutting
     *            if true the cutting flag is set
     * @param string $presentationFilePath
     *            the path to the presentation track file, which is uploaded
     * @param string $presenterFilePath
     *            the path to the presenter track file, which is uploaded
     *
     * @return string the event id
     */
    public function createEpisode(string $title, string $creator, DateTime $startDate, string $series_id, bool $flagForCutting, ?string $presentationFilePath, ?string $presenterFilePath)
    {
        $url = "/api/events";
        $startDate->setTimezone(new \DateTimeZone("UTC"));
        $metadata = array(
            "title" => $title,
            "creator" => array(
                $creator
            ),
            "startDate" => $startDate->format("Y-m-d"),
            "startTime" => $startDate->format("H:i:s"),
            "isPartOf" => $series_id
        );

        $post = array(
            'metadata' => json_encode(array(
                array(
                    "flavor" => "dublincore/episode",
                    "fields" => self::values($metadata)
                )
            )),
            'acl' => $this->getAccessControl(),
            'processing' => json_encode(array(
                "workflow" => $this->configObject->getUploadWorkflow(),
                "configuration" => array( // TODO
                    "flagForCutting" => $flagForCutting ? "true" : "false",
                    "straightToPublishing" => $flagForCutting ? "false" : "true",
                    "rewritefiles" => "true"
                )
            ))
        );
        if(null != $presentationFilePath) {
            $post['presentation'] = new \CurlFile($presentationFilePath);
        }
        if(null != $presenterFilePath) {
            $post['presenter'] = new \CurlFile($presenterFilePath);
        }
        $episode = json_decode($this->opencastRESTClient->postMultipart($url, $post));
        return $episode->identifier;
    }

    /**
     * Get the episode with the default metadata catalog
     *
     * @param string $episode_id
     * @return object the episode object from opencast
     */
    public function getEpisode(string $episode_id)
    {
        $url = "/api/events/$episode_id";
        return $this->opencastRESTClient->get($url);
    }

    /**
     * Get the episode publication for a channel
     *
     * @param string $episode_id
     * @return object the publication of the episode or null if there is no publication for the channel
     */
    public function getEpisodePublication(string $episode_id, string $channel = self::API_PUBLICATION_CHANNEL)
    {
        $url = "/api/events/$episode_id/publications";
        $params = array(
            "sign" => $this->configObject->getDeliveryMethod()=='api'?"true":"false"
        );

        $publications = $this->opencastRESTClient->get($url, $params);
        foreach ($publications as $publication) {
            if ($publication->channel == $channel) {
                return $publication;
            }
        }
        return null;
    }

    /**
     * The scheduled information for the series returned by opencast
     *
     * @param string $series_id
     *            series id
     * @return array the scheduled episodes for the series returned by opencast
     */
    public function getScheduledEpisodes(string $series_id)
    {
        $url = "/api/events";

        $params = array(
            'filter' => self::filter(array(
                "status" => "EVENTS.EVENTS.STATUS.SCHEDULED",
                "series" => $series_id
            )),
            'sort' => 'date:ASC'
        );

        return $this->opencastRESTClient->get($url, $params);
    }

    /**
     * Get the episodes which are on hold for given series
     *
     * @param string $series_id
     *            series id
     * @return array the episodes which are on hold for the series returned by opencast
     */
    public function getOnHoldEpisodes(string $series_id)
    {
        $url = "/api/events";

        $params = array(
            'filter' => self::filter(array(
                "status" => "EVENTS.EVENTS.STATUS.PROCESSED",
                "series" => $series_id
            )),
            'sort' => 'date:ASC',
            'withpublications' => "true",
            'sign' => "true"
        );

        $episodes = $this->opencastRESTClient->get($url, $params);
        return array_filter($episodes, array(
            $this,
            'isOnholdEpisode'
        ));
    }

    private function isOnholdEpisode($episode)
    {
        return ! $this->isReadyEpisode($episode);
    }

    /**
     * Get the episodes which have a publication on the api channel and non preview tracks for given series
     *
     * @param string $series_id
     *            series id
     * @return array the episodes which are published for the series returned by opencast
     */
    public function getReadyEpisodes(string $series_id)
    {
        $url = "/api/events";

        $params = array(
            'filter' => self::filter(array(
                "status" => "EVENTS.EVENTS.STATUS.PROCESSED",
                "series" => $series_id
            )),
            'sort' => 'date:ASC',
            'withpublications' => "true",
            'sign' => "true"
        );

        $episodes = $this->opencastRESTClient->get($url, $params);
        return array_filter($episodes, 'self::isReadyEpisode');
    }

    private static function isReadyEpisode($episode)
    {
        if (! in_array(self::API_PUBLICATION_CHANNEL, $episode->publication_status)) {
            return false;
        }

        $apiPublication = null;
        foreach ($episode->publications as $publication) {
            if ($publication->channel == self::API_PUBLICATION_CHANNEL) {
                $apiPublication = $publication;
            }
        }
        if ($apiPublication == null) {
            return false;
        }

        $nonPreviewTracks = array_filter($apiPublication->media, function ($track) {
            return ! in_array("preview", $track->tags);
        });

        return count($nonPreviewTracks) > 0;
    }

    /**
     * Get the workflows of the given series which are in processing
     *
     * @param string $series_id
     *            series id
     * @return object the workfolws which are in processing for the series returned by opencast
     */
    public function getActiveWorkflows(string $series_id)
    {
        $url = "/api/workflows";
        $params = array(
            "filter" => self::filter(array(
                "series_identifier" => $series_id,
                "state" => "running",
                "state_not" => "stopped",
                "current_operation_not" => "schedule",
                "current_operation_not" => "capture"
            )),
            "withoperations" => "true"
        );

        return $this->opencastRESTClient->get($url, $params);
    }

    /**
     * Get the workflow definitions with the given tag
     *
     * @param string $tag
     *            series id
     * @return array the workflow definition returned by opencast
     */
    public function getWorkflowDefinition(string $tag)
    {
        $url = "/api/workflow-definitions";
        $params = array(
            "filter" => self::filter(array(
                "tag" => $tag
            ))
        );

        return $this->opencastRESTClient->get($url, $params);
    }

    public function delete(string $episodeid)
    {
        $url = "/api/events/$episodeid";

        $this->opencastRESTClient->delete($url);
    }

    /**
     * Trims the tracks of a episode
     *
     * @param string $eventid
     *            the id of the episode
     * @param array $keeptrack
     *            the id of the tracks to be not removed
     * @param int $trimin
     *            the starttime of the new tracks in seconds
     * @param int $trimout
     *            the endtime of the new tracks in seconds
     */
    public function trim(string $eventid, array $keeptracks, int $trimin, int $trimout)
    {
        $url = "/api/workflows";

        $params = array(
            "event_identifier" => $eventid,
            "workflow_definition_identifier" => $this->configObject->getTrimWorkflow(),
            "configuration" => $this->generateTrimConfiguration($trimin, $trimout, $keeptracks)
        );

        $this->opencastRESTClient->post($url, $params);
    }

    /**
     *
     * @param int $trimin
     *            the starttime of the new tracks in seconds
     * @param int $trimout
     *            the endtime of the new tracks in seconds
     * @param array $keeptrack
     *            the id of the tracks to be not removed
     * @return string
     */
    private function generateTrimConfiguration(int $trimin, int $trimout, array $keeptracks)
    {
        $configuration = array(
            "start" => strval($trimin),
            "end" => strval($trimout)
        );

        if (! in_array(self::TRACK_TYPE_PRESENTER, $keeptracks)) {
            $configuration["hide_presenter_video"] = "true";
        }

        if (! in_array(self::TRACK_TYPE_PRESENTATION, $keeptracks)) {
            $configuration["hide_presentation_video"] = "true";
        }

        return json_encode($configuration);
    }

    /**
     *
     * @param string $seriesid
     * @param array $metadata
     * @param string $type
     */
    public function setSeriesMetadata(string $seriesid, array $metadata, string $type = "dublincore/series")
    {
        $url = "/api/series/$seriesid/metadata";
        $query = http_build_query(array(
            "type" => $type
        ));

        $post = array(
            "metadata" => json_encode(self::values($metadata))
        );
        $this->opencastRESTClient->put("$url?$query", $post);
    }

    /**
     *
     * @param string $episodeid
     * @param array $metadata
     * @param string $type
     */
    public function setEpisodeMetadata(string $episodeid, array $metadata, string $type = "dublincore/episode")
    {
        $url = "/api/events/$episodeid/metadata";
        $query = http_build_query(array(
            "type" => $type
        ));

        $post = array(
            "metadata" => json_encode(self::values($metadata))
        );
        $this->opencastRESTClient->put("$url?$query", $post);
    }

    /**
     * Convert a php array to the json structure of Opencast values
     *
     * @param array $metadata
     * @return array
     */
    private static function values(array $metadata)
    {
        $adapter = array();
        foreach ($metadata as $id => $value) {
            $adapter[] = array(
                "id" => $id,
                "value" => $value
            );
        }
        return $adapter;
    }

    private static function filter(array $filter)
    {
        $filters = array();
        foreach ($filter as $name => $value) {
            $filters[] = "$name:$value";
        }
        return implode(',', $filters);
    }
}