<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
 
/**
* Matterhorn repository object plugin
*
* @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
* @version $Id$
*
*/
class ilMatterhornPlugin extends ilRepositoryObjectPlugin
{
	function getPluginName()
	{
		return "Matterhorn";
	}
}
?>
