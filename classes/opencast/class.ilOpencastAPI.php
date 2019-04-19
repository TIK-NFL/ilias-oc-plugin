<?php
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn')->includeClass('class.ilMatterhornConfig.php');
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn')->includeClass('opencast/class.ilOpencastRESTClient.php');

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

    /**
     *
     * @var ilMatterhornConfig
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
        $this->configObject = new ilMatterhornConfig();
        $this->opencastRESTClient = new ilOpencastRESTClient($this->configObject->getMatterhornServer(), $this->configObject->getOpencastAPIUser(), $this->configObject->getOpencastAPIPassword());
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

    /**
     * Do a GET Request of the given url on the Opencast Server with digest authorization
     *
     * @param string $url
     * @throws Exception
     * @return mixed
     * @deprecated use the api
     */
    private function getDigest(string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->configObject->getMatterhornServer() . $url);
        $this->digestAuthentication($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $response = curl_exec($ch);

        if ($response === FALSE) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (! $httpCode) {
                throw new Exception("error GET request: $url", 503);
            }
            throw new Exception("error GET request: $url $httpCode", 500);
        }
        return $response;
    }

    /**
     *
     * @deprecated
     */
    private function digestAuthentication($ch)
    {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
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
        global $DIC;
        $ilUser = $DIC->user();

        $userid = $ilUser->getLogin();
        if (null != $ilUser->getExternalAccount) {
            $userid = $ilUser->getExternalAccount();
        }

        return json_encode(array(
            array(
                "role" => $userid,
                "action" => "read",
                "allow" => true
            ),
            array(
                "role" => $userid,
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
     * Get the series dublin core
     *
     * @param string $series_id
     * @return string the series dublin core XML document
     * @deprecated
     */
    public function getSeriesXML(string $series_id)
    {
        $url = "/series/$series_id.xml";
        return (string) $this->getDigest($url);
    }

    /**
     *
     * @param string $title
     *            the title of the new created episode
     * @param string $creator
     *            the creator of the episode
     * @param bool $flagForCutting
     *            if true the cutting flag is set
     * @param string $presentationfilePath
     *            the path to the presentation track file, which is uploaded
     * @return string the event id
     */
    public function createEpisode(string $title, string $creator, bool $flagForCutting, string $presentationfilePath)
    {
        $url = "/api/events";
        $metadata = array(
            "title" => $title,
            "creator" => array(
                $creator
            )
        );

        $post = array(
            'metadata' => json_encode(array(
                array(
                    "flavor" => "dublincore/episode",
                    "fields" => self::values($metadata)
                )
            )),
            'processing' => json_encode(array(
                "workflow" => $this->configObject->getUploadWorkflow(),
                "configuration" => array(
                    "flagForCutting" => $flagForCutting ? "true" : "false"
                )
            )),
            'presentation' => new CurlFile($presentationfilePath)
        );
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
     * The scheduled information for the series returned by opencast
     *
     * @param string $series_id
     *            series id
     * @return array the scheduled episodes for the series returned by opencast
     */
    public function getScheduledEpisodes(string $series_id)
    {
        $url = "/api/events/";

        $params = array(
            'filter' => self::filter(array(
                "status" => "EVENTS.EVENTS.STATUS.SCHEDULED",
                "series" => $series_id
            )),
            'sort' => 'date:ASC'
        );

        /* Update URL to container Query String of Paramaters */
        $url .= '?' . http_build_query($params);

        return $this->opencastRESTClient->get($url);
    }

    /**
     * Get the episodes which are on hold for given series
     *
     * @param string $series_id
     *            series id
     * @return array the episodes which are on hold for the series returned by matterhorn
     */
    public function getOnHoldEpisodes(string $series_id)
    {
        $url = "/api/events/";

        $params = array(
            'filter' => self::filter(array(
                "status" => "EVENTS.EVENTS.STATUS.PROCESSED",
                "series" => $series_id
            )),
            'sort' => 'date:ASC'
        );

        /* Update URL to container Query String of Paramaters */
        $url .= '?' . http_build_query($params);

        $episodes = $this->opencastRESTClient->get($url);
        return array_filter($episodes, array(
            $this,
            'isOnholdEpisode'
        ));
    }

    private function isOnholdEpisode($episode)
    {
        if (in_array("ilias", $episode->publication_status)) {
            return false;
        }
        return true;
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

        $url .= '?' . http_build_query($params);
        return $this->opencastRESTClient->get($url);
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

        $url .= '?' . http_build_query($params);

        return $this->opencastRESTClient->get($url);
    }

    public function delete(string $episodeid)
    {
        $url = "/api/events/$episodeid";

        $this->opencastRESTClient->delete($url);
    }

    /**
     * Get editor tool json from admin-ng
     *
     * @param string $episodeid
     *            the id of the episode
     * @throws Exception
     * @return mixed the decoded editor json from the admin ui
     * @deprecated
     */
    public function getEditor(string $episodeid)
    {
        $url = "/admin-ng/tools/$episodeid/editor.json";
        ilLoggerFactory::getLogger('xmh')->info("loading: " . $url);
        try {
            $curlret = $this->getDigest($url);
        } catch (Exception $e) {
            throw new Exception("error loading editor.json for episode " . $episodeid, 500, $e);
        }
        $editorjson = json_decode($curlret);
        if ($editorjson === false) {
            throw new Exception("error loading editor.json for episode " . $episodeid, 500);
        }
        return $editorjson;
    }

    /**
     * Get the media objects json from api
     *
     * @param string $episodeid
     *            the id of the episode
     * @throws Exception
     * @return array the decoded media json from the api publication channel
     */
    public function getMedia(string $episodeid)
    {
        $url = "/api/events/$episodeid";
        $params = array(
            "withpublications" => "true"
        );

        $url .= '?' . http_build_query($params);
        $episode = $this->opencastRESTClient->get($url);
        $publications = $episode->publications;

        $apiPublication = null;
        foreach ($publications as $publication) {
            if ($publication->channel == "api") {
                $apiPublication = $publication;
            }
        }
        if ($apiPublication == null) {
            throw new Exception("no publication for $episodeid on the 'api' channel", 404);
        }

        return $apiPublication->media;
    }

    /**
     * Trims the tracks of a episode
     *
     * @param string $eventid
     *            the id of the episode
     * @param array $keeptrack
     *            the id of the tracks to be not removed
     * @param float $trimin
     *            the starttime of the new tracks
     * @param float $trimout
     *            the endtime of of the new tracks
     */
    public function trim(string $eventid, array $keeptracks, float $trimin, float $trimout)
    {
        // TODO use api
        $url = "/admin-ng/tools/$eventid/editor.json";
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
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $json = array(
            "concat" => array(
                "segments" => array(
                    array(
                        "start" => (1000 * $trimin),
                        "end" => (1000 * $trimout),
                        "deleted" => false
                    )
                ),
                "tracks" => $keeptracks,
                "workflow" => "ilias-publish-after-cutting"
            )
        );
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        $mp = curl_exec($ch);
        if (! curl_errno($ch)) {
            $info = curl_getinfo($ch);
            ilLoggerFactory::getLogger('xmh')->debug('Successful request to ' . $info['url'] . ' in ' . $info['total_time']);
        }
        ilLoggerFactory::getLogger('xmh')->debug($mp);
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