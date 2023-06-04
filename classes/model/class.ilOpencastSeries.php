<?php
namespace TIK_NFL\ilias_oc_plugin\model;

use TIK_NFL\ilias_oc_plugin\opencast\ilOpencastAPI;
use DateTime;
use stdClass;

/**
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastSeries
{

    /**
     * The Opencast series id, not the ilias object id
     *
     * @var string
     */
    private $series_id;

    /**
     *
     * @param string $series_id
     */
    public function __construct($series_id)
    {
        $this->series_id = $series_id;
    }

    /**
     *
     * @return string
     */
    public function getSeriesId()
    {
        return $this->series_id;
    }

    /**
     *
     * @return string
     */
    public function getQuoteSeriesId()
    {
        global $ilDB;
        return $ilDB->quote($this->getSeriesId(), "text");
    }

    /**
     *
     * @return array
     */
    public function getSeriesInformationFromOpencast()
    {
        $series = ilOpencastAPI::getInstance()->getSeries($this->getSeriesId());
        $series = array(
            "title" => (string) $series->title,
            "description" => (string) $series->description,
            "publishers" => (array) $series->publishers,
            "identifier" => (string) $series->identifier
        );
        return $series;
    }

    /**
     * The scheduled information for this series returned by opencast
     *
     * @return array the scheduled episodes for this series returned by opencast
     */
    public function getScheduledEpisodes()
    {
        return ilOpencastAPI::getInstance()->getScheduledEpisodes($this->getSeriesId());
    }

    /**
     * Get the episodes which are on hold for this series
     *
     * @return array the episodes
     */
    public function getOnHoldEpisodes()
    {
        return ilOpencastAPI::getInstance()->getOnHoldEpisodes($this->getSeriesId());
    }

    /**
     * Get the episodes which are ready to be published in ilias for this series
     *
     * @return array the episodes
     */
    public function getReadyEpisodes()
    {
        return ilOpencastAPI::getInstance()->getReadyEpisodes($this->getSeriesId());
    }

    /**
     * Get the episodes which are on hold for this series
     *
     * @return array the episodes which are on hold for this series returned by opencast
     */
    public function getProcessingEpisodes()
    {
        $workflows = ilOpencastAPI::getInstance()->getActiveWorkflows($this->getSeriesId());

        return array_map(array(
            $this,
            'extractProcessingEpisode'
        ), $workflows);
    }

    private function extractProcessingEpisode(stdClass $episode)
    {
//        $operations = array();
//        foreach ($workflow->operations as $operation) {
//            // search for trim. If it will run, count only up to here if it is not finished yet, otherwise count from here
//            if ($operation->operation === "trim" && $operation->if === "true") {
//                if ($operation->state === "succeeded") {
//                    $operations = array();
//                } else {
//                    break;
//                }
//            } else {
//                $operations[] = $operation;
//            }
//        }
//
//        $totalops = count($operations);
//        $finished = 0;
//        $running = "Waiting";
//        foreach ($operations as $operation) {
//            $state = (string) $operation->state;
//            if ($state == "skipped" || $state == "succeeded") {
//                $finished ++;
//            }
//
//            if ($state == "running") {
//                $running = $operation->description;
//            }
//        }
        //$episode = ilOpencastAPI::getInstance()->getEpisode($workflow->event_identifier);

        return array(
            'title' => $episode->title,
            //'workflow_id' => $workflow->operation,
            'startdate' => $episode->start);


//            'processdone' =>0//($finished / $totalops) * 100,
//            'processcount' => $finished . "/" . $totalops,
//            'running' => $running
//        );
    }

    /**
     *
     * @see ilOpencastAPI#createEpisode()
     */
    public function createEpisode(string $title, string $creator, DateTime $startDate, bool $flagForCutting, ?string $presentationFilePath, ?string $presenterFilePath)
    {
        ilOpencastAPI::getInstance()->createEpisode($title, $creator, $startDate, $this->series_id, $flagForCutting, $presentationFilePath, $presenterFilePath);
    }
}