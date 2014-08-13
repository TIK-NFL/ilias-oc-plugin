<?php
class ilMatterhornConfig
{
	/**
	 * returns the hostname for the matterhorn server
	 * @return the hostname for the matterhorn server
	 */
	public function getMatterhornServer(){		
		$retVal = $this->getValue("mh_server");
		if(!$retVal){
			return "http://host.is.unset";
		}
		return $retVal;
	}
	
	public function getXSendfileBasedir(){
		$retVal = $this->getValue("xsendfile_basedir");
		if(!$retVal){
			return "/dev/null";
		}
		return $retVal;		
	}
	
	public function setXSendfileBasedir($value){
		$this->setValue("xsendfile_basedir", $value);
	}
	
	/**
	 * @param $key
	 * @param $value
	 */
	public function setValue($key, $value)
	{
		global $ilDB;

		if(!is_string($this->getValue($key)))
		{
			$ilDB->insert("rep_robj_xmh_config"   , array("cfgkey"   => array("text",$key),"cfgvalue" => array("text",$value)));
		}
		else
		{
			$ilDB->update("rep_robj_xmh_config"   , array("cfgkey"   => array("text", $key), "cfgvalue" => array("text",$value))
					, array("cfgkey" => array("text",$key))
			);
		}
	}

	/**
	 * @param $key
	 *
	 * @return bool|string
	 */
	public function getValue($key)
	{
		global $ilDB;
		$result = $ilDB->query("SELECT cfgvalue FROM rep_robj_xmh_config WHERE cfgkey = " . $ilDB->quote($key, "text"));
		if($result->numRows() == 0)
		{
			return false;
		}
		$record = $ilDB->fetchAssoc($result);

		return (string)$record['cfgvalue'];
	}
}


?>