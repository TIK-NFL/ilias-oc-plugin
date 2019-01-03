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

    /**
     *
     * @var ilMatterhornPlugin
     * @access plublic
     */
    public $plugin;

    /**
     * the matterhorn episode
     *
     * @var ilMatterhornEpisode
     */
    private $episode;

    /**
     * Stores the request type.
     *
     * @var string
     * @access private
     * @deprecated
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
        $this->params = array();
        $this->requestType = "none";
        $this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn');

        if ($method == 'GET') {
            parse_str($uri["query"], $this->params);
        } elseif ($method == 'PUT') {
            parse_str(file_get_contents("php://input"), $this->params);
        }

        $this->plugin->includeClass("class.ilMatterhornConfig.php");
        $this->plugin->includeClass("class.ilObjMatterhornAccess.php");
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
        // echo "disposition: " . $this->disposition . "\n";
        // echo "ckeck_ip: " . $this->check_ip . "\n";
        // echo "</pre>";
        // var_dump($_SESSION);
        // exit();
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
                $this->setIDFromParameter();
                ilObjMatterhornAccess::checkEpisodeAccess($this->episode);
                $this->sendEpisode();
            } else if (0 == strcmp("/usertracking", $path)) {
                $this->requestType = "usertracking";
                $this->setIDFromParameter();
                if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    switch ($this->params['type']) {
                        case "FOOTPRINT":
                            ilObjMatterhornAccess::checkEpisodeAccess($this->episode);
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
                $this->setIDFromParameter();
                ilObjMatterhornAccess::checkEpisodeAccess($this->episode);
                $this->sendStats();
            } else if (0 == strcmp("/usertracking/footprint.json", $path)) {
                $this->requestType = "footprint";
                $this->setIDFromParameter();
                ilObjMatterhornAccess::checkEpisodeAccess($this->episode);
                $this->sendFootprint();
            } else if (0 == strcmp("/usertracking/statistic.json", $path)) {
                $this->requestType = "statistic";
                $this->setIDFromParameter();
                ilObjMatterhornAccess::checkEpisodeAccess($this->episode, "write");
                $this->sendStatistic();
            } else if (0 == strcmp("/info/me.json", $path)) {
                $this->requestType = "me";
                $this->sendMe();
            } else if (0 == strcmp("/manager/list.json", $path)) {
                $this->requestType = "list";
                $this->sendList();
            } else {
                $pathSegments = array_slice(array_map('urldecode', explode('/', $path)), 1);

                if (! isset($pathSegments[0]) || $pathSegments[0] != CLIENT_ID) {
                    throw new Exception("Bad CLIENT_ID", 400);
                }

                if (! isset($pathSegments[1]) || ! isset($pathSegments[2])) {
                    throw new Exception("Bad Request", 400);
                }
                $this->setID($pathSegments[1], $pathSegments[2]);

                if (isset($pathSegments[3]) && preg_match('/^preview(sbs|presentation|presenter).(mp4|webm)$/', $pathSegments[3])) {
                    $this->requestType = "preview";
                    ilObjMatterhornAccess::checkPreviewAccess($this->episode);
                    $this->sendPreview($pathSegments[3]);
                } else {
                    $this->requestType = "file";
                    ilObjMatterhornAccess::checkFileAccess($this->episode);
                    $this->sendFile('distribution_directory', array_slice($pathSegments, 1));
                }
            }
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * extract series_id and episode_id from the request param
     *
     * @access private
     */
    private function setIDFromParameter()
    {
        $ids = explode('/', $this->params['id'], 2);
        $series_id = $ids[0];
        $episode_id = $ids[1];
        $this->setID($series_id, $episode_id);
    }

    /**
     * set the series_id and the episode_id
     *
     * @param string $series_id
     * @param string $episode_id
     *
     * @access private
     */
    private function setID(string $series_id, string $episode_id)
    {
        $this->plugin->includeClass("class.ilMatterhornEpisode.php");
        $this->episode = new ilMatterhornEpisode($series_id, $episode_id);
    }

    /**
     * Returns the type of request
     *
     * @return string the request type of this request
     * @access public
     * @deprecated
     */
    public function getRequestType()
    {
        return $this->requestType;
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
        ilMatterhornUserTracking::putUserTracking($user_id, $this->episode, $intime, $outtime);

        header("HTTP/1.0 204 Stored");
    }

    /**
     * send the Statistic overview for the episode as json.
     */
    private function sendStatistic()
    {
        $this->plugin->includeClass("class.ilMatterhornUserTracking.php");
        $statistic = ilMatterhornUserTracking::getStatisticFromVideo($this->episode);
        $data = array();
        foreach ($statistic as $name => $value) {
            $content = array();
            $content['name'] = $name;

            $content['type'] = "mapping";
            $content['key'] = "time";
            $content['value'] = "views";
            $content['step'] = 10;
            $arrayKeys = array_keys($value);
            $max = count($arrayKeys) == 0 ? 0 : max($arrayKeys);
            $mapping = array_fill(0, $max, 0);
            $content['mapping'] = array_replace($mapping, $value);

            $data[] = $content;
        }

        $infoarray = array();
        $infoarray['name'] = $this->episode->getTitle();
        $infoarray['episode_id'] = $this->episode->getEpisodeId();
        $infoarray['series_id'] = $this->episode->getSeriesId();
        $infoarray['duration'] = $this->episode->getDuration();
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
        $response['footprints'] = ilMatterhornUserTracking::getFootprints($this->episode, $user_id);
        $response['last'] = ilMatterhornUserTracking::getLastSecondViewed($this->episode, $user_id);
        $this->sendJSON($response);
    }

    /**
     * send Statistics like views
     */
    private function sendStats()
    {
        $response = array();
        $this->plugin->includeClass("class.ilMatterhornUserTracking.php");
        $views = ilMatterhornUserTracking::getViews($this->episode);
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
     */
    private function sendEpisode()
    {
        $manifest = $this->episode->getManifest();

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
        $episode['search-results']["result"]["dcIsPartOf"] = $this->episode->getSeriesId();
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
        $this->sendJSON($episode);
    }

    private function convertSegment($catalog, $previewrefs)
    {
        $urlsplit = explode('/', (string) $catalog->url);
        end($urlsplit);
        $segmentsxml = new SimpleXMLElement($this->configObject->getDistributionDirectory() . $this->episode->getSeriesId() . '/' . $this->episode->getEpisodeId() . '/' . prev($urlsplit) . '/' . end($urlsplit), null, true);

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
     *
     * @param string $filename
     *            file name from ilias directory (without leading /)
     */
    private function sendPreview(string $filename)
    {
        $typesplit = explode('.', $filename);
        $requestedType = $typesplit[1];
        ilLoggerFactory::getLogger('xmh')->debug('mhpreviewurl requested type:' . $requestedType);
        $editor = $this->episode->getEditor();
        $previewtrack = null;
        foreach ($editor->previews as $preview) {
            if (strpos($preview->uri, $requestedType)) {
                $previewtrack = $preview->uri;
            }
        }
        if ($previewtrack == null) {
            throw new Exception("No Preview", 404);
        }
        $path = parse_url($previewtrack, PHP_URL_PATH);

        $relativeFilePath = str_replace('/static/mh_default_org/internal/', 'downloads/mh_default_org/internal/', $path);
        $this->sendFile('mh_directory', explode('/', $relativeFilePath));
    }

    /**
     * Send the requested file as if directly delivered from the web server.
     *
     * @param string $directoryName
     *            the config name of the directory:
     *            * `distribution_directory`
     *            * `mh_directory`
     * @param array $pathSegments
     *            relative file path
     */
    private function sendFile(string $directoryName, array $pathSegments)
    {
        $relativeFilePath = implode('/', $pathSegments);

        switch ($directoryName) {
            case 'distribution_directory':
                $realFile = $this->configObject->getDistributionDirectory() . "/$relativeFilePath";
                $xAccelAlias = "/__ilias_xmh_distribution_directory__/";
                break;
            case 'mh_directory':
                $realFile = $this->configObject->getMatterhornDirectory() . "/$relativeFilePath";
                $xAccelAlias = "/__ilias_xmh_mh_directory__/";
                break;
            default:
                new Exception("Directory name '$directoryName' is unknow.", 500);
        }
        include_once ("./Services/Utilities/classes/class.ilMimeTypeUtil.php");
        $mime = ilMimeTypeUtil::lookupMimeType($realFile);
        header("Content-Type: " . $mime);

        switch ($this->configObject->getXSendfileHeader()) {
            case ilMatterhornConfig::X_SENDFILE:
                $header = "X-Sendfile: " . $realFile;
                break;
            case ilMatterhornConfig::X_ACCEL_REDIRECT:
                $header = "X-Accel-Redirect: " . $xAccelAlias . $relativeFilePath;
        }
        ilLoggerFactory::getLogger('xmh')->debug("Header: $header");
        header($header);
    }

    /**
     * Send an error response for the requested file
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
