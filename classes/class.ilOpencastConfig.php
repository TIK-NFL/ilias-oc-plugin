<?php
namespace TIK_NFL\ilias_oc_plugin;

use TIK_NFL\ilias_oc_plugin\opencast\ilOpencastAPI;

/**
 * Configuration manager, stores and loads the configuration
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastConfig
{

    public const CONFIG_KEY_OPENCAST_SERVER = "oc_server";

    public const CONFIG_KEY_OPENCAST_API_USER = "oc_api_user";

    public const CONFIG_KEY_OPENCAST_API_PASSWORD = "oc_api_password";

    public const CONFIG_KEY_UPLOAD_WORKFLOW = "uploadworkflow";

    public const CONFIG_KEY_TRIM_WORKFLOW = "trimworkflow";

    public const CONFIG_KEY_PUBLISHER = "publisher";

    public const CONFIG_KEY_URL_SIGNING_KEY = "urlsigningkey";

    public const CONFIG_KEY_DELIVERY_METHOD= "delivery_method";

    public const CONFIG_KEY_DISTRIBUTION_SERVER = "distributionserver";

    public const CONFIG_KEY_TOKEN_VALIDITY = "tokenvalidity";

    public const CONFIG_KEY_SHOW_QRCODE = "show_qrcode";

    public const CONFIG_KEY_SERIES_SIGNING_KEY = "series_signing_key";

    public const CONFIG_KEY_STRIP_URL = "stripurl";

    public const DATABASE_TABLE_CONFIG = "rep_robj_xmh_config";

    public const DATABASE_TABLE_VIEWS = "rep_robj_xmh_views";

    public const DATABASE_TABLE_DATA = "rep_robj_xmh_data";

    public const DATABASE_TABLE_RELEASED_EPISODES = "rep_robj_xmh_rel_ep";

    public const DATABASE_TABLE_SLIDETEXT = "rep_robj_xmh_slidetext";

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

    public function setOpencastServer($a_server): void
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

    public function setOpencastAPIUser($a_user): void
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

    public function setOpencastAPIPassword($a_password): void
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

    public function setUploadWorkflow($value): void
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

    public function setTrimWorkflow($value): void
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

    public function getDeliveryMethod():string
    {
        $retVal = $this->getValue(self::CONFIG_KEY_DELIVERY_METHOD);
        if (! $retVal) {
            return "api";
        }
        return $retVal;
    }

    public function setDeliveryMethod(string $value): void
    {
        $this->setValue(self::CONFIG_KEY_DELIVERY_METHOD, $value);
    }

    public function getUrlSigningKey()
    {
        $retVal = $this->getValue(self::CONFIG_KEY_URL_SIGNING_KEY);
        if (! $retVal) {
            return 'default';
        }
        return $retVal;
    }

    public function setUrlSigningKey($value): void
    {
        $this->setValue(self::CONFIG_KEY_URL_SIGNING_KEY, $value);
    }

    public function getDistributionServer()
    {
        $retVal = $this->getValue(self::CONFIG_KEY_DISTRIBUTION_SERVER);
        if (! $retVal) {
            return 'http://unknown.host';
        }
        return $retVal;
    }

    public function setDistributionServer($value): void
    {
        $this->setValue(self::CONFIG_KEY_DISTRIBUTION_SERVER, $value);
    }

    public function getTokenValidity(): int
    {
        $retVal = $this->getValue(self::CONFIG_KEY_TOKEN_VALIDITY);
        if (! $retVal) {
            return 6;
        }
        return (int)$retVal;
    }

    public function setTokenValidity(int $value): void
    {
        $this->setValue(self::CONFIG_KEY_TOKEN_VALIDITY, $value);
    }

    public function getStripUrl()    {
        $retVal = $this->getValue(self::CONFIG_KEY_STRIP_URL);
        if (! $retVal) {
            return 'http://unknown.host';
        }
        return $retVal;
    }

    public function setStripUrl(string $value): void
    {
        $this->setValue(self::CONFIG_KEY_STRIP_URL, $value);
    }

    public function getSeriesSigningKey(): string
    {
        $retVal = $this->getValue(self::CONFIG_KEY_SERIES_SIGNING_KEY);
        if (! $retVal) {
            return 'default';
        }
        return $retVal;
    }

    public function setSeriesSigningKey(string $value): void
    {
        $this->setValue(self::CONFIG_KEY_SERIES_SIGNING_KEY, $value);
    }

    public function getShowQRCode():bool
    {
        $retVal = $this->getValue(self::CONFIG_KEY_SHOW_QRCODE);
        if (! $retVal) {
            return false;
        }
        return (bool)$retVal;
    }

    public function setShowQRCode(bool $value): void
    {
        $this->setValue(self::CONFIG_KEY_SHOW_QRCODE, $value);
    }

    /**
     *
     * @return array|boolean
     */
    private function getAvailableWorkflows(string $tag)
    {
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
    public function setPublisher(string $publisher): void
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
        if ($result->numRows() === 0) {
            return false;
        }
        $record = $ilDB->fetchAssoc($result);

        return (int)$record['obj_id'];
    }

    /**
     *
     * @return string $series_id
     */
    public function lookupSeriesForOpencastObject(int $obj_id) : string
    {
        global $ilDB;
        $result = $ilDB->query('SELECT series_id FROM ' . self::DATABASE_TABLE_DATA . ' WHERE obj_id = ' . $ilDB->quote($obj_id, 'integer'));
        if ($result->numRows() === 0) {
            return false;
        }
        $record = $ilDB->fetchAssoc($result);

        return $record['series_id'];
    }

    private function setValue(string $key, string $value): void
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
        if ($result->numRows() === 0) {
            return false;
        }
        $record = $ilDB->fetchAssoc($result);

        return (string) $record['cfgvalue'];
    }
}
