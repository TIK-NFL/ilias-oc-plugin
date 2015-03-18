<?php

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once("Services/Table/classes/class.ilTable2GUI.php");

/**
* TableGUI class listing the episodes of a series
*
* @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
* @version $Id$
*
*/
class ilObjMatterhornTableSeriesGUI extends ilTable2GUI
{
	
	function ilObjMatterhornTableSeriesGUI($parent, $parent_cmd = "")
	{
		global $ilCtrl, $lng;
		parent::__construct($parent, $parent_cmd);

		$this->addColumn("");
		$this->addColumn($lng->txt("title"));
		$this->addColumn($lng->txt("date"));
		
		$this->setFormAction($ilCtrl->getFormAction($parent));
		$this->setRowTemplate("tpl.table_matterhorn_episode_row.html",
			"Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
		$this->setShowRowsSelector(false);
	}
	
	/**
	* Fills a row with an episode of the series
	*/
	protected function fillRow($rowdata)
	{
		global $ilCtrl;
        
        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilObjMatterhorn.php");
		$ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $rowdata['mhid']);
		$this->tpl->setVariable("CMD_DOWNLOAD", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode"));
		$this->tpl->setVariable("PREVIEWURL", $rowdata["previewurl"]);
		$this->tpl->setVariable("TXT_TITLE", $rowdata["title"]);
		$this->tpl->setVariable("TXT_DATE", ilDatePresentation::formatDate(new ilDateTime($rowdata["date"],IL_CAL_DATETIME)));
		$this->tpl->setVariable("TXT_NR", $rowdata["nr"]);
	}	

}
?>
