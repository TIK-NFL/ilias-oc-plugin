<?php

/**
 * 
 * @group needInstalledILIAS
 */
class ilMatterhornUserTrackingTest extends PHPUnit_Framework_TestCase
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
     * @var int
     */
    private $series_id = 1234;

    /**
     * test episode
     *
     * @var ilMatterhornEpisode
     */
    private $episode;

    protected function setUp()
    {
        include_once ("./Services/PHPUnit/classes/class.ilUnitUtil.php");
        ilUnitUtil::performInitialisation();
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Matterhorn');
        $plugin->includeClass("class.ilMatterhornEpisode.php");
        
        $this->episode = $this->getMockBuilder(ilMatterhornEpisode::class)
            ->setConstructorArgs([
            $this->series_id,
            $this->episode_id
        ])
            ->setMethods([
            'getManifest'
        ])
            ->getMock();
        $this->episode->method('getManifest')->willReturn(new SimpleXMLElement($plugin->getDirectory() . '/test/TestManifest.xml', null, true));
    }

    public function testGetSeriesId()
    {
        $series_id = $this->episode->getSeriesId();
        $this->assertEquals($this->series_id, $series_id);
    }

    public function testGetQuoteSeriesId()
    {
        $series_id = $this->episode->getQuoteSeriesId();
        $this->assertEquals("" . $this->series_id, $series_id);
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

    public function testGetManifest()
    {}

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
        
        $query = $ilDB->query("SELECT episode_id, series_id FROM rep_robj_xmh_rel_ep WHERE episode_id = " . $this->episode->getQuoteEpisodeId() . " AND series_id = " . $this->episode->getQuoteSeriesId());
        $this->assertEquals(1, $ilDB->numRows($query));
    }

    public function testRetract()
    {
        global $ilDB;
        
        $this->episode->publish();
        
        $this->episode->retract();
        
        $query = $ilDB->query("SELECT episode_id, series_id FROM rep_robj_xmh_rel_ep WHERE episode_id = " . $this->episode->getQuoteEpisodeId() . " AND series_id = " . $this->episode->getQuoteSeriesId());
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