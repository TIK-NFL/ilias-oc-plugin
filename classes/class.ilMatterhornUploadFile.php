<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
// Prevent a general redirect to the login screen for anonymous users.
// The checker will show an error page with login link instead
// (see ilInitialisation::InitILIAS() for details)
$_GET["baseClass"] = "ilStartUpGUI";

$basename = "/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData";

// Define a pseudo module to get a correct ILIAS_HTTP_PATH 
// (needed for links on the error page).
// "data" is assumed to be the ILIAS_WEB_DIR
// (see ilInitialisation::buildHTTPPath() for details)
define("ILIAS_MODULE", substr($_SERVER['PHP_SELF'],
    strpos($_SERVER['PHP_SELF'], $basename) + strlen($basename)+1));

// Define the cookie path to prevent a different session created for web access
// (see ilInitialisation::setCookieParams() for details)
$GLOBALS['COOKIE_PATH'] = substr($_SERVER['PHP_SELF'], 0,
                          strpos($_SERVER['PHP_SELF'], $basename));

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_WEB_ACCESS_CHECK);

// Now the ILIAS header can be included
require_once "./include/inc.header.php";
require_once "./Services/Utilities/classes/class.ilUtil.php";
require_once "./Services/Object/classes/class.ilObject.php";
require_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";


/**
* Class ilMatterhornUploadFile
*
* A class that contains the logic to send the individual chunks received onto Opencast
*
* @auther Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
*
*/

class ilMatterhornUploadFile
{
    var $lng;
    var $ilAccess;

    /**
     * the id of the matterhorn object
     * @var string
     * @access private
     */
    var $obj_id; 
        
    /**
    * errorcode for sendError
    * @var integer
    * @access private
    */
    var $errorcode;

        
    /**
    * errortext for sendError
    * @var integer
    * @access private
    */
    var $errortext;


    /**
     * the configuration for the matterhorn object
     * @var ilMatterhornConfig
     * @access private
     */
    var $configObject;

    /**
     * Stores the instance of the slim rest server to use
    var $app;
    
    /**
    * Constructor
    * @access   public
    */
    function ilMatterhornUploadFile()
    {
        global  $ilAccess, $lng, $ilLog;
        
        $this->lng =& $lng;
        $this->ilAccess =& $ilAccess;
        $this->params = array();
        $this->requestType = "none";
        
        // get the requested file and its type
        $uri = parse_url($_SERVER["REQUEST_URI"]);
        parse_str($uri["query"], $this->params);        
        #$ilLog->write("Request for:".substr($uri["path"],0,strpos($_SERVER["PHP_SELF"],"/sendfile.php")+1)."/episode.json");
        $ilLog->write("Request for: ".$uri["path"]);
        #$ilLog->write("Request for:".strcmp(md5(substr($uri["path"],0,strpos($_SERVER["PHP_SELF"],"/sendfile.php"))."/episode.json"), md5($uri["path"])));
        
        // initialize app
        
        global $basename;               
        // check if it is a request for an upload
        if(0 == strcmp(str_replace("/uploadfile.php","/upload",$_SERVER["PHP_SELF"]), $uri["path"])){
            $ilLog->write("uploadrequest for: ".print_r($this->params,true));
            switch ($_SERVER['REQUEST_METHOD']) {
                case "GET":
                    $this->requestType = "uploadCheck";
                    if (!preg_match('/^[0-9]+/', $this->params['seriesid'])) {
                        $this->errorcode = 404;
                        $this->errortext = $this->lng->txt("series");
                        return false;               
                    }
                    $this->obj_id = $this->params['seriesid'];
                    break;
                case "POST":
                    $this->requestType = "upload";
                    if (!preg_match('/^[0-9]+/', $_POST['seriesid'])) {
                        $this->errorcode = 404;
                        $this->errortext = $this->lng->txt("series");
                        return false;
                    }
                    $ilLog->write("Upload for: ".$_POST['seriesid']);
                    $this->obj_id = $_POST['seriesid'];
                    break;
            }
            
        } else  { 
            if(0 == strcmp(str_replace("/uploadfile.php","/createEpisode",$_SERVER["PHP_SELF"]), $uri["path"])){
                $this->requestType = "createEpisode";
                if (!preg_match('/^[0-9]+/', $_POST['seriesid'])) {
                    $this->errorcode = 404;
                    $this->errortext = $this->lng->txt("series");
                    return false;
                }
                $ilLog->write("CreatedEpisode for: ".$_POST['seriesid']);
                $this->obj_id = $_POST['seriesid'];
            } else {
                if(0 == strcmp(str_replace("/uploadfile.php","/newJob",$_SERVER["PHP_SELF"]), $uri["path"])){
                    $this->requestType = "newJob";
                    if (!preg_match('/^[0-9]+/', $_POST['seriesid'])) {
                        $this->errorcode = 404;
                        $this->errortext = $this->lng->txt("series");
                        return false;
                    }
                    $ilLog->write("NewJob for: ".$_POST['seriesid']);
                    $this->obj_id = $_POST['seriesid'];
                } else {
                    if(0 == strcmp(str_replace("/uploadfile.php","/finishUpload",$_SERVER["PHP_SELF"]), $uri["path"])){
                        $this->requestType = "finishUpload";
                        if (!preg_match('/^[0-9]+/', $_POST['seriesid'])) {
                            $this->errorcode = 404;
                            $this->errortext = $this->lng->txt("series");
                            return false;
                        }
                        $ilLog->write("NewJob for: ".$_POST['seriesid']);
                        $this->obj_id = $_POST['seriesid'];
                    } 
                }            

            }            
        }
        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilMatterhornConfig.php");
        $this->configObject = new ilMatterhornConfig(); 
        // debugging
/*      echo "<pre>";
        var_dump($uri);
        echo "REQUEST_URI:         ". $_SERVER["REQUEST_URI"]. "\n";
        echo "Parsed URI:          ". $uri["path"]. "\n";
        echo "DOCUMENT_ROOT:       ". $_SERVER["DOCUMENT_ROOT"]. "\n";
        echo "PHP_SELF:            ". $_SERVER["PHP_SELF"]. "\n";
        echo "SCRIPT_NAME:         ". $_SERVER["SCRIPT_NAME"]. "\n";
        echo "SCRIPT_FILENAME:     ". $_SERVER["SCRIPT_FILENAME"]. "\n";
        echo "PATH_TRANSLATED:     ". $_SERVER["PATH_TRANSLATED"]. "\n";
        echo "ILIAS_WEB_DIR:       ". ILIAS_WEB_DIR. "\n";
        echo "ILIAS_HTTP_PATH:     ". ILIAS_HTTP_PATH. "\n";
        echo "ILIAS_ABSOLUTE_PATH: ". ILIAS_ABSOLUTE_PATH. "\n";
        echo "ILIAS_MODULE:        ". ILIAS_MODULE. "\n";
        echo "CLIENT_ID:           ". CLIENT_ID. "\n";
        echo "CLIENT_WEB_DIR:      ". CLIENT_WEB_DIR. "\n";
        echo "subpath:             ". $this->subpath. "\n";
        echo "file:                ". $this->file. "\n";
        echo "disposition:         ". $this->disposition. "\n";
        echo "ckeck_ip:            ". $this->check_ip. "\n";
        echo "send_mimetype:       ". $this->send_mimetype. "\n";
        echo "requesttype:         ". $this->requestType. "\n";
        echo "errorcode:           ". $this->errorcode. "\n";
        echo "errortext:           ". $this->errortype. "\n";
        echo "</pre>";

        #       echo phpinfo();
        exit;
    */
        /*
        if (!file_exists($this->file))
        {
            $this->errorcode = 404;
            $this->errortext = $this->lng->txt("url_not_found");
            return false;
        }
        */
    }

    /** 
     * Returns the type of request
     * @access public
     */
    public function getRequestType(){
        return $this->requestType;
    }
    
    /**
     * Determine the current user(s)
     */
    public function determineUser()
    {
        global $ilUser;
        
        // a valid user session is found 
        if ($_SESSION["AccountId"])
        {
            $this->check_users = array($_SESSION["AccountId"]); 
            return;
        }      
        else
        {
            $this->check_users = array(ANONYMOUS_USER_ID);
        $_SESSION["AccountId"] = ANONYMOUS_USER_ID;
            $ilUser->setId(ANONYMOUS_USER_ID);
            $ilUser->read();    
            return;
        }
    }

    /**
     * Check access rights of the requested file
     * @access  public
     */
    public function checkEpisodeAccess()
    {
        // an error already occurred at class initialisation
        if ($this->errorcode)
        {
            return false;
        }   
        // do this here because ip based checking may be set after construction
        $this->determineUser();
        if ($this->checkAccessObject($this->obj_id))
        {
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
    * Check access rights of the requested file
    * @access   public
    */
    public function checkFileAccess()
    {
        #global $ilLog;
        #$ilLog->write("MHSendfile: check access for ". $this->obj_id);
        // an error already occurred at class initialisation
        if ($this->errorcode)
        {
            #$ilLog->write("MHSendfile: check access already has error code for ". $this->obj_id);
            return false;
        }

        // do this here because ip based checking may be set after construction
        $this->determineUser();
        
        $type = 'xmh';
        $iliasid = substr($this->obj_id,10);
        if (!$iliasid || $type == 'none')
        {
            $this->errorcode = 404;
            $this->errortext = $this->lng->txt("obj_not_found");
            #$ilLog->write("MHSendfile: obj_not_found");
            return false;
        }
        if ($this->checkAccessObject($iliasid))
        {
            return true;
        }
        #$ilLog->write("MHSendfile: no access found");
        // none of the checks above gives access
        $this->errorcode = 403;
        $this->errortext = $this->lng->txt('msg_no_perm_read');
        return false;
    }
    
    
    
    
    /**
    * Check access rights for an object by its object id
    *
    * @param    int         object id
    * @return   boolean     access given (true/false)
    */
    private function checkAccessObject($obj_id, $obj_type = '')
    {
        global $ilAccess;

        if (!$obj_type)
        {
            $obj_type = ilObject::_lookupType($obj_id);
        }   
        $ref_ids  = ilObject::_getAllReferences($obj_id);

        foreach($ref_ids as $ref_id)
        {
            foreach ($this->check_users as $user_id)
            {               
                if ($ilAccess->checkAccessOfUser($user_id, "write", "", $ref_id, $obj_type, $obj_id))
                {
                    return true;
                }
            }
        }
        return false;
    }

    public function createEpisode(){
        
        global  $ilUser, $ilLog;

        $episodexml = '<dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:dcterms="http://purl.org/dc/terms/">
          <dcterms:title>'.urldecode($_POST["episodename"]).'</dcterms:title>
          <dcterms:creator>'.urldecode($_POST["presenter"]).'</dcterms:creator>
          <dcterms:isPartOf>ilias_xmh_'.$this->obj_id.'</dcterms:isPartOf>
          <dcterms:license>Creative Commons 3.0: Attribution-NonCommercial-NoDerivs</dcterms:license>
          <dcterms:recordDate>'.urldecode($_POST["episodedate"]).'T'.urldecode($_POST["episodetime"]).'Z</dcterms:recordDate>
          <dcterms:contributor></dcterms:contributor>
          <dcterms:subject></dcterms:subject>
          <dcterms:language></dcterms:language>
          <dcterms:description></dcterms:description>
          <dcterms:rights></dcterms:rights>
          <dcterms:created>'.urldecode($_POST["episodedate"]).'T'.urldecode($_POST["episodetime"]).'Z</dcterms:created>
          </dublincore>';
        
        $ch = curl_init();        
        $url = $this->configObject->getMatterhornServer()."/series/ilias_xmh_".$this->obj_id.".xml";
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $seriesxml = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ilLog->write("Seriesxml:".$httpCode);
        
        $url = $this->configObject->getMatterhornServer()."/ingest/createMediaPackage";
        $ilLog->write("MHObj MHServer:".$url);
        
        //open connection
        $ch = curl_init();
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $mediapackage = curl_exec($ch);
        $pattern = '/start=\"(.*)\" id/';
        $replacement = 'start="'.urldecode($_POST["episodedate"]).'T'.urldecode($_POST["episodetime"]).'Z" id';
        $mediapackage = preg_replace($pattern, $replacement, $mediapackage);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $ilLog->write($httpCode.$mediapackage);         
        $fields = array("flavor" => urlencode("dublincore/episode"),"mediaPackage" => urlencode($mediapackage), "dublinCore" => urlencode($episodexml));

        $fields_string="";
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string,'&');
        $ch = curl_init();        
        $url = $this->configObject->getMatterhornServer()."/ingest/addDCCatalog";
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $mediapackage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ilLog->write($httpCode.$mediapackage);
        
        $fields_string="";
        $fields = array("flavor" => urlencode("dublincore/series"),"mediaPackage" => urlencode($mediapackage), "dublinCore" => urlencode($seriesxml));
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string,'&');
        $ch = curl_init();        
        $url = $this->configObject->getMatterhornServer()."/ingest/addDCCatalog";
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $mediapackage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ilLog->write($httpCode.$mediapackage);

        header("Content-Type: text/xml");
        echo $mediapackage;
    }
    
    public function createNewJob(){
        global $ilLog;
        $fields = array("flavor" => urlencode("presentation/source"),
                        "mediaPackage" => urlencode($_POST["mediapackage"]), 
                        "filename" => urlencode($_POST["filename"]),
                        "filesize" => urlencode($_POST["filesize"]),
                        "chunksize" => urlencode($_POST["chunksize"])
                        );
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string,'&');
        $ch = curl_init();        
        $url = $this->configObject->getMatterhornServer()."/upload/newjob";
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $job = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ilLog->write($httpCode.$job);
        header("Content-Type: text/text");
        echo $job;
    }
        
    public function checkChunk(){
        if(!(isset($_GET['resumableIdentifier']) && trim($_GET['resumableIdentifier'])!='')){
            $_GET['resumableIdentifier']='';
        }
        $temp_dir = 'temp/'.$_GET['resumableIdentifier'];
        if(!(isset($_GET['resumableFilename']) && trim($_GET['resumableFilename'])!='')){
            $_GET['resumableFilename']='';
        }
        if(!(isset($_GET['resumableChunkNumber']) && trim($_GET['resumableChunkNumber'])!='')){
            $_GET['resumableChunkNumber']='';
        }
        $chunk_file = $temp_dir.'/'.$_GET['resumableFilename'].'.part'.$_GET['resumableChunkNumber'];
        if (file_exists($chunk_file)) {
            header("HTTP/1.0 200 Ok");
        } else {
            header("HTTP/1.0 404 Not Found");
        }
    }
    
    public function uploadChunk(){
        if (!empty($_FILES)) foreach ($_FILES as $file) {
            switch ($file['error']) {
              case UPLOAD_ERR_OK:
                  break;
              case UPLOAD_ERR_NO_FILE:
                  $ilLog->write('Upload_error: No file sent.');
                  throw new RuntimeException('No file sent.');
              case UPLOAD_ERR_INI_SIZE:
              case UPLOAD_ERR_FORM_SIZE:
                  $ilLog->write('Upload_error: Exceeded filesize limit.');
                  throw new RuntimeException('Exceeded filesize limit.');
              default:
                  $ilLog->write('Upload_error: Unknown errors.');
                  throw new RuntimeException('Unknown errors.');
            }
            $tmpfile = $file['tmp_name'];
            $filename = $_POST['resumableFilename'];
            $chunknumber = $_POST['resumableChunkNumber'];
            $jobID = $_POST['jobid'];
            global $ilLog;
            $ilLog->write($tmpfile);
            if (function_exists('curl_file_create')) { 
                $cFile = curl_file_create($tmpfile); 
            } else { 
                $cFile = '@' . realpath($tmpfile); 
            }
            $fields = array("chunknumber" => urlencode($chunknumber-1),
                            "filedata" => $cFile
                            );
            #foreach($fields as $key=>$valuie) { $fields_string .= $key.'='.$value.'&'; }
            #rtrim($fields_string,'&');
            $ch = curl_init();        
            $url = $this->configObject->getMatterhornServer()."/upload/job/".$jobID;
            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_POST,count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
            $job = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ilLog->write($httpCode.$job);
            header("Content-Type: text/xml");
            echo $job;
        }       
    }

    
    public function finishUpload(){
        global $ilLog;
        $mediapackage = $_POST["mediaPackage"];
        $ilLog->write($mediapackage);
        $fields_string = "";
        $fields = array("mediapackage" => $mediapackage, 
                        "trackUri" => urlencode($_POST["fileurl"]),
                        "flavor" => urlencode("presentation/source")
                        );
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string,'&');
        $ch = curl_init();        
        $url = $this->configObject->getMatterhornServer()."/mediapackage/addTrack";
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $mediapackage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ilLog->write($httpCode.$mediapackage);
        $fields_string = "";
        $fields = array("mediaPackage" => urlencode($mediapackage), 
                        "trimHold" => "true",
                        "archiveOp" => "true",
                        "distribution" => urlencode("Matterhorn Media Module")
                        );
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string,'&');
        $ch = curl_init();        
        $url = $this->configObject->getMatterhornServer()."/ingest/ingest/ilias";
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $workflow = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ilLog->write($httpCode.$workflow);
        header("Content-Type: text/xml");
        echo $workflow;

    }

    
    
    /**
    * Send an error response for the requested file
    * @access   public
    */
    public function sendError()
    {
        global $ilUser, $tpl, $lng, $tree;

        switch ($this->errorcode)
        {
            case 404:
                header("HTTP/1.0 404 Not Found");
                return;
//              break;
            case 403:
            default:
                header("HTTP/1.0 403 Forbidden");
                return;
                
                //break;
        }
        
        // set the page base to the ILIAS directory
        // to get correct references for images and css files
        $tpl->setCurrentBlock("HeadBaseTag");
        $tpl->setVariable('BASE', ILIAS_HTTP_PATH . '/error.php');
        $tpl->parseCurrentBlock();
        $tpl->addBlockFile("CONTENT", "content", "tpl.error.html");

        // Check if user is logged in
        $anonymous = ($ilUser->getId() == ANONYMOUS_USER_ID);

        if ($anonymous)
        {
            // Provide a link to the login screen for anonymous users

            $tpl->SetCurrentBlock("ErrorLink");
            $tpl->SetVariable("TXT_LINK", $lng->txt('login_to_ilias'));
            $tpl->SetVariable("LINK", ILIAS_HTTP_PATH. '/login.php?cmd=force_login&client_id='.CLIENT_ID);
            $tpl->ParseCurrentBlock();
        }
        else
        {
            // Provide a link to the repository for authentified users

            $nd = $tree->getNodeData(ROOT_FOLDER_ID);
            $txt = $nd['title'] == 'ILIAS' ? $lng->txt('repository') : $nd['title'];

            $tpl->SetCurrentBlock("ErrorLink");
            $tpl->SetVariable("TXT_LINK", $txt);
            $tpl->SetVariable("LINK", ILIAS_HTTP_PATH. '/ilias.php?baseClass=ilRepositoryGUI&amp;client_id='.CLIENT_ID);
            $tpl->ParseCurrentBlock();
        }

        $tpl->setCurrentBlock("content");
        $tpl->setVariable("ERROR_MESSAGE",($this->errortext));
        $tpl->setVariable("SRC_IMAGE", ilUtil::getImagePath("mess_failure.png"));
        $tpl->parseCurrentBlock();

        $tpl->show();
        exit;
    }
    
    /**
    * Get the mime type of the requested file
    * @param    string      default type
    * @return   string      mime type
    * @access   public
    */
    public function getMimeType($default = 'application/octet-stream')
    {
        // take a previously set mimetype
        if (isset($this->mimetype))
        {
            return $this->mimetype;
        }
        
        $mime = '';

        include_once("./Services/Utilities/classes/class.ilMimeTypeUtil.php");
        $mime = ilMimeTypeUtil::getMimeType($this->file);
        $this->mimetype = $mime ? $mime : $default;
        return $this->mimetype;
    }
}
?>
