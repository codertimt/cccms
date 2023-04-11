<?php

$startTime = microtime();
GLOBAL $config;
GLOBAL $extraHead;
GLOBAL $bodyOnLoad;
GLOBAL $dbcalls;

//initialize
$extraHead = "";
$bodyOnLoad = "";
$dbCalls = 0;
$config = array();

include_once("config.php");
include_once("classes/dbhelper.php");
include_once("classes/pageFactory.php");
include_once("classes/display.php");
include_once("classes/catCache.php");

$expireTime = 60*60*24*100; // 100 days
session_set_cookie_params($expireTime);
session_start();

if(!isset($_SESSION['userLevel']))		
	$_SESSION['userLevel'] = 0;

//open db connection
$db = new db($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname']);
//start construction of html with doctype and header
$myhtml = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"
        \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
$myhtml .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\" xml:lang=\"en\">\n";

//get info from db to place in header
$sql = "select siteTitle, siteName, siteSlogan, siteLogo, metaKeywords, metaDesc, themeName, defaultPageType, defaultPageName, footerMsg, domain, admin from " . $config['tableprefix'] . "parms";
$db->runQuery($sql);
$row = $db->getRowObject();

$parms['siteTitle'] = $row->siteTitle;
$parms['siteName'] = $row->siteName;
$parms['siteSlogan'] = $row->siteSlogan;
$parms['siteLogo'] = $row->siteLogo;
$parms['themeName'] = $row->themeName;
$parms['footerMsg'] = $row->footerMsg;

$config['domain'] = $row->domain;
$config['admin'] = $row->admin; 

$config['themeName'] = $parms['themeName'];
$catCache = new catCache($db);
$catCache->init();
$parms['themeName'] = $config['themeName'];
include_once("themes/" . $config['themeName'] . "/theme.php");
include('detectBrowser.php');
$browserData = browser_detection('full');

$useStyle = true;
if ( $browserData[0] === 'ie' )
{
	if ( $browserData[1] <= 4.01 )
	{
		$useStyle = false;
	}
}

$serverName = $_SERVER["HTTP_HOST"];
//$serverName = $_SERVER["SERVER_NAME"];
$myhtml .= "<head>\n";
$myhtml .= "<base href=\"http://$serverName\" />\n";
$myhtml .= "<title> $row->siteTitle</title>\n";

$myhtml .= "<meta name=\"KEYWORDS\" content=\"$row->metaKeywords\" />\n";
$myhtml .= "<meta name=\"DESCRIPTION\" content=\"$row->metaDesc\" />\n";
if($useStyle) {
	$myhtml .= "<style type=\"text/css\">@import url(\"themes/" . $config['themeName'] . "/style.css\"); </style>\n";
	$myhtml .= "<style type=\"text/css\">@import url(\"themes/" . $config['themeName'] . "/eventStyle.css\"); </style>\n";
}


//parse _GET array for parameters
$pageFactory = new pageFactory($db);
if(sizeof($_GET) == 0) {
	$_GET['page'] = $row->defaultPageType;
	$_GET['name'] = $row->defaultPageName;
}
$page = $pageFactory->getPageClass();
//create our theme object
$theme = new myTheme($parms);
//create display object passing theme, db connection,and page object
$display = new display($db, $theme, $page);

$displayText .= $display->getDisplayText();
$myhtml .= $extraHead;
$myhtml .= "</head>\n";
$myhtml .= "<body $bodyOnLoad><div id=\"outer\">\n";
$myhtml .= $displayText;
$endTime = microtime();
$totalTime = $endTime - $startTime;
//$myhtml .= "<center>Total database calls: $dbcalls<br>Total Time: $totalTime</center>";
$myhtml .= "</div></body></html>";

echo $myhtml;
?>
