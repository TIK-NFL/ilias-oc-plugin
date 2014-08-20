<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");

/**
* Application class for matterhorn repository object.
*
* @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
*
* $Id$
*/
class ilObjMatterhorn extends ilObjectPlugin
{

	/**
	 * Stores the search result from doRead()
	 */
	var $searchResult;
	
	/**
	* Constructor
	*
	* @access	public
	*/
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilMatterhornConfig.php");
        $this->configObject = new ilMatterhornConfig();

	}	

	/**
	* Get type.
	*/
	final function initType()
	{
		$this->setType("xmh");
	}
	
	/**
	* Create object
	*/
	function doCreate()
	{
		global $ilDB, $ilLog;
		
		$url = $this->configObject->getMatterhornServer()."/series/";
		$ilLog->write("MHObj MHServer:".$url);
		$fields = array(
				'series'=>urlencode('<?xml version="1.0"?>
<dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.opencastproject.org http://www.opencastproject.org/schema.xsd" xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/" xmlns:oc="http://www.opencastproject.org/matterhorn/">

  <dcterms:title xml:lang="en">'.
	$this->getTitle().
    '</dcterms:title>
  <dcterms:subject>
    climate, land, vegetation
    </dcterms:subject>
  <dcterms:description xml:lang="en">
    Introduction lecture from the Institute for
    Atmospheric and Climate Science.
    </dcterms:description>
  <dcterms:publisher>
    ETH Zurich, Switzerland
    </dcterms:publisher>
  <dcterms:identifier>
    ilias_xmh_'.$this->getId().
    '</dcterms:identifier>
  <dcterms:modified xsi:type="dcterms:W3CDTF">
    2007-12-05
    </dcterms:modified>
  <dcterms:format xsi:type="dcterms:IMT">
    video/x-dv
    </dcterms:format>
  <oc:promoted>
    true
  </oc:promoted>
</dublincore>'),
				'acl'=>urlencode('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><acl xmlns="http://org.opencastproject.security"><ace><role>admin</role><action>delete</action><allow>true</allow></ace></acl>')
		);
				
		
		//url-ify the data for the POST
		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		rtrim($fields_string,'&');
		
		//open connection
		$ch = curl_init();
		
		//set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST,count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
		curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);				

		$ilDB->manipulate("INSERT INTO rep_robj_xmh_data ".
				"(id, is_online, option_one, option_two) VALUES (".
				$ilDB->quote($this->getId(), "integer").",".
				$ilDB->quote(0, "integer").",".
				$ilDB->quote($result, "text").",".
				$ilDB->quote($httpCode, "text").
				")");
		
	}
	
	/**
	* Read data from db
	*/
	function doRead()
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xmh_data ".
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->setOnline($rec["is_online"]);
			$this->setOptionOne($rec["option_one"]);
			$this->setOptionTwo($rec["option_two"]);
		}
		
		$url = $this->configObject->getMatterhornEngageServer()."/search/episode.json";

		/* $_GET Parameters to Send */
		$params = array('sid' =>'ilias_xmh_'.$this->getId());
		
		/* Update URL to container Query String of Paramaters */
		$url .= '?' . http_build_query($params);
		
		//open connection
		$ch = curl_init();
		
		//set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
		curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		$this->searchResult = json_decode(curl_exec($ch),true);
		//$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
	}
	
	/**
	* Update data
	*/
	function doUpdate()
	{
		global $ilDB;
		
		$ilDB->manipulate($up = "UPDATE rep_robj_xmh_data SET ".
			" is_online = ".$ilDB->quote($this->getOnline(), "integer").",".
			" option_one = ".$ilDB->quote($this->getOptionOne(), "text").",".
			" option_two = ".$ilDB->quote($this->getOptionTwo(), "text").
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
	}
	
	/**
	* Delete data from db
	*/
	function doDelete()
	{
		global $ilDB;
		
		$ilDB->manipulate("DELETE FROM rep_robj_xmh_data WHERE ".
			" id = ".$ilDB->quote($this->getId(), "integer")
			);
		
	}
	
	/**
	* Do Cloning
	*/
	function doClone($a_target_id,$a_copy_id,$new_obj)
	{
		global $ilDB; 
		
		$new_obj->setOnline($this->getOnline());
		$new_obj->setOptionOne($this->getOptionOne());
		$new_obj->setOptionTwo($this->getOptionTwo());
		$new_obj->update();
	}
	
//
// Set/Get Methods for our example properties
//

	/**
	* Set online
	*
	* @param	boolean		online
	*/
	function setOnline($a_val)
	{
		$this->online = $a_val;
	}
	
	/**
	* Get online
	*
	* @return	boolean		online
	*/
	function getOnline()
	{
		return $this->online;
	}
	
	/**
	* Set option one
	*
	* @param	string		option one
	*/
	function setOptionOne($a_val)
	{
		$this->option_one = $a_val;
	}
	
	/**
	* Get option one
	*
	* @return	string		option one
	*/
	function getOptionOne()
	{
		return $this->option_one;
	}
	
	/**
	* Set option two
	*
	* @param	string		option two
	*/
	function setOptionTwo($a_val)
	{
		$this->option_two = $a_val;
	}
	
	/**
	* Get option two
	*
	* @return	string		option two
	*/
	function getOptionTwo()
	{
		return $this->option_two;
	}

	/**
	 * The series information returned by matterhorn
	 * 
	 * @return string the series information returned by matterhorn as json
	 */
	function getSearchResult(){
		return $this->searchResult;
	}
	
}
?>
