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
class ilObjMatterhornTableUpcommingGUI extends ilTable2GUI
{
	
	function ilObjMatterhornTableUpcommingGUI($parent, $parent_cmd = "")
	{
		global $ilCtrl, $lng;		
		parent::__construct($parent, $parent_cmd);
		$this->pluginGUI = $parent;		
		$this->addColumn($lng->txt("title"));
		$this->addColumn($lng->txt("date"));
		$this->addColumn($lng->txt("action"));
		$this->setFormAction($ilCtrl->getFormAction($parent));
		$this->setRowTemplate("tpl.table_matterhorn_upcomming_row.html",
			"Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
		$this->setShowRowsSelector(false);
	}
	
    /**
    * Fills a row with an episode of the series
    */
	protected function fillRow($rowdata)
	{
		global $ilCtrl, $lng;
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilObjMatterhorn.php");
		$ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $rowdata['mhid']);
		$this->tpl->setVariable("CMD_DOWNLOAD", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "deleteUpcomming"));
        $this->tpl->setVariable("PREVIEWURL", $rowdata["previewurl"]);
		$this->tpl->setVariable("TXT_TITLE", $rowdata["title"]);
		$this->tpl->setVariable("PUBLISHED", $rowdata["published"]?true:false);
        $this->tpl->setVariable("CMD_PUBLISH", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", $rowdata["published"]?"retract":"publish"));
		$this->tpl->setVariable("TXT_DATE", ilDatePresentation::formatDate(new ilDateTime($rowdata["date"],IL_CAL_DATETIME)));
        $this->tpl->setVariable("TXT_DELETE", $this->pluginGUI->getText($rowdata["published"]?"retract":"publish"));
		
	}	

}
?>
