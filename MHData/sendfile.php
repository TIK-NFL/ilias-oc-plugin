<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

chdir("../../../../../../../../");

require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn//classes/class.ilMatterhornSendfile.php";

$sf = new ilMatterhornSendfile();

if($sf->isEpisodeRequest()){
	
	if ($sf->checkEpisodeAccess())
	{
		$sf->sendEpisode();
	}
	else
	{
		$sf->sendError();
	}
	
} else {
	if ($sf->checkFileAccess())
	{
		$sf->sendFile();
	}
	else
	{
		$sf->sendError();
	}
}
?>
