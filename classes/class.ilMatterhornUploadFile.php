<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
// Prevent a general redirect to the login screen for anonymous users.
// The checker will show an error page with login link instead
// (see ilInitialisation::InitILIAS() for details)
$_GET['baseClass'] = 'ilStartUpGUI';

$basename = '/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData';

// Define a pseudo module to get a correct ILIAS_HTTP_PATH
// (needed for links on the error page).
// "data" is assumed to be the ILIAS_WEB_DIR
// (see ilInitialisation::buildHTTPPath() for details)
define('ILIAS_MODULE', substr($_SERVER['PHP_SELF'], strpos($_SERVER['PHP_SELF'], $basename) + strlen($basename) + 1));

// Define the cookie path to prevent a different session created for web access
// (see ilInitialisation::setCookieParams() for details)
$GLOBALS['COOKIE_PATH'] = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], $basename));

include_once 'Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_WAC);

// Now the ILIAS header can be included
require_once './include/inc.header.php';
require_once './Services/Utilities/classes/class.ilUtil.php';
require_once './Services/Objecst/classes/class.ilObject.php';
require_once './Services/MediaObjects/classes/class.ilObjMediaObject.php';

/**
 * Class ilMatterhornUploadFile.
 *
 * A class that contains the logic to send the individual chunks received onto Opencast
 *
 * @auther Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
 */
class ilMatterhornUploadFile
{

    public $lng;

    public $ilAccess;

    /*
     * the id of the matterhorn object
     * @var string
     * @access private
     */
    public $obj_id;

    /*
     * errorcode for sendError
     * @var integer
     * @access private
     */
    public $errorcode;

    /*
     * errortext for sendError
     * @var integer
     * @access private
     */
    public $errortext;

    /*
     * the configuration for the matterhorn object
     * @var ilMatterhornConfig
     * @access private
     */
    public $configObject;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $ilAccess, $lng;
        
        $this->lng = &$lng;
        $this->ilAccess = &$ilAccess;
        $this->params = array();
        $this->requestType = 'none';
        
        // get the requested file and its type
        $uri = parse_url($_SERVER['REQUEST_URI']);
        parse_str($uri['query'], $this->params);
        // ilLoggerFactory::getLogger('xmh')->debug("Request for:".substr($uri["path"],0,strpos($_SERVER["PHP_SELF"],"/sendfile.php")+1)."/episode.json");
        ilLoggerFactory::getLogger('xmh')->debug('Request for: ' . $uri['path']);
        // ilLoggerFactory::getLogger('xmh')->debug("Request for:".strcmp(md5(substr($uri["path"],0,strpos($_SERVER["PHP_SELF"],"/sendfile.php"))."/episode.json"), md5($uri["path"])));
        
        // initialize app
        global $basename;
        // check if it is a request for an upload
        if (0 == strcmp(str_replace('/uploadfile.php', '/upload', $_SERVER['PHP_SELF']), $uri['path'])) {
            ilLoggerFactory::getLogger('xmh')->debug('uploadrequest for: ' . print_r($this->params, true));
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $this->requestType = 'uploadCheck';
                    if (! preg_match('/^[0-9]+/', $this->params['seriesid'])) {
                        $this->errorcode = 404;
                        $this->errortext = $this->lng->txt('series');
                        
                        return false;
                    }
                    $this->obj_id = $this->params['seriesid'];
                    break;
                case 'POST':
                    $this->requestType = 'upload';
                    if (! preg_match('/^[0-9]+/', $_POST['seriesid'])) {
                        $this->errorcode = 404;
                        $this->errortext = $this->lng->txt('series');
                        
                        return false;
                    }
                    ilLoggerFactory::getLogger('xmh')->debug('Upload for: ' . $_POST['seriesid']);
                    $this->obj_id = $_POST['seriesid'];
                    break;
            }
        } else {
            if (0 == strcmp(str_replace('/uploadfile.php', '/createEpisode', $_SERVER['PHP_SELF']), $uri['path'])) {
                $this->requestType = 'createEpisode';
                if (! preg_match('/^[0-9]+/', $_POST['seriesid'])) {
                    $this->errorcode = 404;
                    $this->errortext = $this->lng->txt('series');
                    
                    return false;
                }
                ilLoggerFactory::getLogger('xmh')->debug('CreatedEpisode for: ' . $_POST['seriesid']);
                $this->obj_id = $_POST['seriesid'];
            } else {
                if (0 == strcmp(str_replace('/uploadfile.php', '/newJob', $_SERVER['PHP_SELF']), $uri['path'])) {
                    $this->requestType = 'newJob';
                    if (! preg_match('/^[0-9]+/', $_POST['seriesid'])) {
                        $this->errorcode = 404;
                        $this->errortext = $this->lng->txt('series');
                        
                        return false;
                    }
                    ilLoggerFactory::getLogger('xmh')->debug('NewJob for: ' . $_POST['seriesid']);
                    $this->obj_id = $_POST['seriesid'];
                } else {
                    if (0 == strcmp(str_replace('/uploadfile.php', '/finishUpload', $_SERVER['PHP_SELF']), $uri['path'])) {
                        $this->requestType = 'finishUpload';
                        if (! preg_match('/^[0-9]+/', $_POST['seriesid'])) {
                            $this->errorcode = 404;
                            $this->errortext = $this->lng->txt('series');
                            
                            return false;
                        }
                        ilLoggerFactory::getLogger('xmh')->debug('NewJob for: ' . $_POST['seriesid']);
                        $this->obj_id = $_POST['seriesid'];
                    }
                }
            }
        }
        include_once './Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilMatterhornConfig.php';
        $this->configObject = new ilMatterhornConfig();
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
         * echo "errorcode: ". $this->errorcode. "\n";
         * echo "errortext: ". $this->errortype. "\n";
         * echo "</pre>";
         *
         * # echo phpinfo();
         * exit;
         */
        /*
         * if (!file_exists($this->file))
         * {
         * $this->errorcode = 404;
         * $this->errortext = $this->lng->txt("url_not_found");
         * return false;
         * }
         */
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
        // an error already occurred at class initialisation
        if ($this->errorcode) {
            return false;
        }
        if ($this->checkAccessObject($this->obj_id)) {
            return true;
        }
        // none of the checks above gives access
        $this->errorcode = 403;
        $this->errortext = $this->lng->txt('msg_no_perm_read');
        
        return false;
    }

    public function checkPreviewAccess()
    {
        return $this->checkFileAccess();
    }

    /**
     * Check access rights of the requested file.
     */
    public function checkFileAccess()
    {
        // ilLoggerFactory::getLogger('xmh')->debug("MHSendfile: check access for ". $this->obj_id);
        // an error already occurred at class initialisation
        if ($this->errorcode) {
            // ilLoggerFactory::getLogger('xmh')->debug("MHSendfile: check access already has error code for ". $this->obj_id);
            return false;
        }
        
        // do this here because ip based checking may be set after construction
        
        $type = 'xmh';
        $iliasid = substr($this->obj_id, 10);
        if (! $iliasid || $type == 'none') {
            $this->errorcode = 404;
            $this->errortext = $this->lng->txt('obj_not_found');
            // ilLoggerFactory::getLogger('xmh')->debug("MHSendfile: obj_not_found");
            return false;
        }
        if ($this->checkAccessObject($iliasid)) {
            return true;
        }
        // ilLoggerFactory::getLogger('xmh')->debug("MHSendfile: no access found");
        // none of the checks above gives access
        $this->errorcode = 403;
        $this->errortext = $this->lng->txt('msg_no_perm_read');
        
        return false;
    }

    /**
     * Check access rights for an object by its object id.
     *
     * @param
     *            int object id
     *            
     * @return bool access given (true/false)
     */
    private function checkAccessObject($obj_id, $obj_type = '')
    {
        global $DIC;
        
        if (! $obj_type) {
            $obj_type = ilObject::_lookupType($obj_id);
        }
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

    public function createEpisode()
    {
        global $ilUser;
        
        $basedir = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn')->getDirectory();
        
        // parameter checking
        $episodename = urldecode($_POST['episodename']);
        $episodedate = urldecode($_POST['episodedate']);
        $episodetime = urldecode($_POST['episodetime']);
        $creator = urldecode($_POST['presenter']);
        $datestring = $episodedate . 'T' . $episodetime . 'Z';
        if (! $episodename) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Missing parameter episodename';
            
            return false;
        }
        if (! (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $episodedate, $matches) && checkdate($matches[2], $matches[3], $matches[1]))) {
            ilLoggerFactory::getLogger('xmh')->debug('episodedate:' . $matches[2] . 's');
            header('HTTP/1.0 400 Bad Request');
            echo 'Missing or bad parameter episodedate';
            
            return false;
        }
        
        if (! (preg_match('/\d{1,2}:\d{1,2}/', $episodetime, $matches) && 0 <= $matches[1] && $matches[2] <= 23 && 0 <= $matches[2] && $matches[2] <= 59)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Missing or bad parameter episodetime';
            
            return false;
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
            'flavor' => urlencode('dublincore/episode'),
            'mediaPackage' => urlencode($mediapackage),
            'dublinCore' => urlencode($episodexml)
        );
        
        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        $url = $this->configObject->getMatterhornServer() . '/ingest/addDCCatalog';
        $ch = $this->createCURLCall($url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $mediapackage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        ilLoggerFactory::getLogger('xmh')->debug($httpCode . $mediapackage);
        
        // add series.xml to media package
        $fields_string = '';
        $fields = array(
            'flavor' => urlencode('dublincore/series'),
            'mediaPackage' => urlencode($mediapackage),
            'dublinCore' => urlencode($seriesxml)
        );
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
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

    public function createNewJob()
    {
        if (! array_key_exists('iliasupload_mpid_' . $_POST['mpid'], $_SESSION)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Missing parameter mpid';
            
            return false;
        }
        $realmp = $_SESSION['iliasupload_mpid_' . $_POST['mpid']];
        $fields = array(
            'filename' => urlencode($_POST['filename']),
            'filesize' => urlencode($_POST['filesize']),
            'chunksize' => urlencode($_POST['chunksize']),
            'flavor' => urlencode('presentation/source'),
            'mediapackage' => $realmp
        );
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
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

    public function checkChunk()
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
            header('HTTP/1.0 200 Ok');
        } else {
            header('HTTP/1.0 404 Not Found');
        }
    }

    public function uploadChunk()
    {
        if (! empty($_FILES)) {
            foreach ($_FILES as $file) {
                switch ($file['error']) {
                    case UPLOAD_ERR_OK:
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        ilLoggerFactory::getLogger('xmh')->debug('Upload_error: No file sent.');
                        throw new RuntimeException('No file sent.');
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        ilLoggerFactory::getLogger('xmh')->debug('Upload_error: Exceeded filesize limit.');
                        throw new RuntimeException('Exceeded filesize limit.');
                    default:
                        ilLoggerFactory::getLogger('xmh')->debug('Upload_error: Unknown errors.');
                        throw new RuntimeException('Unknown errors.');
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

    public function finishUpload()
    {
        if (! array_key_exists('iliasupload_jobid_' . $_POST['jobid'], $_SESSION)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Missing parameter jobid';
            
            return false;
        }
        if (! array_key_exists('iliasupload_mpid_' . $_POST['mpid'], $_SESSION)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Missing parameter mpid';
            
            return false;
        }
        $realjob = $_SESSION['iliasupload_jobid_' . $_POST['jobid']];
        $realmp = $_SESSION['iliasupload_mpid_' . $_POST['mpid']];
        unset($_SESSION['iliasupload_mpid_' . $_POST['mpid']]);
        unset($_SESSION['iliasupload_jobid_' . $_POST['jobid']]);
        $trimeditor = isset($_POST['trimeditor']) && $_POST['trimeditor'] === "true";
        
        $jobxml = new SimpleXMLElement($realjob);
        ilLoggerFactory::getLogger('xmh')->debug($jobxml->payload[0]->url);
        $fields_string = '';
        $fields = array(
            'mediaPackage' => $realmp,
            'url' => urlencode($jobxml->payload[0]->url),
            'flavor' => urlencode('presentation/source')
        );
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        $url = $this->configObject->getMatterhornServer() . '/ingest/addTrack';
        $ch = $this->createCURLCall($url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $mediapackage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        ilLoggerFactory::getLogger('xmh')->debug("Adding track to MP: " . $httpCode);
        $fields_string = '';
        $fields = array(
            'mediaPackage' => urlencode($mediapackage),
            'straightToPublishing' => $trimeditor ? "false" : "true",
            'distribution' => urlencode('ILIAS-Upload')
        );
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
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
     */
    public function sendError()
    {
        global $ilUser, $tpl, $lng, $tree;
        
        switch ($this->errorcode) {
            case 400:
                header('HTTP/1.0 400 Bad Request');
                
                return;
            
            case 404:
                header('HTTP/1.0 404 Not Found');
                
                return;
            // break;
            case 403:
            default:
                header('HTTP/1.0 403 Forbidden');
                
                return;
            
            // break;
        }
        
        // set the page base to the ILIAS directory
        // to get correct references for images and css files
        $tpl->setCurrentBlock('HeadBaseTag');
        $tpl->setVariable('BASE', ILIAS_HTTP_PATH . '/error.php');
        $tpl->parseCurrentBlock();
        $tpl->addBlockFile('CONTENT', 'content', 'tpl.error.html');
        
        // Check if user is logged in
        $anonymous = ($ilUser->getId() == ANONYMOUS_USER_ID);
        
        if ($anonymous) {
            // Provide a link to the login screen for anonymous users
            
            $tpl->SetCurrentBlock('ErrorLink');
            $tpl->SetVariable('TXT_LINK', $lng->txt('login_to_ilias'));
            $tpl->SetVariable('LINK', ILIAS_HTTP_PATH . '/login.php?cmd=force_login&client_id=' . CLIENT_ID);
            $tpl->ParseCurrentBlock();
        } else {
            // Provide a link to the repository for authentified users
            
            $nd = $tree->getNodeData(ROOT_FOLDER_ID);
            $txt = $nd['title'] == 'ILIAS' ? $lng->txt('repository') : $nd['title'];
            
            $tpl->SetCurrentBlock('ErrorLink');
            $tpl->SetVariable('TXT_LINK', $txt);
            $tpl->SetVariable('LINK', ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilRepositoryGUI&amp;client_id=' . CLIENT_ID);
            $tpl->ParseCurrentBlock();
        }
        
        $tpl->setCurrentBlock('content');
        $tpl->setVariable('ERROR_MESSAGE', ($this->errortext));
        $tpl->setVariable('SRC_IMAGE', ilUtil::getImagePath('mess_failure.png'));
        $tpl->parseCurrentBlock();
        
        $tpl->show();
        exit();
    }
}
