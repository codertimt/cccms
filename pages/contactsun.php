<?php

include_once("pages/page.php");

class contact extends page
{
	var $m_db;
	var $m_title;
	var $m_data;
	var $m_showLeftBlocks;
	var $m_showRightBlocks;
	var $m_minUserLevel;
	var	$m_function;

	function contact($db) {
		GLOBAL $config;
		$this->m_db = $db;
		$this->m_page->category = 1;

		$this->page();
	}
	
	function getDisplayText() {
		$evalStr = '$html .= $this->' . $this->m_name . '();';
		eval($evalStr);
		return $html;
	}	
	//need to over load these since contact 	
	function hideLeftBlocks() {
		return 1;
	}
	
	function hideRightBlocks() {
		return 1;
	}

	function getPageTitle() {
		return $this->m_name;
	}

	function index() {
		$html = "<h3>Contact Us</h3>";
		$html .= $this->showForm();

		return $html;
	}

	function showForm() {
		$name = addslashes(htmlspecialchars($_POST['name']));
		$email = addslashes(htmlspecialchars($_POST['email']));
		$subject = addslashes(htmlspecialchars($_POST['subject']));
		$message = addslashes(htmlspecialchars($_POST['message']));
		
		$html .= "<form method=\"post\" action=\"contact/process.html\">\n";
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Contact:</div>\n";
		$html .= "We'd love to hear from you, please fill out the form below to sent us an email\n";
		$html .= "<p><font class=\"star\">*</font>indicates a required field</p>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Recipient:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<select name=\"who\">\n";
		/*this is going to be in a for loop*/
		$isSelected = "";	
		if($_POST['who'] == 0)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"0\" $isSelected>General Church</option>\n";
		$isSelected = "";	
		if($_POST['who'] == 1)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"1\" $isSelected>Bobby Ball</option>\n";
		
		$isSelected = "";	
		if($_POST['who'] == 2)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"2\" $isSelected>Brad Cook</option>\n";

		$isSelected = "";	
		if($_POST['who'] == 3)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"3\" $isSelected>Mark Pruitt</option>\n";

		
		$html .= "\t\t<option value=\"1\" $isSelected></option>\n";
		$html .= "\t\t<option value=\"1\" $isSelected></option>\n";
		$html .= "\t\t<option value=\"1\" $isSelected></option>\n";
		$html .= "\t\t</select><br />\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Your Name:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"name\" valeu=\"$name\" type=\"text\" maxlength=\"100\" size=\"25\" /></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Email Address:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"email\" value=\"$email\" type=\"text\" maxlength=\"100\" size=\"25\" /></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Subject:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"subject\" value=\"$subject\" type=\"text\" maxlength=\"100\" size=\"25\" /></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Message:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea wrap=\"soft\" name=\"message\" rows=\"16\" cols=\"55\">$message</textarea></div>\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "<input type=\"reset\" value=\"Reset Form\" />\n";
		$html .= "<input type=\"submit\" name=\"submitok\" value=\"Send Message\" />\n";
		$html .= "</form>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";

		return $html;
	}

	function validEmail() {
		$_POST['email'] = trim($_POST['email']);
		if (!eregi("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,6}$", $_POST['email']))
			return false;
		else 
			return true;
	}

	function process() {
		GLOBAL $config;
		$html = "";
		$who = $_POST['who'];
		if($who == "0")
   	     	$recipient = "bcook@sunbreakchurch.org";
		else if($who == "1")
   	     	$recipient = "bball@sunbreakchurch.org";
		else if($who == "2")
   	     	$recipient = "bcook@sunbreakchurch.org";
		else if($who == "3")
   	     	$recipient = "mpruitt@sunbreakchurch.org";
		else if($who == "4")
   	     	$recipient = "bball@sunbreakchurch.org";

		if(!isset($_POST['name']) ||
			!isset($_POST['email']) ||
			!isset($_POST['subject']) ||
			!isset($_POST['message'])) {
		
			$html .= "<p>Please check that all fields are filled out and try again.</p>\n";
			$html .= $this->index();
		} else if (!$this->validEmail()) {
			$html .= "<p>Please check the email address you entered, it does not appear valid.</p>\n";
			$html .= $this->index();
		} else {
			$domain = $config['domain'];
			$admin = $config['admin'];
			$name = addslashes(htmlspecialchars($_POST['name']));
			$email = addslashes(htmlspecialchars($_POST['email']));
			$subject = addslashes(htmlspecialchars($_POST['subject']));
			$message = addslashes(htmlspecialchars($_POST['message']));
		
			$msg = "Submitted by $name via form from $domain:\n\n";

			$msg .= $message . "\n\n";

			if (false) {
				$msg .= "Host: ".$REMOTE_HOST."\n";
				$msg .= "User: ". $REMOTE_USER."\n";
				$msg .= "Address: ". $REMOTE_ADDR."\n";
				$msg .= "Broswer: ". $HTTP_USER_AGENT."\n";
			}

			mail($recipient, $subject, $msg, "From: $email");

			$html .= "<p>Message sent successfully.</p>\n";
			$html .= $this->index();

		}
		return $html;
	}
}
?>
