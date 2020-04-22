<?php

class ilMatterhornConfig
{

    const X_SENDFILE = 0;

    const X_ACCEL_REDIRECT = 1;

    /**
     * returns the hostname for the matterhorn server.
     *
     * @return string the hostname for the matterhorn server
     */
    public function getMatterhornServer()
    {
        $retVal = $this->getValue('mh_server');
        if (! $retVal) {
            return 'http://host.is.unset';
        }

        return $retVal;
    }

    public function setMatterhornServer($a_server)
    {
        $this->setValue('mh_server', $a_server);
    }

    public function getMatterhornEngageServer()
    {
        $retVal = $this->getValue('mh_server_engage');
        if (! $retVal) {
            return 'http://host.is.unset';
        }

        return $retVal;
    }

    public function setMatterhornEngageServer($a_server)
    {
        $this->setValue('mh_server_engage', $a_server);
    }

    public function getMatterhornUser()
    {
        $retVal = $this->getValue('mh_digest_user');
        if (! $retVal) {
            return 'matterhorn_system_account';
        }

        return $retVal;
    }

    public function setMatterhornUser($a_user)
    {
        $this->setValue('mh_digest_user', $a_user);
    }

    public function getMatterhornPassword()
    {
        $retVal = $this->getValue('mh_digest_password');
        if (! $retVal) {
            return 'CHANGE_ME';
        }

        return $retVal;
    }

    public function setMatterhornPassword($a_password)
    {
        $this->setValue('mh_digest_password', $a_password);
    }

    public function getOpencastAPIUser()
    {
        $retVal = $this->getValue('oc_api_user');
        if (! $retVal) {
            return 'admin';
        }
        
        return $retVal;
    }

    public function setOpencastAPIUser($a_user)
    {
        $this->setValue('oc_api_user', $a_user);
    }

    public function getOpencastAPIPassword()
    {
        $retVal = $this->getValue('oc_api_password');
        if (! $retVal) {
            return 'opencast';
        }
        
        return $retVal;
    }

    public function setOpencastAPIPassword($a_password)
    {
        $this->setValue('oc_api_password', $a_password);
    }

    /**
     *
     * @return string org.opencastproject.storage.dir
     */
    public function getMatterhornDirectory()
    {
        $retVal = $this->getValue('mh_directory');
        if (! $retVal) {
            return '/dev/null';
        }

        return $retVal;
    }

    public function setMatterhornDirectory($a_filesdir)
    {
        $this->setValue('mh_directory', $a_filesdir);
    }

    /**
     *
     * @return int
     */
    public function getXSendfileHeader()
    {
        $retVal = intval($this->getValue('xsendfile_header'));
        if (! $retVal) {
            return self::X_SENDFILE;
        }
        return $retVal;
    }

    public function setXSendfileHeader($value)
    {
        $this->setValue('xsendfile_header', $value);
    }

    public function getXSendfileHeaderOptions()
    {
        return array(
            self::X_SENDFILE => 'X-Sendfile',
            self::X_ACCEL_REDIRECT => 'X-Accel-Redirect'
        );
    }

    public function getDistributionDirectory()
    {
        $retVal = $this->getValue('distribution_directory');
        if (! $retVal) {
            return '/dev/null';
        }

        return $retVal;
    }

    public function setDistributionDirectory($value)
    {
        if (substr($value, - 1) != '/') {
            $value = $value . '/';
        }
        $this->setValue('distribution_directory', $value);
    }

    public function getMatterhornVersion()
    {
        $retVal = $this->getValue('matterhorn_version');
        if (! $retVal) {
            return $this->getMatterhornVersionOptions()[0];
        }

        return $retVal;
    }

    public function setMatterhornVersion($value)
    {
        $this->setValue('matterhorn_version', $value);
    }

    public function getMatterhornVersionOptions()
    {
        return array(
            '1.6',
            '2.1'
        );
    }

    public function getUploadWorkflow()
    {
        $retVal = $this->getValue('uploadworkflow');
        if (! $retVal) {
            return 'default';
        }

        return $retVal;
    }

    public function setUploadWorkflow($value)
    {
        $this->setValue('uploadworkflow', $value);
    }

    public function getSigningKey()
    {
        $retVal = $this->getValue('signingkey');
        if (! $retVal) {
            return 'default';
        }

        return $retVal;
    }

    public function setSigningKey($value)
    {
        $this->setValue('signingkey', $value);
    }

    public function getDistributionServer()
    {
        $retVal = $this->getValue('distributionserver');
        if (! $retVal) {
            return 'http://unknown.host';
        }

        return $retVal;
    }

    public function setDistributionServer($value)
    {
        $this->setValue('distributionserver', $value);
    }

    public function getStripUrl()    {
        $retVal = $this->getValue('stripurl');
        if (! $retVal) {
            return 'http://unknown.host';
        }

        return $retVal;
    }

    public function setStripUrl($value)
    {
        $this->setValue('stripurl', $value);
    }

    /**
     *
     * @param string $series_id
     * @return int $obj_id
     */
    public function lookupMatterhornObjectForSeries($series_id)
    {
        global $ilDB;
        $result = $ilDB->query('SELECT obj_id FROM rep_robj_xmh_data WHERE series_id = ' . $ilDB->quote($series_id, 'text'));
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
    public function lookupSeriesForMatterhornObject($obj_id)
    {
        global $ilDB;
        $result = $ilDB->query('SELECT series_id FROM rep_robj_xmh_data WHERE obj_id = ' . $ilDB->quote($obj_id, 'integer'));
        if ($result->numRows() == 0) {
            return false;
        }
        $record = $ilDB->fetchAssoc($result);

        return $record['series_id'];
    }

    /**
     *
     * @param
     *            $key
     * @param
     *            $value
     */
    private function setValue($key, $value)
    {
        global $ilDB;

        if (! is_string($this->getValue($key))) {
            $ilDB->insert('rep_robj_xmh_config', array(
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
            $ilDB->update('rep_robj_xmh_config', array(
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
     * @param
     *            $key
     *            
     * @return bool|string
     */
    private function getValue($key)
    {
        global $ilDB;
        $result = $ilDB->query('SELECT cfgvalue FROM rep_robj_xmh_config WHERE cfgkey = ' . $ilDB->quote($key, 'text'));
        if ($result->numRows() == 0) {
            return false;
        }
        $record = $ilDB->fetchAssoc($result);

        return (string) $record['cfgvalue'];
    }
}
