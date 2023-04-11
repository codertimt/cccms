<?php

/*  PHP Paypal IPN Integration Class Demonstration File
 *  4.16.2005 - Micah Carrick, email@micahcarrick.com
 *
 *  This file demonstrates the usage of paypal.class.php, a class designed  
 *  to aid in the interfacing between your website, paypal, and the instant
 *  payment notification (IPN) interface.  This single file serves as 4 
 *  virtual pages depending on the "action" varialble passed in the URL. It's
 *  the processing page which processes form data being submitted to paypal, it
 *  is the page paypal returns a user to upon success, it's the page paypal
 *  returns a user to upon canceling an order, and finally, it's the page that
 *  handles the IPN request from Paypal.
 *
 *  I tried to comment this file, aswell as the acutall class file, as well as
 *  I possibly could.  Please email me with questions, comments, and suggestions.
 *  See the header of paypal.class.php for additional resources and information.
*/

// Setup class
require_once('../classes/paypal.class.php');  // include the class file
include_once('../classes/dbhelper.php');
include_once('../classes/invoicer.php');

$config = array();

include_once("../config.php");

$p = new paypal_class;             // initiate an instance of the class
//$p->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';   // testing paypal url
$p->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';     // paypal url
            
// setup a variable for this script (ie: 'http://www.micahcarrick.com/paypal.php')
$this_script = 'http://'.$_SERVER['HTTP_HOST'];

// if there is not action variable, set the default action of 'process'
if (empty($_GET['action'])) $_GET['action'] = 'none';  

switch ($_GET['action']) {
    
   case 'process':      // Process and order...

      // There should be no output at this point.  To process the POST data,
      // the submit_paypal_post() function will output all the HTML tags which
      // contains a FORM which is submited instantaneously using the BODY onload
      // attribute.  In other words, don't echo or printf anything when you're
      // going to be calling the submit_paypal_post() function.
 
      // This is where you would have your form validation  and all that jazz.
      // You would take your POST vars and load them into the class like below,
      // only using the POST values instead of constant string expressions.
 
      // For example, after ensureing all the POST variables from your custom
      // order form are valid, you might have:
      //
      // $p->add_field('first_name', $_POST['first_name']);
      // $p->add_field('last_name', $_POST['last_name']);
		if(isset($_POST['paymentType']) && isset($_POST['hostingType'])) {
	     	$paymentType = $_POST['paymentType'];
	     	$hostingType = $_POST['hostingType'];
			$db = new db($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname']);
		} else {
			echo "Error submitting form data. Cannot find payment params.";
			return;
		}

		$sql = "select * from paypal_payment_types where hostingType = " . $hostingType 
				. " and paymentType = $paymentType";
		$db->runQuery($sql);	
		$numTypes = $db->getNumRows();
		if($numTypes != 1){
			echo "Error submitting form data. Cannot get payment info.";
			return;
		}
		
		$item = $db->getRowObject();
		$p->add_field('item_number', $_POST['paymentType'] . "," . $_POST['invoiceId']);
		$p->add_field('business', 'sales@churchcontent.com');
		$p->add_field('return', $this_script.'/paypal/success_print.html');
		$p->add_field('cancel_return', $this_script.'/paypal/cancel_it.html');
		$p->add_field('notify_url', 'http://www.churchcontent.com/pages/paypal_quiet.php?action=ipn');
		$p->add_field('item_name', $item->desc);
		$p->add_field('amount', $item->amount);

		if($paymentType == 2) {	
	    	$p->add_field('cmd','_xclick-subscriptions'); 
			$p->add_field('modify', '1');	
			$p->add_field('encrypted', $item->subscr_txt);
		}
		$p->submit_paypal_post(); // submit the fields to paypal
		//$p->dump_fields();      // for debugging, output a table of all the fields
		break;
      
   case 'ipn':          // Paypal is calling page for IPN validation...
   
      // It's important to remember that paypal calling this script.  There
      // is no output here.  This is where you validate the IPN data and if it's
      // valid, update your database to signify that the user has payed.  If
      // you try and use an echo or printf function here it's not going to do you
      // a bit of good.  This is on the "backend".  That is why, by default, the
      // class logs all IPN data to a text file.

		if ($p->validate_ipn()) {

			// Payment has been recieved and IPN is verified.  This is where you
			// update your database to activate or process the order, or setup
			// the database with the user's order details, email an administrator,
			// etc.  You can access a slew of information via the ipn_data() array.

			// Check the paypal documentation for specifics on what information
			// is available in the IPN POST variables.  Basically, all the POST vars
			// which paypal sends, which we send back for validation, are now stored
			// in the ipn_data() array.

			// For this example, we'll just email ourselves ALL the data.

			$db = new db($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname']);

			$subject = 'Instant Payment Notification - Recieved Payment';
			$to = 'ttempl@tcworks.net';    //  your email
			$body =  "An instant payment notification was successfully recieved\n";
			$body .= "from ".$p->ipn_data['payer_email']." on ".date('m/d/Y');
			$body .= " at ".date('g:i A')."\n\nDetails:\n";

			foreach ($p->ipn_data as $key => $value) { 
				$body .= "\n$key: $value"; 
			}
			$invoicer = new invoicer($db);

			$invoicer->processPayment($p->ipn_data, $subject, $body);

			mail($to, $subject, $body);

		}
      break;
	case 'none':
		echo "This page should only be called for silent processing.";
	break;
 }     

?>
