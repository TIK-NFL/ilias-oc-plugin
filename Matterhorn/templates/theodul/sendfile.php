<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

chdir("../../../../../../../../../");

require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilMatterhornSendPlayer.php";

$sf = new ilMatterhornSendPlayer();

if ($sf->checkFileAccess())
{
	$sf->sendFile();
}
else
{
	$sf->sendError();
}

?>
