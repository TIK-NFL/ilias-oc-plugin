<?php
namespace TIK_NFL\ilias_oc_plugin\api;

require_once __DIR__ . '/../../vendor/autoload.php';

use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;
use TIK_NFL\ilias_oc_plugin\model\ilOpencastEpisode;
use Exception;
use ilLoggerFactory;
use ilObjOpencastAccess;
use ilOpencastPlugin;
use TIK_NFL\ilias_oc_plugin\opencast\ilDeliveryUrlTrait;

/**
 * simple REST API for the video player
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 * @author Per Pascal Seeland <pascal.seeland@tik.uni-stuttgart.de>
 */
class ilAPIController
{

    use ilDeliveryUrlTrait;

    /**
     *
     * @var ilOpencastPlugin
     */
    private ilOpencastPlugin $plugin;

    /**
     * the configuration for the Opencast plugin
     *
     * @var ilOpencastConfig
     */
    private ilOpencastConfig $configObject;

    /**
     * Constructor
     *
     * @param mixed $uri
     *            the parsed REQUEST_URI
     * @param string $method
     *            the REQUEST_METHOD
     * @access public
     */
    public function __construct($uri, string $method)
    {
        $this->params = array();

        if ($method === 'GET') {
            if(isset($uri["query"])) {
                parse_str($uri["query"], $this->params);
            }
        } elseif ($method === 'PUT') {
            parse_str(file_get_contents("php://input"), $this->params);
        }
        $this->configObject = new ilOpencastConfig();
    }

    /**
     * Main function for handle Requests
     *
     * @param string $path
     *            the path of the request
     */
    public function handleRequest(string $path): void
    {
        try {
            // check if it is a request for an episode
            if (0 === strcmp("/episode.json", $path)) {
                $epsiode = $this->getEpisodeFromParameter();
                ilObjOpencastAccess::checkEpisodeAccess($epsiode);
                $this->sendEpisode($epsiode);
            } else if (0 === strcmp("/usertracking", $path)) {
                $epsiode = $this->getEpisodeFromParameter();
                if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    switch ($this->params['type']) {
                        case "FOOTPRINT":
                            ilObjOpencastAccess::checkEpisodeAccess($epsiode);
                            $this->putUserTracking($epsiode);
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
            } else if (0 === strcmp("/usertracking/stats.json", $path)) {
                $epsiode = $this->getEpisodeFromParameter();
                ilObjOpencastAccess::checkEpisodeAccess($epsiode);
                $this->sendStats($epsiode);
            } else if (0 === strcmp("/usertracking/footprint.json", $path)) {
                $epsiode = $this->getEpisodeFromParameter();
                ilObjOpencastAccess::checkEpisodeAccess($epsiode);
                $this->sendFootprint($epsiode);
            } else if (0 === strcmp("/usertracking/statistic.json", $path)) {
                $epsiode = $this->getEpisodeFromParameter();
                ilObjOpencastAccess::checkEpisodeAccess($epsiode, "write");
                $this->sendStatistic($epsiode);
            } else if (0 === strcmp("/info/me.json", $path)) {
                $this->sendMe();
            } else if (0 === strcmp("/manager/list.json", $path)) {
                $this->sendList();
            } else {
                throw new Exception("Bad Request", 400);
            }
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * extract series_id and episode_id from the request param
     */
    private function getEpisodeFromParameter(): ilOpencastEpisode
    {
        $ids = explode('/', $this->params['id'], 2);
        $series_id = $ids[0];
        $episode_id = $ids[1];
        return $this->getEpisode($series_id, $episode_id);
    }

    /**
     * get the epsiode from the series_id and the episode_id
     *
     * @param string $series_id
     * @param string $episode_id
     * @return ilOpencastEpisode
     */
    private function getEpisode(string $series_id, string $episode_id): ilOpencastEpisode
    {
        return new ilOpencastEpisode($series_id, $episode_id);
    }

    /**
     * Send the requested episode.json
     */
    private function sendEpisode(ilOpencastEpisode $episodeObject): void
    {
        $episodeData = $episodeObject->getEpisode();
        $publication = $episodeObject->getPublication();

        $attachments = array(
            "attachment" => array()
        );
        $previewrefs = [];
        foreach ($publication->attachments as $attachment) {
            $att = array(
                'id' => $attachment->id,
                'type' => $attachment->flavor,
                'ref' => $attachment->ref,
                'mimetype' => $attachment->mediatype,
                'url' => $this->getDeliveryUrl($attachment->url),
                'tags' => array(
                    'tag' => $attachment->tags
                )
            );
            if ($att['type'] === "presentation/segment+preview") {
                preg_match("/(.*)time=(.*)F(\d+)/", $att['ref'], $regmatches);
                $previewrefs[$regmatches[2]] = $att;
            }
            $attachments['attachment'][] = $att;
        }

        $metadata = array(
            "catalog" => array()
        );
        $segmentsUrl = null;
        foreach ($publication->metadata as $catalog) {
            $cat = array(
                'id' => $catalog->id,
                'type' => $catalog->flavor,
                'mimetype' => $catalog->mediatype,
                'url' => $this->getDeliveryUrl($catalog->url),
                'tags' => array(
                    'tag' => $catalog->tags
                )
            );

            if (0 === strcmp($cat['type'], 'mpeg-7/segments')) {
                $segmentsUrl = $this->getDeliveryUrl($catalog->url);
            }
            if (0 === strcmp($cat['type'], 'mpeg-7/text')) {
                $segmentsUrl = $this->getDeliveryUrl($catalog->url);
            }
            $metadata['catalog'][] = $cat;
        }

        $media = array(
            "track" => array()
        );
        foreach ($publication->media as $track) {
            $trk = array(
                'id' => $track->id,
                'type' => $track->flavor,
                'mimetype' => $track->mediatype,
                'url' => $this->getDeliveryUrl($track->url),
                'duration' => $track->duration,
                'tags' => array(
                    'tag' => $track->tags
                )
            );
            if ($track->flavor === 'composite/sbs') {
                continue;
            }
            if ($track->has_video) {
                $trk['video'] = array();
                $trk['video']['id'] = "video-1";
                $trk['video']['resolution'] = $track->width . "x" . $track->height;
            }
            if ($track->has_audio) {
                $trk['audio'] = array();
                $trk['audio']['id'] = "audio-1";
            }
            $media['track'][] = $trk;
        }

        usort($media['track'], function ($a, $b) {
            // reverse order so presenter is before presentation
            return (- 1 * strcmp($a['type'], $b['type']));
        });

        if ($segmentsUrl) {
            $segments = $this->convertSegment($segmentsUrl, $previewrefs);
        }

        $episode = array(
            'search-results' => array(
                "total" => "1",
                "result" => array(
                    'id' => $episodeData->identifier,
                    'mediaType' => "AudioVisual",
                    "dcCreated" => $episodeData->start,
                    "dcExtent" => 0, // TODO
                    "dcTitle" => $episodeData->title,
                    "dcIsPartOf" => $episodeData->is_part_of,
                    "dcCreator" => $episodeData->presenter,
                    "mediapackage" => array(
                        'attachments' => $attachments,
                        'metadata' => $metadata, // TODO reqired?
                        'media' => $media,
                        'duration' => "0", // TODO
                        'id' => $episodeObject->getEpisodeId()
                    ),
                    "segments" => $segments
                )
            )
        );

        $this->sendJSON($episode);
    }

    /**
     *
     * @param string $url
     * @param array $previewrefs
     * @return array[]
     */
    private function convertSegment(string $url, array $previewrefs): array
    {
        $segmentsxml = new \SimpleXMLElement($url, null, true);

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
            // TODO: check unused variable
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
            $segments['segment'][] = $segment;
        }
        return $segments;
    }

    /**
     * stores the usertracking data in the database
     *
     * @param ilOpencastEpisode $episode
     */
    private function putUserTracking(ilOpencastEpisode $episode): void
    {
        global $ilUser;
        $intime = (int)$this->params['in'];
        $outtime = (int)$this->params['out'];
        $user_id = $ilUser->getId();

        ilOpencastUserTracking::putUserTracking($user_id, $episode, $intime, $outtime);

        header("HTTP/1.0 204 Stored");
    }

    /**
     * send the Statistic overview for the episode as json.
     */
    private function sendStatistic(ilOpencastEpisode $episode): void
    {
        $statistic = ilOpencastUserTracking::getStatisticFromVideo($episode);
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

        $episodeData = $episode->getEpisode();

        $infoarray = [];
        $infoarray['name'] = $episodeData->title;
        $infoarray['episode_id'] = $episode->getEpisodeId();
        $infoarray['series_id'] = $episode->getSeriesId();
        $infoarray['duration'] = (string)$episode->getMedia()[0]->duration;
        $infoarray['data'] = $data;

        $this->sendJSON($infoarray);
    }

    /**
     * send Footprints for the user
     *
     * @param ilOpencastEpisode $episode
     */
    private function sendFootprint(ilOpencastEpisode $episode)
    {
        global $ilUser;
        $user_id = $ilUser->getId();

        $response = array();
        $response['footprints'] = ilOpencastUserTracking::getFootprints($episode, $user_id);
        $response['last'] = ilOpencastUserTracking::getLastSecondViewed($episode, $user_id);
        $this->sendJSON($response);
    }

    /**
     * send Statistics like views
     *
     * @param ilOpencastEpisode $episode
     */
    private function sendStats(ilOpencastEpisode $episode): void
    {
        $response = array();
        $views = ilOpencastUserTracking::getViews($episode);
        $response['stats'] = [
            'views' => $views
        ];
        $this->sendJSON($response);
    }

    /**
     * send the info/me.json
     */
    private function sendMe(): void
    {
        $info = new ilOpencastInfo();
        $response = $info->getMyInfo();
        $this->sendJSON($response);
    }

    /**
     * send the manager/list.json
     */
    private function sendList(): void
    {
        $info = new ilOpencastInfo();
        $response = $info->listPlugins();
        $this->sendJSON($response);
    }

    /**
     * Send the Array as json
     *
     * @param array $array
     *            the array
     */
    private function sendJSON(array $array): void
    {
        header("Content-Type: application/json");
        echo json_encode($array);
    }

    /**
     * Send an error response for the requested file
     *
     * @param Exception $exception
     */
    public function sendError(Exception $exception): void
    {
        $errorcode = $exception->getCode();
        $errortext = $exception->getMessage();

        ilLoggerFactory::getLogger('xmh')->debug($errorcode . " " . $errortext);

        http_response_code($errorcode);
        echo $errortext;
        exit();
    }
}
