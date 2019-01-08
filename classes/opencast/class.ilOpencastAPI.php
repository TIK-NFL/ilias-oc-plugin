<?php
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn')->includeClass('class.ilMatterhornConfig.php');

/**
 * All Communication with the Opencast server should be implemented in this class
 *
 * Require Opencast API version 1.1.0 or higher
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastAPI
{

    const API_VERSION = "v1.1.0";

    private static $instance = null;

    /**
     *
     * @var ilMatterhornConfig
     */
    private $configObject;

    /**
     * Singleton constructor
     */
    private function __construct()
    {
        $this->configObject = new ilMatterhornConfig();
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
     * Do a GET Request of the given url on the Opencast Server with basic authorization
     *
     * @param string $url
     * @throws Exception
     * @return mixed
     */
    private function get(string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->configObject->getMatterhornServer() . $url);
        $this->basicAuthentication($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/" . self::API_VERSION . "+json"
        ));

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
     * Do a POST Request of the given url on the Opencast Server with basic authorization
     *
     * @param string $url
     * @param array $post
     * @param boolean $returnHttpCode
     * @throws Exception
     * @return mixed
     */
    private function post(string $url, array $post, bool $returnHttpCode = false)
    {
        $post_string = http_build_query($post);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->configObject->getMatterhornServer() . $url);
        curl_setopt($ch, CURLOPT_POST, count($post));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $this->basicAuthentication($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/" . self::API_VERSION . "+json"
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === FALSE) {
            throw new Exception("error POST request: $url $post_string $httpCode", 500);
        }

        if ($returnHttpCode) {
            return $httpCode;
        }
        return $response;
    }

    /**
     * Do a PUT Request of the given url on the Opencast Server with Basic Authentication
     *
     * @param string $url
     * @param array $post
     * @param boolean $returnHttpCode
     * @throws Exception
     * @return mixed
     */
    private function put(string $url, array $post, bool $returnHttpCode = false)
    {
        $post_string = http_build_query($post);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->configObject->getMatterhornServer() . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POST, count($post));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $this->basicAuthentication($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/" . self::API_VERSION . "+json"
        ));

        $response = curl_exec($ch);
        if ($response === FALSE) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            throw new Exception("error PUT request: $url $post_string $httpCode", 500);
        }

        if ($returnHttpCode) {
            return curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        return $response;
    }

    private function digestAuthentication($ch)
    {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
    }

    private function basicAuthentication($ch)
    {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getOpencastAPIUser() . ':' . $this->configObject->getOpencastAPIPassword());
    }

    private static function title(string $title, int $id, int $refId)
    {
        return "ILIAS-$id:$refId:$title";
    }

    public function checkOpencast()
    {
        $url = "/api/version";
        try {
            $versionInfo = json_decode($this->get($url), true);
            return in_array(self::API_VERSION, $versionInfo["versions"]);
        } catch (Exception $e) {
            return false;
        }
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

        $series = json_decode($this->post($url, $fields));
        return $series["identifier"];
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
            "description" => $description,
            "publishers" => array(
                "University of Stuttgart, Germany"
            )
        );
        $fields = array();
        foreach ($metadata as $id => $value) {
            $fields[] = array(
                "id" => $id,
                "value" => $value
            );
        }

        return array(
            'metadata' => json_encode(array(
                array(
                    "flavor" => "dublincore/series",
                    "fields" => $fields
                )
            )),
            'acl' => $this->getAccessControl()
        );
    }

    /**
     * Get the series with the default metadata catalog
     *
     * @param string $series_id
     * @return array the series object from opencast
     */
    public function getSeries(string $series_id)
    {
        $url = "/api/series/$series_id";
        return json_decode($this->get($url));
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
     * Get the episode with the default metadata catalog
     *
     * @param string $episode_id
     * @return array the episode object from opencast
     */
    public function getEpisode(string $episode_id)
    {
        $url = "/api/events/$episode_id";
        return json_decode($this->get($url));
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

        $curlret = $this->get($url);

        return json_decode($curlret, true);
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
                "comments" => "OPEN",
                "series" => $series_id
            )),
            'sort' => 'date:ASC'
        );

        /* Update URL to container Query String of Paramaters */
        $url .= '?' . http_build_query($params);

        $curlret = $this->get($url);
        return json_decode($curlret, true);
    }

    /**
     * Get the workflows of the given series which are in processing
     *
     * @param string $series_id
     *            series id
     * @return array the workfolws which are in processing for the series returned by opencast
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
            "withoperations" => true
        );

        $url .= '?' . http_build_query($params);

        $curlret = $this->get($url);
        return json_decode($curlret, true);
    }

    public function delete(string $episodeid)
    {
        $url = $this->configObject->getMatterhornServer() . "/api/events/$episodeid";

        // open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $this->basicAuthentication($ch);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/" . self::API_VERSION . "+json"
        ));

        if (! curl_exec($ch)) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            ilLoggerFactory::getLogger('xmh')->debug("Failded to delete episode $episodeid  httpcode: $httpCode");
            return false;
        }
        return true;
    }

    /**
     * Get editor tool json from admin-ng
     *
     * @param string $episodeid
     *            the id of the episode
     * @throws Exception
     * @return mixed the decoded editor json from the admin ui
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
     * Get the media objects json from admin-ng
     *
     * @param string $episodeid
     *            the id of the episode
     * @throws Exception
     * @return mixed the decoded media json from the admin ui
     * @deprecated removed without replacement
     */
    public function getMedia(string $episodeid)
    {
        $url = "/admin-ng/event/$episodeid/asset/media/media.json";
        ilLoggerFactory::getLogger('xmh')->info("loading: " . $url);

        $curlret = $this->getDigest($url);
        $mediajson = json_decode($curlret);
        if ($mediajson === false) {
            throw new Exception("error loading media for episode " . $episodeid, 500);
        }
        return $mediajson;
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
        $adapter = array();
        foreach ($metadata as $id => $value) {
            $adapter[] = array(
                "id" => $id,
                "value" => $value
            );
        }

        $post = array(
            "metadata" => json_encode($adapter)
        );
        $this->put("$url?$query", $post);
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
        $adapter = array();
        foreach ($metadata as $id => $value) {
            $adapter[] = array(
                "id" => $id,
                "value" => $value
            );
        }

        $post = array(
            "metadata" => json_encode($adapter)
        );
        $this->put("$url?$query", $post);
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