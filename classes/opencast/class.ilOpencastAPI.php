<?php
ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn')->includeClass('class.ilMatterhornConfig.php');

/**
 * All Communication with the Opencast server should be implemented in this class
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
     * Do a GET Request of the given url on the Matterhorn Server with authorization
     *
     * @param string $url
     * @throws Exception
     * @return mixed
     */
    private function get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->configObject->getMatterhornServer() . $url);
        $this->setAuthorization($ch);
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
     * Do a POST Request of the given url on the Matterhorn Server with authorization
     *
     * @param string $url
     * @param array $post
     * @param boolean $returnHttpCode
     * @throws Exception
     * @return mixed
     */
    private function post($url, $post, $returnHttpCode = false)
    {
        $post_string = http_build_query($post);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->configObject->getMatterhornServer() . $url);
        curl_setopt($ch, CURLOPT_POST, count($post));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $this->setAuthorization($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

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

    private function setAuthorization($ch)
    {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
    }

    private static function title($title, $id, $refId)
    {
        return "ILIAS-$id:$refId:$title";
    }

    /**
     *
     * @param string $title
     * @param string $description
     * @param integer $obj_id
     * @param integer $refId
     * @return string the series id
     */
    public function createSeries($title, $description, $obj_id, $refId)
    {
        $url = "/series/";
        $series_id = 'ilias_xmh_' . $obj_id;
        $fields = $this->createPostFields($series_id, $title, $description, $obj_id, $refId);
        // TODO use api
        $this->post($url, $fields);
        return $series_id;
    }

    private static function setChildren($xml, $name, $value, $ns)
    {
        $cc = $xml->children($ns);
        if (isset($cc->$name)) {
            $cc->$name = $value;
        } else {
            $xml->addChild($name, $value, $ns);
        }
    }

    /**
     *
     * @param string $series_id
     * @param string $title
     * @param string $description
     * @param integer $obj_id
     * @param integer $refId
     * @return integer the httpCode
     */
    public function updateSeries($series_id, $title, $description, $obj_id, $refId)
    {
        $url = "/series/";

        $seriesxml = $this->getSeries($series_id);
        $xml = new SimpleXMLElement($seriesxml);
        $ns = "http://purl.org/dc/terms/";
        self::setChildren($xml, "title", self::title($title, $obj_id, $refId), $ns);
        self::setChildren($xml, "description", $description, $ns);
        self::setChildren($xml, "modified", date("c"), $ns);
        $seriesxml = $xml->asXML();
        $fields = array(
            'series' => $seriesxml,
            'acl' => $this->getAccessControl()
        );
        $httpCode = (integer) $this->post($url, $fields, true);
        return $httpCode;
    }

    private function getAccessControl()
    {
        global $DIC;
        $ilUser = $DIC->user();

        $userid = $ilUser->getLogin();
        if (null != $ilUser->getExternalAccount) {
            $userid = $ilUser->getExternalAccount();
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><acl xmlns="http://org.opencastproject.security">
								<ace><role>' . $userid . '</role><action>read</action><allow>true</allow></ace>
								<ace><role>' . $userid . '</role><action>write</action><allow>true</allow></ace>
						</acl>';
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
    private function createPostFields($series_id, $title, $description, $obj_id, $refId)
    {
        $fields = array(
            'series' => '<?xml version="1.0"?>
<dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.opencastproject.org http://www.opencastproject.org/schema.xsd" xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/" xmlns:oc="http://www.opencastproject.org/matterhorn/">
		
  <dcterms:title xml:lang="en">' . self::title($title, $obj_id, $refId) . '</dcterms:title>
  <dcterms:subject>
    </dcterms:subject>
  <dcterms:description xml:lang="en">' . $description . '</dcterms:description>
  <dcterms:publisher>
    University of Stuttgart, Germany
    </dcterms:publisher>
  <dcterms:identifier>
    ' . $series_id . '</dcterms:identifier>
  <dcterms:modified xsi:type="dcterms:W3CDTF">' . date("c") . '</dcterms:modified>
  <dcterms:format xsi:type="dcterms:IMT">
    video/mp4
    </dcterms:format>
  <oc:promoted>
   	false
  </oc:promoted>
</dublincore>',
            'acl' => $this->getAccessControl()
        );
        return $fields;
    }

    /**
     * Get the series dublin core
     *
     * @param string $series_id
     * @return string the series dublin core XML document
     */
    public function getSeries($series_id)
    {
        $url = "/series/" . $series_id . ".xml";
        $seriesxml = (string) $this->get($url);
        return $seriesxml;
    }

    /**
     * The scheduled information for the series returned by opencast
     *
     * @param string $series_id
     *            series id
     * @return array the scheduled episodes for the series returned by opencast
     */
    public function getScheduledEpisodes($series_id)
    {
        $url = "/admin-ng/event/events.json";
        /* $_GET Parameters to Send */
        $params = array(
            'filter' => 'status:EVENTS.EVENTS.STATUS.SCHEDULED,series:' . $series_id,
            'sort' => 'date:ASC'
        );

        /* Update URL to container Query String of Paramaters */
        $url .= '?' . http_build_query($params);

        $curlret = $this->get($url);
        $searchResult = json_decode($curlret, true);

        return $searchResult;
    }

    public function deleteschedule($workflowid)
    {
        $url = $this->configObject->getMatterhornServer() . '/admin-ng/event/' . $workflowid;

        // open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $this->setAuthorization($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $curlret = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        ilLoggerFactory::getLogger('xmh')->debug("delete code: " . $httpCode);
        return $httpCode;
    }

    /**
     * Get the episodes which are on hold for given series
     *
     * @param string $series_id
     *            series id
     * @return array the episodes which are on hold for the series returned by matterhorn
     */
    public function getOnHoldEpisodes($series_id)
    {
        $url = "/admin-ng/event/events.json";
        /* $_GET Parameters to Send */
        $params = array(
            'filter' => 'status:EVENTS.EVENTS.STATUS.PROCESSED,comments:OPEN,series:' . $series_id,
            'sort' => 'date:ASC'
        );

        /* Update URL to container Query String of Paramaters */
        $url .= '?' . preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', http_build_query($params, null, '&'));

        $curlret = $this->get($url);
        $searchResult = json_decode($curlret, true);

        if (is_array($searchResult)) {
            return $searchResult['results'];
        } else {
            return [];
        }
    }

    /**
     * Get the episodes which are on hold for the given series
     *
     * @param string $series_id
     *            series id
     * @return array the episodes which are on hold for the series returned by matterhorn
     */
    public function getProcessingEpisodes($series_id)
    {
        $url = "/workflow/instances.json";
        $params = array(
            'seriesId' => $series_id,
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

        $curlret = $this->get($url);
        $searchResult = json_decode($curlret, true);

        return $searchResult;
    }

    /**
     * Get editor tool json from admin-ng
     *
     * @param string $episodeid
     *            the id of the episode
     * @throws Exception
     * @return mixed the decoded editor json from the admin ui
     */
    public function getEditor($episodeid)
    {
        $url = "/admin-ng/tools/$episodeid/editor.json";
        ilLoggerFactory::getLogger('xmh')->info("loading: " . $url);
        try {
            $curlret = $this->get($url);
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
     * @param
     *            string the id of the episode
     * @throws Exception
     * @return mixed the decoded media json from the admin ui
     */
    public function getMedia($episodeid)
    {
        $url = "/admin-ng/event/$episodeid/asset/media/media.json";
        ilLoggerFactory::getLogger('xmh')->info("loading: " . $url);

        $curlret = $this->get($url);
        $mediajson = json_decode($curlret);
        if ($mediajson === false) {
            throw new Exception("error loading media for episode " . $episodeid, 500);
        }
        return $mediajson;
    }
}
