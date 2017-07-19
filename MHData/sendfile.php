<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
chdir("../../../../../../../../");

// Prevent a general redirect to the login screen for anonymous users.
// The checker will show an error page with login link instead
// (see ilInitialisation::InitILIAS() for details)
$_GET["baseClass"] = "ilStartUpGUI";

$basename = "/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData";

// Define a pseudo module to get a correct ILIAS_HTTP_PATH
// (needed for links on the error page).
// "data" is assumed to be the ILIAS_WEB_DIR
// (see ilInitialisation::buildHTTPPath() for details)
define("ILIAS_MODULE", substr($_SERVER['PHP_SELF'], strpos($_SERVER['PHP_SELF'], $basename) + strlen($basename) + 1));

// Define the cookie path to prevent a different session created for web access
// (see ilInitialisation::setCookieParams() for details)
$GLOBALS['COOKIE_PATH'] = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], $basename));

// Remember if the initial session was empty
// Then a new session record should not be written
// (see ilSession::_writeData for details)
$GLOBALS['WEB_ACCESS_WITHOUT_SESSION'] = (session_id() == "");

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_WAC);

// Now the ILIAS header can be included
require_once "./include/inc.header.php";
require_once "./Services/Utilities/classes/class.ilUtil.php";
require_once "./Services/Object/classes/class.ilObject.php";
require_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";

require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn//classes/class.ilMatterhornSendfile.php";

$sf = new ilMatterhornSendfile();

switch ($sf->getRequestType()) {
    case "episode":
        if ($sf->checkEpisodeAccess()) {
            $sf->sendEpisode();
        } else {
            $sf->sendError();
        }
        break;
    case "usertracking":
        if ($sf->checkEpisodeAccess()) {
            $sf->putUserTracking();
        } else {
            $sf->sendError();
        }
        break;
    case "preview":
        if ($sf->checkPreviewAccess()) {
            $sf->sendPreview();
        } else {
            $sf->sendError();
        }
        break;
    case "file":
        if ($sf->checkFileAccess()) {
            $sf->sendFile();
        } else {
            $sf->sendError();
        }
        break;
}
?>
