<?php

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once("Services/Table/classes/class.ilTable2GUI.php");

/**
* TableGUI class for table NewsForContext
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
*/
class ilObjMatterhornTableGUI extends ilTable2GUI
{
	
	function ilObjMatterhornTableGUI($a_parent_obj, $a_parent_cmd = "")
	{
		global $ilCtrl, $lng;
		
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		
		$this->addColumn($lng->txt("Episode"));
		$this->addColumn($lng->txt("Date"));
		$this->addColumn($lng->txt("Download"));
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate("tpl.table_matterhorn_row.html",
			"Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
		
		$this->setShowRowsSelector(false);

		// this messes up the db ordering, where the id is also taken into
		// account, if the creation date is the same (this happens e.g. on import)
//		$this->setDefaultOrderField("creation_date");
//		$this->setDefaultOrderDirection("desc");
	}
	
	/**
	* Standard Version of Fill Row. Most likely to
	* be overwritten by derived class.
	*/
	protected function fillRow($a_set)
	{
		global $ilCtrl;

		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilObjMatterhorn.php");
				
	//	$ilCtrl->setParameterByClass("ilobjmediacastgui", "item_id", "");
		$ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $a_set['mhid']);
		$this->tpl->setVariable("CMD_DOWNLOAD", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode"));
		$this->tpl->setVariable("TXT_TITLE", $a_set["title"]);
		$this->tpl->setVariable("TXT_NR", $a_set["nr"]);
	}	

}
?>
