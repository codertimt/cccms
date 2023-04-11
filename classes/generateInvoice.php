<?php

include_once('../classes/dbhelper.php');
include_once('../classes/invoicer.php');

$config = array();

include_once("../config.php");

$db = new db($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname']);

$invoicer = new invoicer($db);
$sql = "select * from cc_user where hostingType != 0";

if(!$db->runQuery($sql)) {
	$body .= "Generate invoice failed for $uid getting user info.\n";	
	$subject .= " - Generate invoice failed";	
	return;
}
$numRows = $db->getNumRows();

$body = "";
$subject = "";
for($userNum = 0; $userNum < $numRows; ++$userNum) {
	$userInfo = $db->getRowObject();
	$invoicer->generate($userInfo, $body, $subject);
}


?>
