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

	public function setMatterhornServer($a_server){
		$this->setValue("mh_server",$a_server);
	}

	public function getMatterhornEngageServer(){
		$retVal = $this->getValue("mh_server_engage");
		if(!$retVal){
			return "http://host.is.unset";
		}
		return $retVal;
	}
	
	public function setMatterhornEngageServer($a_server){
		$this->setValue("mh_server_engage",$a_server);
	}
	
	public function getMatterhornUser(){
		$retVal = $this->getValue("mh_digest_user");
		if(!$retVal){
			return "matterhorn_system_account";
		}
		return $retVal;
	}
	
	public function setMatterhornUser($a_user){
		$this->setValue("mh_digest_user",$a_user);
	}
	
	public function getMatterhornPassword(){
		$retVal = $this->getValue("mh_digest_password");
		if(!$retVal){
			return "CHANGE_ME";
		}
		return $retVal;
	}
	
	public function setMatterhornPassword($a_password){
		$this->setValue("mh_digest_password",$a_password);
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

	public function getMatterhornVersion(){
		$retVal = $this->getValue("matterhorn_version");
		if(!$retVal){
			return "1.4";
		}
		return $retVal;
	}
	
	public function setMatterhornVersion($value){
		$this->setValue("matterhorn_version", $value);
	}
	
	
	/**
	 * @param $key
	 * @param $value
	 */
	private function setValue($key, $value)
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
	private function getValue($key)
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