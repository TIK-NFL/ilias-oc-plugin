<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

chdir("../../../../../../../../");

require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn//classes/class.ilMatterhornUploadFile.php";

$sf = new ilMatterhornUploadFile();

switch ($sf->getRequestType()) {
    case "uploadCheck":
        if ($sf->checkEpisodeAccess())
        {
            $sf->checkChunk();
        }
        else
        {
            $sf->sendError();
        }
        break;
    case "upload":
        if ($sf->checkEpisodeAccess())
        {
            $sf->uploadChunk();
        }
        else
        {
            $sf->sendError();
        }
        break;
    case "createEpisode":
        if ($sf->checkEpisodeAccess())
        {
            $sf->createEpisode();
        }
        else
        {
            $sf->sendError();
        }
        break;
    case "newJob":
        if ($sf->checkEpisodeAccess())
        {
            $sf->createNewJob();
        }
        else
        {
            $sf->sendError();
        }
    break;
    case "finishUpload":
        if ($sf->checkEpisodeAccess())
        {
            $sf->finishUpload();
        }
        else
        {
            $sf->sendError();
        }
    break;

}
?>
