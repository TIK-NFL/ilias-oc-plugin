<?php

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

    private function setAuthorization($ch)
    {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
    }

    /**
     *
     * @param string $title
     * @param string $description
     * @param integer $id
     * @param integer $refId
     * @param string $lectureId
     * @return mixed[]
     */
    public function createSeries($title, $description, $id, $refId, $lectureId)
    {
        $url = $this->configObject->getMatterhornServer() . "/series/";
        $fields = $this->createPostFields($title, $description, $id, $refId, $lectureId);
        
        $fields_string = http_build_query($fields);
        
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $this->setAuthorization($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $identifier = (string) curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array(
            "identifier" => $identifier,
            "httpCode" => $httpCode
        );
    }

    /**
     *
     * @param string $title
     * @param string $description
     * @param integer $id
     * @param integer $refId
     * @param string $lectureId
     * @return mixed[]
     */
    public function updateSeries($title, $description, $id, $refId, $lectureId)
    {
        $url = $this->configObject->getMatterhornServer() . "/series/";
        $fields = $this->createPostFields($title, $description, $id, $refId, $lectureId);
        
        $fields_string = http_build_query($fields);
        
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $this->setAuthorization($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $identifier = (string) curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array(
            "identifier" => $identifier,
            "httpCode" => $httpCode
        );
    }

    /**
     *
     * @param string $title
     * @param string $description
     * @param integer $id
     * @param integer $refId
     * @param string $lectureId
     * @return string[]
     */
    private function createPostFields($title, $description, $id, $refId, $lectureId)
    {
        global $DIC;
        $ilUser = $DIC->user();
        
        $userid = $ilUser->getLogin();
        if (null != $ilUser->getExternalAccount) {
            $userid = $ilUser->getExternalAccount();
        }
        $acl = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><acl xmlns="http://org.opencastproject.security">
								<ace><role>' . $userid . '</role><action>read</action><allow>true</allow></ace>
								<ace><role>' . $userid . '</role><action>write</action><allow>true</allow></ace>
						</acl>';
        $fields = array(
            'series' => '<?xml version="1.0"?>
<dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.opencastproject.org http://www.opencastproject.org/schema.xsd" xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/" xmlns:oc="http://www.opencastproject.org/matterhorn/">
		
  <dcterms:title xml:lang="en">ILIAS-' . $id . ':' . $refId . ':' . $title . '</dcterms:title>
  <dcterms:subject>
    </dcterms:subject>
  <dcterms:description xml:lang="en">' . $description . '</dcterms:description>
  <dcterms:publisher>
    University of Stuttgart, Germany
    </dcterms:publisher>
  <dcterms:identifier>
    ' . $this->configObject->getSeriesPrefix() . $id . '</dcterms:identifier>
  <dcterms:references>' . $lectureId . '</dcterms:references>
  <dcterms:modified xsi:type="dcterms:W3CDTF">' . date("Y-m-d") . '</dcterms:modified>
  <dcterms:format xsi:type="dcterms:IMT">
    video/mp4
    </dcterms:format>
  <oc:promoted>
   	false
  </oc:promoted>
</dublincore>',
            'acl' => $acl
        );
        return $fields;
    }

    /**
     *
     * @param integer $id
     * @return string[]
     */
    public function getSeries($id)
    {
        $url = $this->configObject->getMatterhornServer() . "/series/" . $this->configObject->getSeriesPrefix() . $id . ".xml";
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        $this->setAuthorization($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $series = (string) curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array(
            "series" => $series,
            "httpCode" => $httpCode
        );
    }

    /**
     * The scheduled information for the series returned by matterhorn
     *
     * @param integer $id
     *            series id
     * @return array the scheduled episodes for the series returned by matterhorn
     */
    public function getScheduledEpisodes($id)
    {
        $url = $this->configObject->getMatterhornServer() . "/admin-ng/event/events.json";
        /* $_GET Parameters to Send */
        $params = array(
            'filter' => 'status:EVENTS.EVENTS.STATUS.SCHEDULED,series:' . $this->configObject->getSeriesPrefix() . $id,
            'sort' => 'date:ASC'
        );
        
        /* Update URL to container Query String of Paramaters */
        $url .= '?' . http_build_query($params);
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        $this->setAuthorization($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
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
        $curlret = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        ilLoggerFactory::getLogger('xmh')->debug("delete code: " . $httpCode);
        return $httpCode;
    }

    /**
     * Get the episodes which are on hold for given series
     *
     * @param integer $id
     *            series id
     * @return array the episodes which are on hold for the series returned by matterhorn
     */
    public function getOnHoldEpisodes($id)
    {
        $url = $this->configObject->getMatterhornServer() . "/admin-ng/event/events.json";
        /* $_GET Parameters to Send */
        $params = array(
            'filter' => 'status:EVENTS.EVENTS.STATUS.PROCESSED,comments:OPEN,series:' . $this->configObject->getSeriesPrefix() . $id,
            'sort' => 'date:ASC'
        );
        
        /* Update URL to container Query String of Paramaters */
        $url .= '?' . preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', http_build_query($params, null, '&'));
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        $this->setAuthorization($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
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
     * @param integer $id
     *            series id
     * @return array the episodes which are on hold for the series returned by matterhorn
     */
    public function getProcessingEpisodes($id)
    {
        $url = $this->configObject->getMatterhornServer() . "/workflow/instances.json";
        $params = array(
            'seriesId' => $this->configObject->getSeriesPrefix() . $id,
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
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        $this->setAuthorization($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlret = curl_exec($ch);
        $searchResult = json_decode($curlret, true);
        
        return $searchResult;
    }
}