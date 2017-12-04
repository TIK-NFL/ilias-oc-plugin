<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
chdir("../../../../../../../../");

// Prevent a general redirect to the login screen for anonymous users.
// The checker will show an error page with login link instead
// (see ilInitialisation::InitILIAS() for details)
$_GET['baseClass'] = 'ilStartUpGUI';

$basename = '/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData';

// Define a pseudo module to get a correct ILIAS_HTTP_PATH
// (needed for links on the error page).
// "data" is assumed to be the ILIAS_WEB_DIR
// (see ilInitialisation::buildHTTPPath() for details)
define('ILIAS_MODULE', substr($_SERVER['PHP_SELF'], strpos($_SERVER['PHP_SELF'], $basename) + strlen($basename) + 1));

// Define the cookie path to prevent a different session created for web access
// (see ilInitialisation::setCookieParams() for details)
$GLOBALS['COOKIE_PATH'] = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], $basename));

include_once 'Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_WAC);

// Now the ILIAS header can be included
require_once './include/inc.header.php';
require_once './Services/Utilities/classes/class.ilUtil.php';
require_once './Services/Objecst/classes/class.ilObject.php';
require_once './Services/MediaObjects/classes/class.ilObjMediaObject.php';

require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilMatterhornUploadFile.php";

// get the requested file and its type
$uri = parse_url($_SERVER['REQUEST_URI']);
$method = $_SERVER['REQUEST_METHOD'];

$uf = new ilMatterhornUploadFile($uri, $method);

switch ($uf->getRequestType()) {
    case "uploadCheck":
        if ($uf->checkEpisodeAccess()) {
            $uf->checkChunk();
        } else {
            $uf->sendError();
        }
        break;
    case "upload":
        if ($uf->checkEpisodeAccess()) {
            $uf->uploadChunk();
        } else {
            $uf->sendError();
        }
        break;
    case "createEpisode":
        if ($uf->checkEpisodeAccess()) {
            $uf->createEpisode();
        } else {
            $uf->sendError();
        }
        break;
    case "newJob":
        if ($uf->checkEpisodeAccess()) {
            $uf->createNewJob();
        } else {
            $uf->sendError();
        }
        break;
    case "finishUpload":
        if ($uf->checkEpisodeAccess()) {
            $uf->finishUpload();
        } else {
            $uf->sendError();
        }
        break;
}
?>
