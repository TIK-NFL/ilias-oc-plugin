<?php

/**
 * 
 * @group needInstalledILIAS
 */
use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;

class ilOpencastConfigTest extends PHPUnit_Framework_TestCase
{

    protected $backupGlobals = FALSE;

    /**
     * test config
     *
     * @var ilOpencastConfig
     */
    private $config;

    protected function setUp()
    {
        include_once ("./Services/PHPUnit/classes/class.ilUnitUtil.php");
        ilUnitUtil::performInitialisation();
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast');
        $plugin->includeClass("class.ilOpencastConfig.php");
        $this->config = new ilOpencastConfig();
    }

    public function testSetOpencastServer()
    {
        $testValue = "https://sub123.example.com/some/path/";
        $this->config->setOpencastServer($testValue);
        $actual = $this->config->getOpencastServer();
        $this->assertEquals($testValue, $actual);
    }
}