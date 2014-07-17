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
	* Constructor
	*
	* @access	public
	*/
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
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
		global $ilDB;
		
		$ilDB->manipulate("INSERT INTO rep_robj_xmh_data ".
			"(id, is_online, option_one, option_two) VALUES (".
			$ilDB->quote($this->getId(), "integer").",".
			$ilDB->quote(0, "integer").",".
			$ilDB->quote("default 1", "text").",".
			$ilDB->quote("default 2", "text").
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

}
?>
