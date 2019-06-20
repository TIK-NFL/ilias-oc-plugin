<?php

/**
 * 
 * @group needInstalledILIAS
 */
use TIK_NFL\ilias_oc_plugin\api\ilOpencastUserTracking;
use TIK_NFL\ilias_oc_plugin\model\ilOpencastEpisode;

class ilOpencastUserTrackingTest extends PHPUnit_Framework_TestCase
{

    protected $backupGlobals = FALSE;

    /**
     * test episode
     *
     * @var string
     */
    private $episode_id = "28b408e5-da79-4737-ba1c-5567d76a3cb6";

    /**
     * test episode
     *
     * @var ilOpencastEpisode
     */
    private $episode;

    protected function setUp()
    {
        include_once ("./Services/PHPUnit/classes/class.ilUnitUtil.php");
        ilUnitUtil::performInitialisation();
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast');
        $plugin->includeClass("api/class.ilOpencastUserTracking.php");
        $plugin->includeClass("model/class.ilOpencastEpisode.php");
        $this->episode = new ilOpencastEpisode("8b8966ac-e5e4-11e8-9f32-f2801f1b9fd1", $this->episode_id);
    }

    /**
     *
     * @before
     */
    public function setupDataBase()
    {
        global $ilUser;

        $user_id = $ilUser->getId();
        $episode = $this->episode;

        ilOpencastUserTracking::putUserTracking($user_id, $episode, 0, 10);
        ilOpencastUserTracking::putUserTracking($user_id, $episode, 10, 20);
        ilOpencastUserTracking::putUserTracking($user_id, $episode, 20, 30);
        ilOpencastUserTracking::putUserTracking($user_id, $episode, 40, 50);

        ilOpencastUserTracking::putUserTracking($user_id, $episode, 0, 10);
        ilOpencastUserTracking::putUserTracking($user_id, $episode, 0, 10);
        ilOpencastUserTracking::putUserTracking($user_id, $episode, 30, 40);
    }

    public function testPutUserTracking()
    {
        global $ilUser, $ilDB;

        $user_id = $ilUser->getId();

        $query = $ilDB->query("SELECT intime, outtime FROM " . ilOpencastUserTracking::DATATABLE . " WHERE user_id = " . $ilDB->quote($user_id, "integer") . " AND episode_id LIKE " . $ilDB->quote($this->episode_id, "text"));

        $result = $ilDB->fetchAssoc($query);
        $this->assertEquals(0, $result['intime']);
        $this->assertEquals(30, $result['outtime']);

        $result = $ilDB->fetchAssoc($query);
        $this->assertEquals(40, $result['intime']);
        $this->assertEquals(50, $result['outtime']);

        $result = $ilDB->fetchAssoc($query);
        $this->assertEquals(0, $result['intime']);
        $this->assertEquals(10, $result['outtime']);

        $result = $ilDB->fetchAssoc($query);
        $this->assertEquals(0, $result['intime']);
        $this->assertEquals(10, $result['outtime']);

        $result = $ilDB->fetchAssoc($query);
        $this->assertEquals(30, $result['intime']);
        $this->assertEquals(40, $result['outtime']);
    }

    public function testGetStatisticFromVideo()
    {
        $result = ilOpencastUserTracking::getStatisticFromVideo($this->episode);

        $this->assertEquals(3, $result['views'][0]);
        $this->assertEquals(1, $result['views'][1]);
        $this->assertEquals(1, $result['views'][2]);
        $this->assertEquals(1, $result['views'][3]);
        $this->assertEquals(1, $result['views'][4]);

        $this->assertEquals(1, $result['unique_views'][0]);
        $this->assertEquals(1, $result['unique_views'][1]);
        $this->assertEquals(1, $result['unique_views'][2]);
        $this->assertEquals(1, $result['unique_views'][3]);
        $this->assertEquals(1, $result['unique_views'][4]);
    }

    public function testGetFootprints()
    {
        global $ilUser;

        $user_id = $ilUser->getId();

        $result = ilOpencastUserTracking::getFootprints($this->episode, $user_id);

        $this->assertEquals(0, $result['footprint'][0]['position']);
        $this->assertEquals(3, $result['footprint'][0]['views']);

        $this->assertEquals(10, $result['footprint'][1]['position']);
        $this->assertEquals(1, $result['footprint'][1]['views']);

        $this->assertEquals(50, $result['footprint'][2]['position']);
        $this->assertEquals(0, $result['footprint'][2]['views']);

        $this->assertEquals(3, $result['total']);
    }

    public function testGetViews()
    {
        $result = ilOpencastUserTracking::getViews($this->episode);
        $this->assertEquals(3, $result);
    }

    public function testGetLastSecondViewed()
    {
        global $ilUser;

        $user_id = $ilUser->getId();
        $result = ilOpencastUserTracking::getLastSecondViewed($this->episode, $user_id);
        $this->assertEquals(40, $result);
    }

    /**
     *
     * @after
     */
    public function restoreDataBase()
    {
        ilOpencastUserTracking::removeViews($this->episode);
    }
}