<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilMatterhornUploadFile.
 *
 * A class that contains the logic to send the individual chunks received onto Opencast
 *
 * @auther Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
 */
class ilMatterhornUploadFile
{

    /**
     *
     * @var ilMatterhornPlugin
     */
    private $plugin;

    /**
     * the id of the matterhorn object
     *
     * @var int
     */
    private $obj_id;

    /**
     * the configuration for the matterhorn object
     *
     * @var ilMatterhornConfig
     */
    private $configObject;

    /**
     * Constructor
     *
     * @param mixed $uri
     *            the parsed REQUEST_URI
     * @param string $method
     *            the REQUEST_METHOD
     */
    public function __construct($uri, $method)
    {
        $this->params = array();
        $this->requestType = 'none';
        
        $this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn');
        $this->plugin->includeClass("class.ilMatterhornConfig.php");
        $this->configObject = new ilMatterhornConfig();
        
        if ($method == 'GET') {
            parse_str($uri["query"], $this->params);
        } else if ($method == 'POST') {
            $this->params = $_POST;
        }
        
        // debugging
        /*
         * echo "<pre>";
         * var_dump($uri);
         * echo "REQUEST_URI: ". $_SERVER["REQUEST_URI"]. "\n";
         * echo "Parsed URI: ". $uri["path"]. "\n";
         * echo "DOCUMENT_ROOT: ". $_SERVER["DOCUMENT_ROOT"]. "\n";
         * echo "PHP_SELF: ". $_SERVER["PHP_SELF"]. "\n";
         * echo "SCRIPT_NAME: ". $_SERVER["SCRIPT_NAME"]. "\n";
         * echo "SCRIPT_FILENAME: ". $_SERVER["SCRIPT_FILENAME"]. "\n";
         * echo "PATH_TRANSLATED: ". $_SERVER["PATH_TRANSLATED"]. "\n";
         * echo "ILIAS_WEB_DIR: ". ILIAS_WEB_DIR. "\n";
         * echo "ILIAS_HTTP_PATH: ". ILIAS_HTTP_PATH. "\n";
         * echo "ILIAS_ABSOLUTE_PATH: ". ILIAS_ABSOLUTE_PATH. "\n";
         * echo "ILIAS_MODULE: ". ILIAS_MODULE. "\n";
         * echo "CLIENT_ID: ". CLIENT_ID. "\n";
         * echo "CLIENT_WEB_DIR: ". CLIENT_WEB_DIR. "\n";
         * echo "subpath: ". $this->subpath. "\n";
         * echo "file: ". $this->file. "\n";
         * echo "disposition: ". $this->disposition. "\n";
         * echo "ckeck_ip: ". $this->check_ip. "\n";
         * echo "requesttype: ". $this->requestType. "\n";
         * echo "</pre>";
         *
         * # echo phpinfo();
         * exit;
         */
    }

    /**
     * Main function for handle Requests
     *
     * @param string $path
     *            the path of the request
     * @param string $method
     *            the REQUEST_METHOD
     * @return boolean
     */
    public function handleRequest($path, $method)
    {
        ilLoggerFactory::getLogger('xmh')->debug('Request for: ' . $path);
        
        try {
            // check if it is a request for an upload
            if (0 == strcmp('/upload', $path)) {
                ilLoggerFactory::getLogger('xmh')->debug('uploadrequest for: ' . print_r($this->params, true));
                switch ($method) {
                    case 'GET':
                        $this->requestType = 'uploadCheck';
                        $this->setID();
                        $this->checkEpisodeAccess();
                        $this->checkChunk();
                        break;
                    case 'POST':
                        $this->requestType = 'upload';
                        $this->setID();
                        ilLoggerFactory::getLogger('xmh')->debug('Upload for: ' . $this->obj_id);
                        $this->checkEpisodeAccess();
                        $this->uploadChunk();
                        break;
                }
            } else if (0 == strcmp('/createEpisode', $path)) {
                $this->requestType = 'createEpisode';
                $this->setID();
                ilLoggerFactory::getLogger('xmh')->debug('CreatedEpisode for: ' . $this->obj_id);
                $this->checkEpisodeAccess();
                $this->createEpisode();
            } else if (0 == strcmp('/newJob', $path)) {
                $this->requestType = 'newJob';
                $this->setID();
                ilLoggerFactory::getLogger('xmh')->debug('NewJob for: ' . $this->obj_id);
                $this->checkEpisodeAccess();
                $this->createNewJob();
            } else if (0 == strcmp('/finishUpload', $path)) {
                $this->requestType = 'finishUpload';
                $this->setID();
                ilLoggerFactory::getLogger('xmh')->debug('NewJob for: ' . $this->obj_id);
                $this->checkEpisodeAccess();
                $this->finishUpload();
            }
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * extract obj_id from the request param
     *
     * @throws Exception if the seriesid have wrong syntax
     * @access private
     */
    private function setID()
    {
        $series_id = $this->params['seriesid'];
        
        if (! preg_match('/^[0-9]+/', $series_id)) {
            throw new Exception($this->plugin->txt('series'), 404);
        }
        
        $this->obj_id = intval($series_id);
    }

    /**
     * Returns the type of request.
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Check access rights of the requested file.
     */
    public function checkEpisodeAccess()
    {
        if ($this->checkAccessObject($this->obj_id)) {
            return true;
        }
        // none of the checks above gives access
        throw new Exception($this->plugin->txt('msg_no_perm_read'), 403);
    }

    /**
     * Check access rights for an object by its object id.
     *
     * @param
     *            int object id
     *            
     * @return bool access given (true/false)
     */
    private function checkAccessObject($obj_id)
    {
        global $DIC;
        
        $obj_type = ilObject::_lookupType($obj_id);
        
        $ref_ids = ilObject::_getAllReferences($obj_id);
        $access = $DIC->access();
        
        foreach ($ref_ids as $ref_id) {
            ilLoggerFactory::getLogger('xmh')->debug("checking object for refid: " . $ref_id . " and user: " . $DIC->user()
                ->getId() . " result:" . $access->checkAccess("write", "upload", $ref_id));
            if ($access->checkAccess("access", "upload", $ref_id)) {
                return true;
            }
        }
        ilLoggerFactory::getLogger('xmh')->debug("checking object access failed for upload");
        
        return false;
    }

    private function createCURLCall($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser() . ':' . $this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Requested-Auth: Digest',
            'X-Opencast-Matterhorn-Authorization: true'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        return $ch;
    }

    private function createEpisode()
    {
        global $ilUser;
        
        $basedir = $this->plugin->getDirectory();
        
        // parameter checking
        $episodename = urldecode($this->params['episodename']);
        $episodedate = urldecode($this->params['episodedate']);
        $episodetime = urldecode($this->params['episodetime']);
        $creator = urldecode($this->params['presenter']);
        $datestring = $episodedate . 'T' . $episodetime . 'Z';
        if (! $episodename) {
            throw new Exception('Missing parameter episodename', 400);
        }
        if (! (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $episodedate, $matches) && checkdate($matches[2], $matches[3], $matches[1]))) {
            ilLoggerFactory::getLogger('xmh')->debug('episodedate:' . $matches[2] . 's');
            throw new Exception('Missing or bad parameter episodedate', 400);
        }
        if (! (preg_match('/\d{1,2}:\d{1,2}/', $episodetime, $matches) && 0 <= $matches[1] && $matches[2] <= 23 && 0 <= $matches[2] && $matches[2] <= 59)) {
            throw new Exception('Missing or bad parameter episodetime', 400);
        }
        
        // create an episode.xml for this media package
        $dom = new DOMDocument();
        $dom->load($basedir . '/templates/xml/episode.xml');
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('dcterms', 'http://purl.org/dc/terms/');
        $result = $xpath->query('//dcterms:title');
        $result->item(0)->nodeValue = $episodename;
        $result = $xpath->query('//dcterms:isPartOf');
        $result->item(0)->nodeValue = $this->configObject->getSeriesPrefix() . $this->obj_id;
        $result = $xpath->query('//dcterms:recordDate');
        $result->item(0)->nodeValue = $datestring;
        $result = $xpath->query('//dcterms:created');
        $result->item(0)->nodeValue = $datestring;
        if ($creator) {
            $result = $xpath->query('//dcterms:creator');
            $result->item(0)->nodeValue = $creator;
        }
        $episodexml = $dom->saveXML();
        
        // get the series xml for this mediapackage
        $url = $this->configObject->getMatterhornServer() . '/series/' . $this->configObject->getSeriesPrefix() . $this->obj_id . '.xml';
        $ch = $this->createCURLCall($url);
        $seriesxml = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (! $httpCode) {
            throw new Exception(curl_error($ch), 503);
        }
        
        // create a new media package and fix start date
        $url = $this->configObject->getMatterhornServer() . '/ingest/createMediaPackage';
        $ch = $this->createCURLCall($url);
        $mediapackage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $pattern = '/start=\"(.*)\" id/';
        $replacement = 'start="' . $datestring . '" id';
        $mediapackage = preg_replace($pattern, $replacement, $mediapackage);
        
        // add episode.xml to media package
        ilLoggerFactory::getLogger('xmh')->debug($httpCode . $mediapackage);
        $fields = array(
            'flavor' => 'dublincore/episode',
            'mediaPackage' => $mediapackage,
            'dublinCore' => $episodexml
        );
        $fields_string = http_build_query($fields);
        $url = $this->configObject->getMatterhornServer() . '/ingest/addDCCatalog';
        $ch = $this->createCURLCall($url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $mediapackage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        ilLoggerFactory::getLogger('xmh')->debug($httpCode . $mediapackage);
        
        // add series.xml to media package
        $fields = array(
            'flavor' => 'dublincore/series',
            'mediaPackage' => $mediapackage,
            'dublinCore' => $seriesxml
        );
        $fields_string = http_build_query($fields);
        $url = $this->configObject->getMatterhornServer() . '/ingest/addDCCatalog';
        $ch = $this->createCURLCall($url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $mediapackage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $mpid = uniqid();
        $_SESSION['iliasupload_mpid_' . $mpid] = $mediapackage;
        header('Content-Type: text/text');
        echo $mpid;
    }

    private function createNewJob()
    {
        $key_mpid = 'iliasupload_mpid_' . $this->params['mpid'];
        if (! array_key_exists($key_mpid, $_SESSION)) {
            throw new Exception('Missing parameter mpid', 400);
        }
        $realmp = $_SESSION[$key_mpid];
        
        $fields = array(
            'filename' => $this->params['filename'],
            'filesize' => $this->params['filesize'],
            'chunksize' => $this->params['chunksize'],
            'flavor' => 'presentation/source',
            'mediapackage' => $realmp
        );
        $fields_string = http_build_query($fields);
        $url = $this->configObject->getMatterhornServer() . '/upload/newjob';
        $ch = $this->createCURLCall($url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $job = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        ilLoggerFactory::getLogger('xmh')->debug($httpCode . $job);
        
        header('Content-Type: text/text');
        echo $job;
    }

    private function checkChunk()
    {
        if (! (isset($_GET['resumableIdentifier']) && trim($_GET['resumableIdentifier']) != '')) {
            $_GET['resumableIdentifier'] = '';
        }
        $temp_dir = 'temp/' . $_GET['resumableIdentifier'];
        if (! (isset($_GET['resumableFilename']) && trim($_GET['resumableFilename']) != '')) {
            $_GET['resumableFilename'] = '';
        }
        if (! (isset($_GET['resumableChunkNumber']) && trim($_GET['resumableChunkNumber']) != '')) {
            $_GET['resumableChunkNumber'] = '';
        }
        $chunk_file = $temp_dir . '/' . $_GET['resumableFilename'] . '.part' . $_GET['resumableChunkNumber'];
        if (file_exists($chunk_file)) {
            http_response_code(200);
        } else {
            throw new Exception("", 404);
        }
    }

    private function uploadChunk()
    {
        if (! empty($_FILES)) {
            foreach ($_FILES as $file) {
                switch ($file['error']) {
                    case UPLOAD_ERR_OK:
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        throw new RuntimeException('No file sent.', 400);
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        throw new RuntimeException('Exceeded filesize limit.', 413);
                    default:
                        throw new RuntimeException('Unknown errors.', 500);
                }
                $tmpfile = $file['tmp_name'];
                $filename = $_POST['resumableFilename'];
                $chunknumber = $_POST['resumableChunkNumber'];
                $jobID = $_POST['jobid'];
                ilLoggerFactory::getLogger('xmh')->debug($tmpfile);
                if (function_exists('curl_file_create')) {
                    $cFile = curl_file_create($tmpfile);
                } else {
                    $cFile = '@' . realpath($tmpfile);
                }
                $fields = array(
                    'chunknumber' => urlencode($chunknumber - 1),
                    'filedata' => $cFile
                );
                $url = $this->configObject->getMatterhornServer() . '/upload/job/' . $jobID;
                $ch = $this->createCURLCall($url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                $job = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                ilLoggerFactory::getLogger('xmh')->debug($httpCode . $job);
                $jobid = uniqid();
                $_SESSION['iliasupload_jobid_' . $jobid] = $job;
                
                header('Content-Type: text/text');
                echo $jobid;
            }
        }
    }

    private function finishUpload()
    {
        if (! array_key_exists('iliasupload_jobid_' . $_POST['jobid'], $_SESSION)) {
            throw new Exception('Missing parameter jobid', 400);
        }
        if (! array_key_exists('iliasupload_mpid_' . $_POST['mpid'], $_SESSION)) {
            throw new Exception('Missing parameter mpid', 400);
        }
        $realjob = $_SESSION['iliasupload_jobid_' . $_POST['jobid']];
        $realmp = $_SESSION['iliasupload_mpid_' . $_POST['mpid']];
        unset($_SESSION['iliasupload_mpid_' . $_POST['mpid']]);
        unset($_SESSION['iliasupload_jobid_' . $_POST['jobid']]);
        $trimeditor = isset($_POST['trimeditor']) && $_POST['trimeditor'] === "true";
        
        $jobxml = new SimpleXMLElement($realjob);
        ilLoggerFactory::getLogger('xmh')->debug($jobxml->payload[0]->url);
        
        $fields = array(
            'mediaPackage' => $realmp,
            'url' => $jobxml->payload[0]->url,
            'flavor' => 'presentation/source'
        );
        $fields_string = http_build_query($fields);
        $url = $this->configObject->getMatterhornServer() . '/ingest/addTrack';
        $ch = $this->createCURLCall($url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $mediapackage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        ilLoggerFactory::getLogger('xmh')->debug("Adding track to MP: " . $httpCode);
        
        $fields = array(
            'mediaPackage' => $mediapackage,
            'straightToPublishing' => $trimeditor ? "false" : "true",
            'distribution' => 'ILIAS-Upload'
        );
        $fields_string = http_build_query($fields);
        $url = $this->configObject->getMatterhornServer() . '/ingest/ingest/' . $this->configObject->getUploadWorkflow();
        $ch = $this->createCURLCall($url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $workflow = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        header('Content-Type: text/text');
        echo 'created workflow';
    }

    /**
     * Send an error response for the requested file.
     *
     * @param Exception $exception
     */
    public function sendError($exception)
    {
        $errorcode = $exception->getCode();
        $errortext = $exception->getMessage();
        
        ilLoggerFactory::getLogger('xmh')->debug($errorcode . " " . $errortext);
        
        http_response_code($errorcode);
        echo $errortext;
        exit();
    }
}
