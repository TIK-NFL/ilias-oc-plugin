<?php
namespace TIK_NFL\ilias_oc_plugin;

use TIK_NFL\ilias_oc_plugin\opencast\ilOpencastAPI;
use ilPlugin;

/**
 * Configuration manager, stores and loads the configuration
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastConfig
{

    const CONFIG_KEY_OPENCAST_SERVER = "oc_server";

    const CONFIG_KEY_OPENCAST_API_USER = "oc_api_user";

    const CONFIG_KEY_OPENCAST_API_PASSWORD = "oc_api_password";

    const CONFIG_KEY_UPLOAD_WORKFLOW = "uploadworkflow";

    const CONFIG_KEY_TRIM_WORKFLOW = "trimworkflow";

    const CONFIG_KEY_PUBLISHER = "publisher";

    const DATABASE_TABLE_CONFIG = "rep_robj_xmh_config";

    const DATABASE_TABLE_VIEWS = "rep_robj_xmh_views";

    const DATABASE_TABLE_DATA = "rep_robj_xmh_data";

    const DATABASE_TABLE_RELEASED_EPISODES = "rep_robj_xmh_rel_ep";

    const DATABASE_TABLE_SLIDETEXT = "rep_robj_xmh_slidetext";

    /**
     * returns the hostname for the Opencast server.
     *
     * @return string the hostname for the Opencast server
     */
    public function getOpencastServer()
    {
        $retVal = $this->getValue(self::CONFIG_KEY_OPENCAST_SERVER);
        if (! $retVal) {
            return 'http://host.is.unset';
        }

        return $retVal;
    }

    public function setOpencastServer($a_server)
    {
        $this->setValue(self::CONFIG_KEY_OPENCAST_SERVER, $a_server);
    }

    public function getOpencastAPIUser()
    {
        $retVal = $this->getValue(self::CONFIG_KEY_OPENCAST_API_USER);
        if (! $retVal) {
            return 'admin';
        }

        return $retVal;
    }

    public function setOpencastAPIUser($a_user)
    {
        $this->setValue(self::CONFIG_KEY_OPENCAST_API_USER, $a_user);
    }

    public function getOpencastAPIPassword()
    {
        $retVal = $this->getValue(self::CONFIG_KEY_OPENCAST_API_PASSWORD);
        if (! $retVal) {
            return 'opencast';
        }

        return $retVal;
    }

    public function setOpencastAPIPassword($a_password)
    {
        $this->setValue(self::CONFIG_KEY_OPENCAST_API_PASSWORD, $a_password);
    }

    public function getUploadWorkflow()
    {
        $retVal = $this->getValue(self::CONFIG_KEY_UPLOAD_WORKFLOW);
        if (! $retVal) {
            return 'default';
        }

        return $retVal;
    }

    public function setUploadWorkflow($value)
    {
        $this->setValue(self::CONFIG_KEY_UPLOAD_WORKFLOW, $value);
    }

    /**
     *
     * @return array|boolean
     */
    public function getUploadWorkflowOptions()
    {
        return $this->getAvailableWorkflows("upload");
    }

    public function getTrimWorkflow()
    {
        $retVal = $this->getValue(self::CONFIG_KEY_TRIM_WORKFLOW);
        if (! $retVal) {
            return 'default';
        }

        return $retVal;
    }

    public function setTrimWorkflow($value)
    {
        $this->setValue(self::CONFIG_KEY_TRIM_WORKFLOW, $value);
    }

    /**
     *
     * @return array|boolean
     */
    public function getTrimWorkflowOptions()
    {
        return $this->getAvailableWorkflows("editor");
    }

    /**
     *
     * @return array|boolean
     */
    private function getAvailableWorkflows(string $tag)
    {
        $plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Opencast');
        $plugin->includeClass("opencast/class.ilOpencastAPI.php");
        if (ilOpencastAPI::getInstance()->checkOpencast()) {
            $workflowDefinitions = ilOpencastAPI::getInstance()->getWorkflowDefinition($tag);
            $options = array();
            foreach ($workflowDefinitions as $workflowDefinition) {
                $options[$workflowDefinition->identifier] = $workflowDefinition->title;
            }
            return $options;
        }
        return false;
    }

    /**
     * Get the Publisher used for creating new Opencast series or empty string if not specified.
     *
     * @return string the publisher
     */
    public function getPublisher()
    {
        $retVal = $this->getValue(self::CONFIG_KEY_PUBLISHER);
        if (! $retVal) {
            return '';
        }

        return $retVal;
    }

    /**
     *
     * @param string $publisher
     */
    public function setPublisher(string $publisher)
    {
        $this->setValue(self::CONFIG_KEY_PUBLISHER, $publisher);
    }

    /**
     *
     * @param string $series_id
     * @return int $obj_id
     */
    public function lookupOpencastObjectForSeries(string $series_id)
    {
        global $ilDB;
        $result = $ilDB->query('SELECT obj_id FROM ' . self::DATABASE_TABLE_DATA . ' WHERE series_id = ' . $ilDB->quote($series_id, 'text'));
        if ($result->numRows() == 0) {
            return false;
        }
        $record = $ilDB->fetchAssoc($result);

        return intval($record['obj_id']);
    }

    /**
     *
     * @param int $obj_id
     * @return string $series_id
     */
    public function lookupSeriesForOpencastObject($obj_id)
    {
        global $ilDB;
        $result = $ilDB->query('SELECT series_id FROM ' . self::DATABASE_TABLE_DATA . ' WHERE obj_id = ' . $ilDB->quote($obj_id, 'integer'));
        if ($result->numRows() == 0) {
            return false;
        }
        $record = $ilDB->fetchAssoc($result);

        return $record['series_id'];
    }

    /**
     *
     * @param string $key
     * @param string $value
     */
    private function setValue(string $key, string $value)
    {
        global $ilDB;

        if (! is_string($this->getValue($key))) {
            $ilDB->insert(self::DATABASE_TABLE_CONFIG, array(
                'cfgkey' => array(
                    'text',
                    $key
                ),
                'cfgvalue' => array(
                    'text',
                    $value
                )
            ));
        } else {
            $ilDB->update(self::DATABASE_TABLE_CONFIG, array(
                'cfgkey' => array(
                    'text',
                    $key
                ),
                'cfgvalue' => array(
                    'text',
                    $value
                )
            ), array(
                'cfgkey' => array(
                    'text',
                    $key
                )
            ));
        }
    }

    /**
     *
     * @param string $key
     *
     * @return bool|string
     */
    private function getValue(string $key)
    {
        global $ilDB;
        $result = $ilDB->query('SELECT cfgvalue FROM ' . self::DATABASE_TABLE_CONFIG . ' WHERE cfgkey = ' . $ilDB->quote($key, 'text'));
        if ($result->numRows() == 0) {
            return false;
        }
        $record = $ilDB->fetchAssoc($result);

        return (string) $record['cfgvalue'];
    }
}
