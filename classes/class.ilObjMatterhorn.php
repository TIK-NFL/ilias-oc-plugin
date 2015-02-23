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
	 * Stores the series
	 */
	var $series;

	/**
	 * Stores the mhretval
	 */
	var $mhretval;
	
	/**
	 * Stores the lectureID
	 */
	var $lectureID;
	
	/**
	 * Stores the viewmode
	 */
	var $viewMode;

        /**
         * Stores the manual release
         */
        var $manualrelease;

	
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
	function doCreate($a_clone_mode)
	{
		global $ilDB, $ilLog;
		$url = $this->configObject->getMatterhornServer()."/series/";
		$ilLog->write("MHObj MHServer:".$url);
				
		$fields = $this->createPostFields();
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
				"(id, is_online, series, mhretval, lectureid,viewmode,manualrelease) VALUES (".
				$ilDB->quote($this->getId(), "integer").",".
				$ilDB->quote(0, "integer").",".
				$ilDB->quote($result, "text").",".
				$ilDB->quote($httpCode, "text").",".
				$ilDB->quote($this->getLectureID(), "text").",".
				$ilDB->quote(0, "integer").",".
				$ilDB->quote($this->getManualRelease(), "integer").
				")");
	}
	
	/**
	* Read data from db
	*/
	function doRead()
	{
		global $ilDB,$ilLog;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xmh_data ".
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->setOnline($rec["is_online"]);
			$this->setSeries($rec["series"]);
			$this->setMhRetVal($rec["mhretval"]);
			$this->setLectureID($rec["lectureid"]);
			$this->setViewMode($rec["viewmode"]);
			$this->setManualRelease($rec["manualrelease"]);
		}
		
	}
	
	/**
	* Update data
	*/
	function doUpdate()
	{
		global $ilDB,$ilLog;

		$url = $this->configObject->getMatterhornServer()."/series/";
		$ilLog->write("MHObj MHServer:".$url);
		$fields = $this->createPostFields();
		
		
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
		
		
		$ilDB->manipulate($up = "UPDATE rep_robj_xmh_data SET ".
			" is_online = ".$ilDB->quote($this->getOnline(), "integer").",".
			" series = ".$ilDB->quote($this->getSeries(), "text").",".
			" lectureid = ".$ilDB->quote($this->getLectureID(), "text").",".
			" viewmode = ".$ilDB->quote($this->getViewMode(), "integer").",".
                        " manualrelease = ".$ilDB->quote($this->getManualRelease(), "integer").",".
			" mhretval = ".$ilDB->quote($this->getMhRetVal(), "text")." ".
			" WHERE id = ".$ilDB->quote($this->getId(), "text")
			);
	}
	
	
	/**^
	* Delete data from db
	*/
	function doDelete()
	{
		global $ilDB;
	
                $ilDB->manipulate("DELETE FROM  rep_robj_xmh_released_episodes ".
                        " WHERE seriesid = ".$ilDB->quote($this->getId(), "text")
                        );
           
		$ilDB->manipulate("DELETE FROM rep_robj_xmh_data WHERE ".
			" id = ".$ilDB->quote($this->getId(), "integer")
			);
	
	
	}
	
	/**
	* Do Cloning
	*/
	function doCloneObject($new_obj, $a_target_id, $a_copy_id)
	{	
	#	$new_obj->setSeries($this->getSeries());
#		$new_obj->setMhRetVal($this->getMhRetVal());
#		$new_obj->setOnline($this->getOnline());
#		$new_obj->update();
	}
	
	private function createPostFields() {
		
		global  $ilUser, $ilLog;
		
		$userid = $ilUser->getLogin();
		if (null != $ilUser->getExternalAccount) {
			$userid = $ilUser->getExternalAccount();
		}
		$ilLog->write("Current User: ".$userid);
		$acl = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><acl xmlns="http://org.opencastproject.security">
								<ace><role>'.$userid.'</role><action>read</action><allow>true</allow></ace>
								<ace><role>'.$userid.'</role><action>write</action><allow>true</allow></ace>
						</acl>';
		$fields = array(
				'series'=>urlencode('<?xml version="1.0"?>
<dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.opencastproject.org http://www.opencastproject.org/schema.xsd" xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/" xmlns:oc="http://www.opencastproject.org/matterhorn/">
		
  <dcterms:title xml:lang="en">'.
						$this->getTitle().
						'</dcterms:title>
  <dcterms:subject>
    </dcterms:subject>
  <dcterms:description xml:lang="en">'.
						$this->getRefId()." ".$this->getDescription().
						'</dcterms:description>
  <dcterms:publisher>
    University of  Stuttgart, Germany
    </dcterms:publisher>
  <dcterms:identifier>
    ilias_xmh_'.$this->getId().
						'</dcterms:identifier>
  <dcterms:references>'.
						$this->getLectureID()
						.'</dcterms:references>
  <dcterms:modified xsi:type="dcterms:W3CDTF">'.
						date("Y-m-d").
						'</dcterms:modified>
  <dcterms:format xsi:type="dcterms:IMT">
    video/mp4
    </dcterms:format>
  <oc:promoted>
   	false
  </oc:promoted>
</dublincore>'),
				'acl'=>urlencode($acl)
		);
		return $fields;
	}
	
//
// Set/Get Methods for the properties
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
	 * Set series information
	 *
	 * @param	String		series
	 */
	function setSeries($a_val)
	{
		$this->series = $a_val;
	}
	
	/**
	 * Get series information
	 *
	 * @return	String		series
	 */
	function getSeries()
	{
		return $this->series;
	}

	/**
	 * Set the http return code when creating the series
	 *
	 * @param	int		mhretval
	 */
	function setMhRetVal($a_val)
	{
		$this->mhretval = $a_val;
	}
	
	/**
	 * Get the http return code when creating the series
	 *
	 * @return	int		mhretval
	 */
	function getMhRetVal()
	{
		return $this->mhretval;
	}

	/**
	 * Set the lectureID 
	 *
	 * @param	String		lectureID
	 */
	function setLectureID($a_val)
	{
		$this->lectureID = $a_val;
	}
	
	/**
	 * Get the lectureID
	 *
	 * @return	string lectureID
	 */
	function getLectureID()
	{
		return $this->lectureID;
	}
	
	/**
	 * Set the ViewMode 
	 *
	 * @param	Integer		viewMode
	 */
	function setViewMode($a_val)
	{
		$this->viewMode = $a_val;
	}
	
	/**
	 * Get the ViewMode
	 *
	 * @return	Integer viewMode
	 */
	function getViewMode()
	{
		return $this->viewMode;
	}

	
        /**
        * Set manual release
        *
        * @param        boolean         manual release
        */
        function setManualRelease($a_val)
        {
                $this->manualrelease = $a_val;
        }
        
        /**
        * Get manual release
        *
        * @return       boolean         manualrelease
        */
        function getManualRelease()
        {
                return $this->manualrelease;
        }

	
	/**
	 * The series information returned by matterhorn
	 * 
	 * @return array the episodes by matterhorn for the given seris
	 */
        function getSearchResult(){
	                
              $url = $this->configObject->getMatterhornEngageServer()."/search/episode.json";

              /* $_GET Parameters to Send */
              $params = array('sid' =>'ilias_xmh_'.$this->getId());
              
              /* Update URL to container Query String of Paramaters */
              $url .= '?' . http_build_query($params);
              $ilLog->write("mh query".$url);
              //open connection
              $ch = curl_init();
              
              //set the url, number of POST vars, POST data
              curl_setopt($ch, CURLOPT_URL,$url);
              curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
              curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
              curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
              curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
              $curlret = curl_exec($ch);        
              $searchResult = json_decode($curlret,true);

              return $searchResult;
        }

	/**
         * Returns a list of the Episodes that have been made public available by the lecturer
         *
         * @return array containing the ids of the episodes that have been made public available.
         */
        function getReleasedEpisodes(){
                global $ilDB,$ilLog;
                
                $set = $ilDB->query("SELECT episodeId FROM rep_robj_xmh_released_episodes ".
                        " WHERE seriesId = ".$ilDB->quote($this->getId(), "text")
                        );
                $released = array();
                while ($rec = $ilDB->fetchAssoc($set))
                {
                        array_push($released,($rec["episodeId"]);
                }
                return $released;
        }
        
        /**
         * The upcomming information for this series returned by matterhorn
         * 
         * @return array the upcomming episodes for this series returned by matterhorn
         */
        function getUpcommingEpisodes(){
                        
                $url = $this->configObject->getMatterhornEngageServer()."/workflow/instances.json";

                /* $_GET Parameters to Send */
                $params = array('seriesId' =>'ilias_xmh_'.$this->getId(),'state' => 'SCHEDULED');
                
                /* Update URL to container Query String of Paramaters */
                $url .= '?' . http_build_query($params);
                $ilLog->write("mh query".$url);
                //open connection
                $ch = curl_init();
                
                //set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
                curl_setopt($ch, CURLOPT_USERPWD, $this->configObject->getMatterhornUser().':'.$this->configObject->getMatterhornPassword());
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest","X-Opencast-Matterhorn-Authorization: true"));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
                $curlret = curl_exec($ch);        
                $searchResult = json_decode($curlret,true);

                return $searchResult;
        }
}
?>
