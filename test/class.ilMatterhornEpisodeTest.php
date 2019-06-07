<?php

/**
 * 
 * @group needInstalledILIAS
 */
class ilOpencastEpisodeTest extends PHPUnit_Framework_TestCase
{

    protected $backupGlobals = FALSE;

    /**
     * test episode
     *
     * @var string
     */
    private $episode_id = "28b408e5-da79-4737-ba1c-5567d76a3cb6";

    /**
     * test episode series id
     *
     * @var string
     */
    private $series_id = "8b8966ac-e5e4-11e8-9f32-f2801f1b9fd1";

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
        $plugin->includeClass("class.ilOpencastEpisode.php");
        
        $this->episode = $this->getMockBuilder(ilOpencastEpisode::class)
            ->setConstructorArgs([
            $this->series_id,
            $this->episode_id
        ])
            ->setMethods([
            'getEpisode'
        ])
            ->getMock();
        $this->episode->method('getEpisode')->willReturn();//TODO
    }

    public function testGetSeriesId()
    {
        $series_id = $this->episode->getSeriesId();
        $this->assertEquals($this->series_id, $series_id);
    }

    public function testGetQuoteSeriesId()
    {
        $series_id = $this->episode->getQuoteSeriesId();
        $this->assertEquals("'" . $this->series_id . "'", $series_id);
    }

    public function testGetEpisodeId()
    {
        $episode_id = $this->episode->getEpisodeId();
        $this->assertEquals($this->episode_id, $episode_id);
    }

    public function testGetQuoteEpisodeId()
    {
        $episode_id = $this->episode->getQuoteEpisodeId();
        $this->assertEquals("'" . $this->episode_id . "'", $episode_id);
    }

    public function testGetDuration()
    {
        $duration = $this->episode->getDuration();
        $this->assertEquals("40032", $duration);
    }

    public function testGetTitle()
    {
        $title = $this->episode->getTitle();
        $this->assertEquals("test video", $title);
    }

    public function testPublish()
    {
        global $ilDB;
        
        $this->episode->publish();
        
        $query = $ilDB->query("SELECT episode_id, series_id FROM rep_robj_xoc_rel_ep WHERE episode_id = " . $this->episode->getQuoteEpisodeId() . " AND series_id = " . $this->episode->getQuoteSeriesId());
        $this->assertEquals(1, $ilDB->numRows($query));
    }

    public function testRetract()
    {
        global $ilDB;
        
        $this->episode->publish();
        
        $this->episode->retract();
        
        $query = $ilDB->query("SELECT episode_id, series_id FROM rep_robj_xoc_rel_ep WHERE episode_id = " . $this->episode->getQuoteEpisodeId() . " AND series_id = " . $this->episode->getQuoteSeriesId());
        $this->assertEquals(0, $ilDB->numRows($query));
    }

    /**
     * @after
     */
    public function restoreDataBase()
    {
        $this->episode->retract();
    }
}