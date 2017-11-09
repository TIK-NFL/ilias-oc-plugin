<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilMatterhornSendfile
 *
 * Checks if a user may access the Matterhorn-Object and sends files using sendfile
 * Based on the WebAccessChecker
 *
 * @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id: class.ilWebAccessChecker.php 50013 2014-05-13 16:20:01Z akill $
 */
class ilMatterhornSendfile
{

    public $lng;

    /**
     *
     * @var ilMatterhornPlugin
     * @access plublic
     */
    public $plugin;

    /**
     * relative file path from ilias directory (without leading /)
     *
     * @var string
     * @access private
     */
    private $subpath;

    /**
     * the id of the matterhorn object
     *
     * @var string
     * @access private
     */
    private $obj_id;

    /**
     * the id of the matterhorn episode
     *
     * @var string
     * @access private
     */
    private $episode_id;

    /**
     * absolute path in file system
     *
     * @var string
     * @access private
     */
    private $file;

    /**
     * Stores the request type.
     *
     * @var string
     * @access private
     */
    private $requestType;

    /**
     * the configuration for the matterhorn object
     *
     * @var ilMatterhornConfig
     * @access private
     */
    private $configObject;

    /**
     * Constructor
     *
     * @param mixed $uri
     *            the parsed REQUEST_URI
     * @param string $method
     *            the REQUEST_METHOD
     * @access public
     */
    public function __construct($uri, $method)
    {
        global $lng;
        
        $lng->loadLanguageModule("rep_robj_xmh");
        $this->lng = & $lng;
        $this->params = array();
        $this->requestType = "none";
        $this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn');
        
        if ($method == 'GET') {
            parse_str($uri["query"], $this->params);
        } elseif ($method == 'PUT') {
            parse_str(file_get_contents("php://input"), $this->params);
        }
        
        $this->plugin->includeClass("class.ilMatterhornConfig.php");
        $this->configObject = new ilMatterhornConfig();
        // debugging
        
        // echo "<pre>";
        // var_dump($uri);
        // echo "REQUEST_URI: " . $_SERVER["REQUEST_URI"] . "\n";
        // echo "Parsed URI: " . $uri["path"] . "\n";
        // echo "DOCUMENT_ROOT: " . $_SERVER["DOCUMENT_ROOT"] . "\n";
        // echo "PHP_SELF: " . $_SERVER["PHP_SELF"] . "\n";
        // echo "SCRIPT_NAME: " . $_SERVER["SCRIPT_NAME"] . "\n";
        // echo "SCRIPT_FILENAME: " . $_SERVER["SCRIPT_FILENAME"] . "\n";
        // echo "PATH_TRANSLATED: " . $_SERVER["PATH_TRANSLATED"] . "\n";
        // echo "ILIAS_WEB_DIR: " . ILIAS_WEB_DIR . "\n";
        // echo "ILIAS_HTTP_PATH: " . ILIAS_HTTP_PATH . "\n";
        // echo "ILIAS_ABSOLUTE_PATH: " . ILIAS_ABSOLUTE_PATH . "\n";
        // echo "ILIAS_MODULE: " . ILIAS_MODULE . "\n";
        // echo "CLIENT_ID: " . CLIENT_ID . "\n";
        // echo "CLIENT_WEB_DIR: " . CLIENT_WEB_DIR . "\n";
        // echo "subpath: " . $this->subpath . "\n";
        // echo "file: " . $this->file . "\n";
        // echo "disposition: " . $this->disposition . "\n";
        // echo "ckeck_ip: " . $this->check_ip . "\n";
        // echo "requesttype: " . $this->requestType . "\n";
        // echo "</pre>";
        // var_dump($_SESSION);
        // exit();
        
        // if (! file_exists($this->file)) {
        // throw new Exception($this->lng->txt("url_not_found"), 404);
        // }
    }

    /**
     * Main function for handle Requests
     *
     * @param string $path
     *            the path of the request
     * @return boolean
     */
    public function handleRequest($path)
    {
        ilLoggerFactory::getLogger('xmh')->debug("Request for:" . $path);
        
        try {
            // check if it is a request for an episode
            if (0 == strcmp("/episode.json", $path)) {
                $this->requestType = "episode";
                $this->setID();
                $this->checkEpisodeAccess();
                $this->sendEpisode();
            } else if (0 == strcmp("/usertracking", $path)) {
                $this->requestType = "usertracking";
                $this->setID();
                if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    switch ($this->params['type']) {
                        case "FOOTPRINT":
                            $this->checkEpisodeAccess();
                            $this->putUserTracking();
                            break;
                        case "VIEWS":
                            throw new Exception("not implemented yet", 404);
                            break;
                        default:
                            throw new Exception($this->plugin->txt("no_such_method"), 404);
                    }
                } else {
                    throw new Exception($this->plugin->txt("no_such_method"), 404);
                }
            } else if (0 == strcmp("/usertracking/stats.json", $path)) {
                $this->requestType = "stats";
                $this->setID();
                $this->checkEpisodeAccess();
                $this->sendStats();
            } else if (0 == strcmp("/usertracking/footprint.json", $path)) {
                $this->requestType = "footprint";
                $this->setID();
                $this->checkEpisodeAccess();
                $this->sendFootprint();
            } else if (0 == strcmp("/usertracking/statistic.json", $path)) {
                $this->requestType = "statistic";
                $this->setID();
                $this->checkEpisodeAccess("write");
                $this->sendStatistic();
            } else if (0 == strcmp("/info/me.json", $path)) {
                $this->requestType = "me";
                $this->sendMe();
            } else if (0 == strcmp("/manager/list.json", $path)) {
                $this->requestType = "list";
                $this->sendList();
            } else {
                $this->subpath = urldecode(substr($path, strlen(CLIENT_ID) + 2));
                $this->obj_id = substr($this->subpath, 0, strpos($this->subpath, '/'));
                
                if (! preg_match('/^ilias_xmh_[0-9]+/', $this->obj_id)) {
                    throw new Exception("", 400);
                }
                if (preg_match('/^ilias_xmh_[0-9]+\/[A-Za-z0-9-]+\/preview(sbs|presentation|presenter).(mp4|webm)$/', $this->subpath)) {
                    $this->requestType = "preview";
                    list ($this->obj_id, $this->episode_id) = explode('/', $this->subpath);
                    $this->checkPreviewAccess();
                    $this->sendPreview();
                } else {
                    $this->requestType = "file";
                    $this->file = realpath(ILIAS_ABSOLUTE_PATH . "/" . $this->subpath);
                    $this->checkFileAccess();
                    $this->sendFile();
                }
            }
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * extract obj_id and episode id from the request param
     *
     * @throws Exception if the id have wrong syntax
     * @access private
     */
    private function setID()
    {
        if (! preg_match('/^[0-9]+\/[A-Za-z0-9]+/', $this->params['id'])) {
            throw new Exception("mediapackageId", 400);
        }
        list ($this->obj_id, $this->episode_id) = explode('/', $this->params['id']);
    }

    /**
     * Returns the type of request
     *
     * @return string the request type of this request
     * @access public
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Check access rights of the requested file
     *
     * @param string $permission            
     * @throws Exception if user have no $permission access for the file
     */
    private function checkEpisodeAccess($permission = "read")
    {
        if ($this->checkAccessObject($this->obj_id, $permission)) {
            return;
        }
        // none of the checks above gives access
        throw new Exception($this->lng->txt('msg_no_perm_read'), 403);
    }

    /**
     * Check access rights of the requested preview of the file
     *
     * @throws Exception if user have no access rights for the preview
     */
    private function checkPreviewAccess()
    {
        $this->checkFileAccess();
    }

    /**
     * Check access rights of the requested file
     *
     * @throws Exception if user have no access rights for the file
     * @access public
     */
    public function checkFileAccess()
    {
        // ilLoggerFactory::getLogger('xmh')->debug("MHSendfile: check access for ". $this->obj_id);
        $type = 'xmh';
        $iliasid = substr($this->obj_id, 10);
        if (! $iliasid || $type == 'none') {
            throw new Exception($this->lng->txt("obj_not_found"), 404);
            // ilLoggerFactory::getLogger('xmh')->debug("MHSendfile: obj_not_found");
        }
        if ($this->checkAccessObject($iliasid)) {
            return;
        }
        // ilLoggerFactory::getLogger('xmh')->debug("MHSendfile: no access found");
        // none of the checks above gives access
        throw new Exception($this->lng->txt('msg_no_perm_read'), 403);
    }

    /**
     * Check access rights for an object by its object id
     *
     * @param int $obj_id
     *            object id
     * @param string $permission
     *            read/write
     * @param string $obj_type            
     * @return boolean access given (true/false)
     */
    private function checkAccessObject($obj_id, $permission = 'read', $obj_type = '')
    {
        global $ilAccess, $ilUser;
        if (! $obj_type) {
            $obj_type = ilObject::_lookupType($obj_id);
        }
        $ref_ids = ilObject::_getAllReferences($obj_id);
        foreach ($ref_ids as $ref_id) {
            if ($ilAccess->checkAccessOfUser($ilUser->getId(), $permission, "view", $ref_id, $obj_type, $obj_id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * stores the usertracking data in the database
     */
    private function putUserTracking()
    {
        global $ilUser;
        $intime = intval($this->params['in']);
        $outtime = intval($this->params['out']);
        $user_id = $ilUser->getId();
        
        $this->plugin->includeClass("class.ilMatterhornUserTracking.php");
        ilMatterhornUserTracking::putUserTracking($user_id, $this->episode_id, $intime, $outtime);
        
        header("HTTP/1.0 204 Stored");
    }

    /**
     * send the Statistic overview for the episode as json.
     */
    private function sendStatistic()
    {
        $this->plugin->includeClass("class.ilMatterhornUserTracking.php");
        $statistic = ilMatterhornUserTracking::getStatisticFromVideo($this->episode_id);
        $data = array();
        foreach ($statistic as $name => $value) {
            $content = array();
            $content['name'] = $name;
            
            $content['type'] = "mapping";
            $content['key'] = "time";
            $content['value'] = "views";
            $content['step'] = 10;
            $mapping = array_fill(0, max(array_keys($value)), 0);
            $content['mapping'] = array_replace($mapping, $value);
            
            $data[] = $content;
        }
        
        $infoarray = array();
        $infoarray['name'] = $this->getTitle();
        $infoarray['episode_id'] = $this->episode_id;
        $infoarray['series_id'] = $this->obj_id;
        $infoarray['duration'] = $this->getDuration();
        $infoarray['data'] = $data;
        
        $this->sendJSON($infoarray);
    }

    /**
     * send Footprints for the user
     */
    private function sendFootprint()
    {
        global $ilUser;
        $user_id = $ilUser->getId();
        
        $response = array();
        $this->plugin->includeClass("class.ilMatterhornUserTracking.php");
        $response['footprints'] = ilMatterhornUserTracking::getFootprints($this->episode_id, $user_id);
        $response['last'] = ilMatterhornUserTracking::getLastSecondViewed($this->episode_id, $user_id);
        $this->sendJSON($response);
    }

    /**
     * send Statistics like views
     */
    private function sendStats()
    {
        $response = array();
        $this->plugin->includeClass("class.ilMatterhornUserTracking.php");
        $views = ilMatterhornUserTracking::getViews($this->episode_id);
        $response['stats'] = [
            'views' => $views
        ];
        $this->sendJSON($response);
    }

    /**
     * send the info/me.json
     */
    private function sendMe()
    {
        $this->plugin->includeClass("class.ilMatterhornInfo.php");
        $info = new ilMatterhornInfo();
        $response = $info->getMyInfo();
        $this->sendJSON($response);
    }

    /**
     * send the manager/list.json
     */
    private function sendList()
    {
        $this->plugin->includeClass("class.ilMatterhornInfo.php");
        $info = new ilMatterhornInfo();
        $response = $info->listPlugins();
        $this->sendJSON($response);
    }

    /**
     * Get the Duration of the episode in milliseconds as String
     *
     * @return string duration in milliseconds
     */
    private function getDuration()
    {
        $manifest = new SimpleXMLElement($this->configObject->getXSendfileBasedir() . 'ilias_xmh_' . $this->obj_id . '/' . $this->episode_id . '/manifest.xml', null, true);
        $duration = (string) $manifest['duration'];
        return $duration;
    }

    /**
     * Get the title of the episode
     *
     * @return string
     */
    private function getTitle()
    {
        $manifest = new SimpleXMLElement($this->configObject->getXSendfileBasedir() . 'ilias_xmh_' . $this->obj_id . '/' . $this->episode_id . '/manifest.xml', null, true);
        $title = (string) $manifest->title;
        return $title;
    }

    /**
     * Send the Array as json
     *
     * @param array $array
     *            the array
     */
    private function sendJSON($array)
    {
        header("Content-Type: application/json");
        echo json_encode($array);
    }

    /**
     * Send the requested eposide.json
     *
     * @access public
     */
    public function sendEpisode()
    {
        // ilLoggerFactory::getLogger('xmh')->debug("Manifestbasedir: ".$this->configObject->getXSendfileBasedir().$this->obj_id.'/'.$this->episode_id);
        $manifest = new SimpleXMLElement($this->configObject->getXSendfileBasedir() . 'ilias_xmh_' . $this->obj_id . '/' . $this->episode_id . '/manifest.xml', null, true);
        
        $episode = array();
        $episode['search-results'] = array(
            "total" => "1",
            "result" => array()
        );
        
        $episode['search-results']["result"]["mediapackage"] = array();
        $attachments = array(
            "attachment" => array()
        );
        $previewrefs = [];
        foreach ($manifest->attachments->attachment as $attachment) {
            $att = array();
            if (isset($attachment['id'])) {
                $att['id'] = (string) $attachment['id'];
            }
            if (isset($attachment['type'])) {
                $att['type'] = (string) $attachment['type'];
            }
            if (isset($attachment['ref'])) {
                $att['ref'] = (string) $attachment['ref'];
            }
            if (isset($attachment->mimetype)) {
                $att['mimetype'] = (string) $attachment->mimetype;
            }
            if (isset($attachment->url)) {
                $att['url'] = (string) $attachment->url;
            }
            if (isset($attachment->tags)) {
                $att['tags'] = array(
                    'tag' => array()
                );
                foreach ($attachment->tags->tag as $tag) {
                    array_push($att['tags']['tag'], (string) $tag);
                }
            }
            if (isset($attachment['type']) && (string) $attachment['type'] == "presentation/segment+preview") {
                if (isset($attachment['ref'])) {
                    preg_match("/(.*)time=(.*)F(\d+)/", (string) $attachment['ref'], $regmatches);
                    $previewrefs[$regmatches[2]] = $att;
                }
            }
            array_push($attachments['attachment'], $att);
        }
        // ilLoggerFactory::getLogger('xmh')->debug((string) $segmentxml->MediaTime->MediaDuration);
        // ilLoggerFactory::getLogger('xmh')->debug(print_r($previewrefs,true));
        
        $episode['search-results']["result"]["mediapackage"]['attachments'] = $attachments;
        
        $metadata = array(
            "catalog" => array()
        );
        $segments = null;
        foreach ($manifest->metadata->catalog as $catalog) {
            $cat = array();
            if (isset($catalog['id'])) {
                $cat['id'] = (string) $catalog['id'];
            }
            if (isset($catalog['type'])) {
                $cat['type'] = (string) $catalog['type'];
            }
            if (isset($catalog['ref'])) {
                $cat['ref'] = (string) $catalog['ref'];
            }
            if (isset($catalog->mimetype)) {
                $cat['mimetype'] = (string) $catalog->mimetype;
            }
            if (isset($catalog->url)) {
                $cat['url'] = (string) $catalog->url;
            }
            if (isset($catalog->tags)) {
                $cat['tags'] = array(
                    'tag' => array()
                );
                foreach ($catalog->tags->tag as $tag) {
                    array_push($cat['tags']['tag'], (string) $tag);
                }
            }
            if (isset($catalog['type']) && 0 == strcmp((string) $catalog['type'], 'mpeg-7/segments')) {
                // ilLoggerFactory::getLogger('xmh')->debug("setting catalog to ".(string)$catalog['type']);
                $segments = $catalog;
            }
            if (isset($catalog['type']) && 0 == strcmp((string) $catalog['type'], 'mpeg-7/text')) {
                // ilLoggerFactory::getLogger('xmh')->debug("setting catalog to ".(string)$catalog['type']);
                $segments = $catalog;
            }
            array_push($metadata['catalog'], $cat);
        }
        $episode['search-results']["result"]["mediapackage"]['metadata'] = $metadata;
        
        $media = array(
            "track" => array()
        );
        foreach ($manifest->media->track as $track) {
            $trk = array();
            if (isset($track['id'])) {
                $trk['id'] = (string) $track['id'];
            }
            if (isset($track['type'])) {
                $trk['type'] = (string) $track['type'];
            }
            if (isset($track['ref'])) {
                $trk['ref'] = (string) $track['ref'];
            }
            if (isset($track->mimetype)) {
                $trk['mimetype'] = (string) $track->mimetype;
            }
            if (isset($track->url)) {
                $trk['url'] = (string) $track->url;
            }
            if (isset($track->duration)) {
                $trk['duration'] = (string) $track->duration;
            }
            if (isset($track->tags)) {
                $trk['tags'] = array(
                    'tag' => array()
                );
                foreach ($track->tags->tag as $tag) {
                    array_push($trk['tags']['tag'], (string) $tag);
                }
            }
            if (isset($track->video)) {
                $trk['video'] = array();
                $trk['video']['id'] = (string) $track->video['id'];
                $trk['video']['resolution'] = (string) $track->video->resolution;
            }
            if (isset($track->audio)) {
                $trk['audio'] = array();
                $trk['audio']['id'] = (string) $track->audio['id'];
            }
            array_push($media['track'], $trk);
        }
        
        $episode['search-results']["result"]["mediapackage"]['media'] = $media;
        $episode['search-results']["result"]["mediapackage"]['duration'] = (string) $manifest['duration'];
        $episode['search-results']["result"]["mediapackage"]['id'] = (string) $manifest['id'];
        
        $episode['search-results']["result"]['id'] = (string) $manifest['id'];
        $episode['search-results']["result"]['mediaType'] = "AudioVisual";
        $episode['search-results']["result"]["dcCreated"] = (string) $manifest['start'];
        $episode['search-results']["result"]["dcExtent"] = (string) $manifest['duration'];
        $episode['search-results']["result"]["dcTitle"] = (string) $manifest->title;
        $episode['search-results']["result"]["dcIsPartOf"] = $this->obj_id;
        if (isset($manifest->creators)) {
            $creators = array();
            foreach ($manifest->creators->creator as $creator) {
                array_push($creators, (string) $creator);
            }
            $episode['search-results']["result"]["dcCreator"] = $creators;
        }
        if ($segments) {
            $episode['search-results']["result"]["segments"] = $this->convertSegment($segments, $previewrefs);
        }
        header("Content-Type: application/json");
        echo json_encode($episode);
    }

    private function convertSegment($catalog, $previewrefs)
    {
        $urlsplit = explode('/', (string) $catalog->url);
        end($urlsplit);
        $segmentsxml = new SimpleXMLElement($this->configObject->getXSendfileBasedir() . 'ilias_xmh_' . $this->obj_id . '/' . $this->episode_id . '/' . prev($urlsplit) . '/' . end($urlsplit), null, true);
        
        $segments = array(
            "segment" => array()
        );
        $currentidx = 0;
        $currenttime = 0;
        
        foreach ($segmentsxml->Description->MultimediaContent->Video->TemporalDecomposition->VideoSegment as $segmentxml) {
            $regmatches = array();
            preg_match("/PT(\d+M)?(\d+S)(\d+)?(0)?N1000F/", (string) $segmentxml->MediaTime->MediaDuration, $regmatches);
            $sec = substr($regmatches[2], 0, - 1);
            $min = 0;
            $msec = 0;
            if (0 != strcmp('', $regmatches[1])) {
                $min = substr($regmatches[1], 0, - 1);
            }
            if (0 != strcmp('', $regmatches[3])) {
                $msec = $regmatches[3];
            }
            $segment = array();
            $segment['index'] = $currentidx;
            $segment['time'] = $currenttime;
            $text = "";
            if ($segmentxml->SpatioTemporalDecomposition) {
                foreach ($segmentxml->SpatioTemporalDecomposition->VideoText as $textxml) {
                    $text = $text . " " . (string) $textxml->Text;
                }
            }
            $segment['text'] = $text;
            
            $segment['duration'] = ($min * 60 + $sec) * 1000 + $msec;
            $curmesc = $cursec = $curmin = $remainhour = 0;
            $curmsec = $currenttime % 1000;
            $remainsec = intdiv($currenttime, 1000);
            $cursec = $remainsec % 60;
            $remainmin = intdiv($remainsec, 60);
            $curmin = $remainmin % 60;
            $remainhour = intdiv($remainmin, 60);
            
            $format = "T%02d:%02d:%02d:%03d";
            $timecode = sprintf($format, $remainhour, $curmin, $cursec, $curmsec);
            $oldformat = "T%02d:%02d:%02d:0";
            $oldtimecode = sprintf($oldformat, $remainhour, $curmin, $cursec);
            if (isset($previewrefs[$timecode])) {
                $attachment = $previewrefs[$timecode];
                preg_match("/track:(.*);time=(.*)F(\d+)/", (string) $attachment['ref'], $regmatches);
                $preview = [];
                $preview["$"] = (string) $attachment['url'];
                $preview["ref"] = $regmatches[1];
            } elseif (isset($previewrefs[$oldtimecode])) {
                $attachment = $previewrefs[$oldtimecode];
                preg_match("/track:(.*);time=(.*)F(\d+)/", (string) $attachment['ref'], $regmatches);
                $preview = [];
                $preview["$"] = (string) $attachment['url'];
                $preview["ref"] = $regmatches[1];
            }
            
            $previews = [];
            $previews["preview"] = $preview;
            $segment['previews'] = $previews;
            
            $currentidx ++;
            $currenttime = $currenttime + $segment['duration'];
            array_push($segments['segment'], $segment);
        }
        return $segments;
    }

    /**
     * Send the requested file as if directly delivered from the web server
     *
     * @access public
     */
    public function sendFile()
    {
        
        // header('x-sendfile: '.$this->configObject->getXSendfileBasedir() . substr($this->subpath, strlen($this->obj_id)));
        include_once ("./Services/Utilities/classes/class.ilMimeTypeUtil.php");
        // ilLoggerFactory::getLogger('xmh')->debug("MHSendfile sending file: ".$this->configObject->getXSendfileBasedir().$this->subpath);
        $mime = ilMimeTypeUtil::lookupMimeType($this->configObject->getXSendfileBasedir() . $this->subpath);
        header("Content-Type: " . $mime);
        // if (isset($_SERVER['HTTP_RANGE'])) {
        // ilLoggerFactory::getLogger('xmh')->debug("range request".$_SERVER['HTTP_RANGE']);
        // }
        $file = $this->configObject->getXSendfileBasedir() . $this->subpath;
        $this->sendData($file);
    }

    public function sendData($filename)
    {
        $fp = fopen($filename, 'rb');
        $size = filesize($filename); // File size
        $length = $size; // Content length
        $start = 0; // Start byte
        $end = $size - 1; // End byte
        
        header("Accept-Ranges: 0-$length");
        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $start;
            $c_end = $end;
            
            list (, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit();
            }
            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            $c_end = ($c_end > $end) ? $end : $c_end;
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit();
            }
            $start = $c_start;
            $end = $c_end;
            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }
        
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);
        
        $buffer = 1024 * 8;
        while (! feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            set_time_limit(0);
            echo fread($fp, $buffer);
            flush();
        }
        fclose($fp);
    }

    public function sendPreview()
    {
        $urlsplit = explode('/', $this->subpath);
        $typesplit = explode('.', $urlsplit[2]);
        ilLoggerFactory::getLogger('xmh')->debug(print_r($typesplit, true));
        ilLoggerFactory::getLogger('xmh')->debug('mhpreviewurl' . $typesplit[0] . $typesplit[1] . $urlsplit[1]);
        $realfile = str_replace($this->configObject->getMatterhornEngageServer() . '/static/mh_default_org/internal', $this->configObject->getMatterhornFilesDirectory(), $_SESSION['mhpreviewurl' . $typesplit[0] . $typesplit[1] . $urlsplit[1]]);
        
        ilLoggerFactory::getLogger('xmh')->debug("Real preview file: " . $realfile);
        // header('x-sendfile: '.$this->configObject->getXSendfileBasedir() . substr($this->subpath, strlen($this->obj_id)));
        include_once ("./Services/Utilities/classes/class.ilMimeTypeUtil.php");
        $mime = ilMimeTypeUtil::lookupMimeType($realfile);
        header("Content-Type: " . $mime);
        // if (isset($_SERVER['HTTP_RANGE'])) {
        // ilLoggerFactory::getLogger('xmh')->debug("range request".$_SERVER['HTTP_RANGE']);
        // }
        $this->sendData($realfile);
    }

    /**
     * Send an error response for the requested file
     *
     * @param Exception $exception            
     * @access public
     */
    public function sendError($exception)
    {
        $errorcode = $exception->getCode();
        $errortext = $exception->getMessage();
        
        ilLoggerFactory::getLogger('xmh')->debug($errorcode . " " . $errortext);
        
        switch ($errorcode) {
            case 404:
                header("HTTP/1.0 404 Not Found");
                break;
            case 400:
                header("HTTP/1.0 400 Bad Request");
                break;
            case 403:
            default:
                header("HTTP/1.0 403 Forbidden");
                break;
        }
        echo $errortext;
        exit();
    }
}
