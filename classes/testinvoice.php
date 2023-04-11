<?php

require_once('../classes/paypal.class.php');  // include the class file
include_once('../classes/dbhelper.php');
include_once('../classes/invoicer.php');

$config = array();

include_once("../config.php");


$db = new db($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname']);

$invoicer = new invoicer($db);

$ipn_data = array();

$ipn_data["item_number"] = "2,1";
$subject = "";
$body = "";

$invoicer->processPayment($ipn_data, $subject, $body);

?>
