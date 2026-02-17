<?php

chdir(dirname(__DIR__, 8)); // 8 since we got the public folder now
// Prevent a general redirect to the login screen for anonymous users.
// The checker will show an error page with login link instead
// (see ilInitialisation::InitILIAS() for details)
$_GET["baseClass"] = "ilStartUpGUI";

$basename = "/Customizing/global/plugins/Services/Repository/RepositoryObject/Opencast/api.php";

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

// for debugging
//error_log("cwd=" . getcwd());
//error_log("PHP_SELF=" . $_SERVER['PHP_SELF']);
//error_log("basename=" . $basename);
//error_log("context_exists=" . (file_exists("components/ILIAS/Context/classes/class.ilContext.php") ? "yes" : "no"));

// ILIAS 10 bootstrap
require_once "vendor/composer/vendor/autoload.php";

// /ILIAS/components/ILIAS/Context/handle-context-related-possibilities.md
include_once "components/ILIAS/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_WAC);

// Now the ILIAS header can be included
ilInitialisation::initILIAS();

$uri = parse_url($_SERVER['REQUEST_URI']);
$method = $_SERVER['REQUEST_METHOD'];

$api = new \TIK_NFL\ilias_oc_plugin\api\ilAPIController($uri, $method);

// get the requested file and its type
$path = substr($uri["path"], strpos($uri["path"], $basename) + strlen($basename));

$api->handleRequest($path);
?>
