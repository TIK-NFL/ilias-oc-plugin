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
        // url-ify the data for the POST
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
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
        
        // url-ify the data for the POST
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        
        // open connection
        $ch = curl_init();
        
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
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
            'series' => urlencode('<?xml version="1.0"?>
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
</dublincore>'),
            'acl' => urlencode($acl)
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
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Requested-Auth: Digest",
            "X-Opencast-Matterhorn-Authorization: true"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $series = (string) curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array(
            "series" => $series,
            "httpCode" => $httpCode
        );
    }
}