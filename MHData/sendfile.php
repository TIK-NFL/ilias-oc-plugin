<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

chdir("../../../../../../../../");

require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn//classes/class.ilMatterhornSendfile.php";

$sf = new ilMatterhornSendfile();

switch ($sf->getRequestType()) {
    case "episode":
        if ($sf->checkEpisodeAccess())
        {
            $sf->sendEpisode();
        }
        else
        {
            $sf->sendError();
        }
        break;
    case "preview":
        if ($sf->checkPreviewAccess())
        {
            $sf->sendPreview();
        }
        else
        {
            $sf->sendError();
        }
        break;
    case "file":
        if ($sf->checkFileAccess())
        {
            $sf->sendFile();
        }
        else
        {
            $sf->sendError();
        }
        break;
}
?>
