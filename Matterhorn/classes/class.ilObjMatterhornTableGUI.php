<?php

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once("Services/Table/classes/class.ilTable2GUI.php");

/**
* TableGUI class for table Matterhorn
*
* @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
* @version $Id$
*
*/
class ilObjMatterhornTableGUI extends ilTable2GUI
{
	
	function ilObjMatterhornTableGUI($a_parent_obj, $a_parent_cmd = "")
	{
		global $ilCtrl, $lng;
		
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		
		$this->addColumn("");
		$this->addColumn($lng->txt("title"));
		$this->addColumn($lng->txt("date"));
		$this->addColumn($lng->txt("view"));
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate("tpl.table_matterhorn_row.html",
			"Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
		
		$this->setShowRowsSelector(false);
	}
	
	/**
	* Standard Version of Fill Row. Most likely to
	* be overwritten by derived class.
	*/
	protected function fillRow($a_set)
	{
		global $ilCtrl;

		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilObjMatterhorn.php");
		$ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $a_set['mhid']);
		$this->tpl->setVariable("CMD_DOWNLOAD", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode"));
		$this->tpl->setVariable("TXT_TITLE", $a_set["title"]);
		$this->tpl->setVariable("TXT_DATE", $a_set["date"]);
		$this->tpl->setVariable("TXT_NR", $a_set["nr"]);
	}	

}
?>
