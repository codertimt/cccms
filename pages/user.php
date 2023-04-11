<?php

include_once("pages/page.php");

class user extends page
{
	var $m_db;
	var $m_title;
	var $m_data;
	var $m_showLeftBlocks;
	var $m_showRightBlocks;
	var $m_minUserLevel;
	var	$m_function;

	function user($db) {
		GLOBAL $config;
		$this->m_db = $db;
		$this->m_page->category = 1;

		$this->page();
	}
	
	function getDisplayText() {
		$evalStr = "return is_callable(array('user', '" . $this->m_name . "'));";
		if(eval($evalStr) === false)
			return "<p>Error: User page not found.</p>";
	
		$evalStr = '$html .= $this->' . $this->m_name . '();';
		if(eval($evalStr) !== false)
			return $html;
		else
			return "<p>Error: User page not found.</p>";
	}
	
	//need to over load these since user 	
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
		if($_SESSION['userLevel'] >= 1) {
			$html = "<h3>Account Options</h3>\n";
			$html .= "<div>\n";
			$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"user/updateUser.html\">"
				. "<img src=\"images/icons/content.gif\" border=\"0\" /></a><br />"
				. "<a href=\"user/updateUser.html\">"
				. "Update User Info."
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
			$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"user/logout.html\">"
				. "<img src=\"images/icons/logout.gif\" border=\"0\" /></a><br />"
				. "<a href=\"user/logout.html\">"
				. "Logout"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
			$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"paypal/\">"
				. "<img src=\"images/icons/content.gif\" border=\"0\" /></a><br />"
				. "<a href=\"paypal/\">"
				. "Invoices/Paypal Payment Gateway"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
			$html .= "<div class=\"clearer\">&nbsp;</div>\n";
			$html .= "</div>\n";
		} else {
			$html = "<p>You do not have access to this area.</p>";
		}

		return $html;
	}

	function admin() {
		if($_SESSION['userLevel'] >= 3) {
			if($_POST['submitok'] == "Create User") {
				$html = $this->processCreateUser($_POST['userLevel']);
				$html .= "<p>User Created.</p>";
			}
			else if($_POST['submitok'] == "Update Info.") {
				$html = $this->processUpdateUser($_POST['userLevel']);
			}
			else if(isset($_POST['delete'])){
				$html = $this->deleteUser();	
			}
			
			$html .= $this->showAdmin();
		} else {
			$html = "<p>You do not have access to this area.</p>";
		}

		return $html;
		
	}

	function deleteUser() {
		GLOBAL $config;
		$sql = "delete from " . $config['tableprefix'] . "user where userid = '" . $_POST['uid'] . "'";
		if (!$this->m_db->runQuery($sql)) {
			$html .= '<p>A database error occurred in processing your '.
				"submission.\nIf this error persists, please ".
				'contact us.</p>';
		} else {
			$html .= "<p>User " . $_POST['uid'] . " deleted.";	
		}

		return $html;

	}

	function showAdmin() {
		GLOBAL $config;
		$html = "<h3>Add/Edit Users</h3>\n";

		$sql = "select userid, firstname, lastname from " . $config['tableprefix'].  "user where userid != 'Admin' order by lastname, firstname asc";
		$this->m_db->runQuery($sql);
		$numRows = $this->m_db->getNumRows();
		$html .= "<form id=\"addUsers\" method=\"post\" action=\"user/admin.html\">\n";
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">User Actions</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "<img src=\"images/html.gif\" alt=\"folder\" /> ";
		$html .= "<input class=\"button\" name=\"newUser\" value=\""; 
		$html .= "New User\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" />\n";
		$html .= "\t</div>\n";
		$html .= "<div class=\"formRow\">\n";
    	$html .= "\t\t<select name=\"editUser\">\n";
		$html .= "\t\t<option></option>\n";
		for($i=0; $i<$numRows; ++$i) {
			$user = $this->m_db->getRowObject();
			$html .= "\t\t<option value=\"". $user->userid . "\">" . $user->lastname . ", " . $user->firstname . " - " . $user->userid . "</option>\n";
		}
    	$html .= "\t\t</select>\n";
		$html .= "<input class=\"button\" name=\"newUser\" value=\""; 
		$html .= "Update User\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" />\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		if(isset($_POST['editUser']) && $_POST['editUser'] != "") {
			$sql = "select userid, firstname, lastname, email, bio, userLevel from " . $config['tableprefix'].  "user where userid = '" . $_POST['editUser'] . "'";
			$this->m_db->runQuery($sql);
			$numRows = $this->m_db->getNumRows();
			if($numRows == 1) {
				$user = $this->m_db->getRowObject();
				$uid = $user->userid;
				$fname = $user->firstname;
				$lname = $user->lastname;
				$email = $user->email;
				$bio = $user->bio;
				$userLevel = $user->userLevel;

				$html .= $this->updateUserFormText($uid, $fname, $lname, $email, $bio, $userLevel);
			} else {
				$html .= "<p>Error Retrieving User information. Create New User?</p>";
				$html .= $this->createUserFormText(1);
			}
		}
		else 
			$html .= $this->createUserFormText(1);
		$html .= "</form>\n";

		return $html;
		

	}

	function userLevelText($userLevel) {
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>User Level:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<select name=\"userLevel\">\n";
		$isSelected = "";	
		if($userLevel == '1')
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"1\" $isSelected>Registered User</option>\n";
		$isSelected = "";	
		if($userLevel == '2')
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"2\" $isSelected>Content Administrator</option>\n";
		$isSelected = "";	
		if($userLevel == '3')
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"3\" $isSelected>Site Administrator</option>\n";
		$html .= "\t\t</select><br />\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		

		return $html;
	}

	function updateUser() {
		if($_SESSION['userLevel'] >= 1) {
			if(isset($_GET['action'])) {
				if($_GET['action'] == "process") {
					$html = $this->processUpdateUser(0); 
					$html .= $this->updateUserForm();
				}
			} else {
				$html = $this->updateUserForm();
			}
		} else {
			$html = "<p>You do not have access to this area.</p>";
		}

		return $html;
	}
	
	function updateUserForm() {
		
		$uid = $_SESSION['uid'];
		$fname = $_SESSION['firstName'];
		$lname = $_SESSION['lastName'];
		$email = $_SESSION['email'];
		$bio = $_SESSION['bio'];

		$html = "<p>Fill out any or all fields in the following form to update your user information.  Any blank fields will be ignored.</p>";
		$html .= "<form method=\"post\" action=\"user/process_updateUser.html\">\n";
		$html .= $this->updateUserFormText($uid, $fname, $lname, $email, $bio, 0);
		$html .= "</form>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		
		return $html;
	}

	function updateUserFormText($uid, $fname, $lname, $email, $bio, $userLevel) {

		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Update User Info.</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>User ID:</div>\n";
		$html .= "\t</div>\n";
	    $html .= "<input name=\"uid\" type=\"hidden\" value=\"" . $uid ."\"  />\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<div>$uid</div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>New Password:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"newpass\" type=\"password\" maxlength=\"100\" size=\"22\" /></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Repeat Password:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"newpass2\" type=\"password\" maxlength=\"100\" size=\"22\" /></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>First Name:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"newfname\" value=\"$fname\" type=\"text\" maxlength=\"100\" size=\"25\" /></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Last Name</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"newlname\" value=\"$lname\"type=\"text\" maxlength=\"100\" size=\"25\" /></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Email Address</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"newemail\" value=\"$email\" type=\"text\" maxlength=\"100\" size=\"42\" /></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Other Info</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea wrap=\"soft\" name=\"newbio\" rows=\"5\" cols=\"42\">$bio</textarea></div>\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		if($userLevel)
			$html .= $this->userLevelText($userLevel);
		$html .= "</div>\n";
		$html .= "<input type=\"reset\" value=\"Reset Form\" />\n";
		$html .= "<input type=\"submit\" name=\"submitok\" value=\"Update Info.\" />\n";
		if($userLevel)
			$html .= "<input type=\"submit\" name=\"delete\" value=\"Delete User\" />\n";
		return $html;
	}

	function processUpdateUser($userLevel) {
		GLOBAL $config;

		if($userLevel>0)
			$uid = $_POST['uid'];
		else
			$uid = $_SESSION['uid'];
		$fname = $_SESSION['firstName'];
		$lname = $_SESSION['lastName'];
		$email = $_SESSION['email'];
		$bio = $_SESSION['bio'];
	
		if(isset($_POST['newpass']) && $_POST['newpass'] != "") {
			if(!isset($_POST['newpass2']) 
					|| $_POST['newpass2'] != $_POST['newpass']) {
				$html = "<p>Passwords do not match.</p>";
				return $html;
			}
		} 

		$newpass = addslashes(htmlspecialchars($_POST['newpass']));
		$newpass2 = addslashes(htmlspecialchars($_POST['newpass2']));
		$newfname = addslashes(htmlspecialchars($_POST['newfname']));
		$newlname = addslashes(htmlspecialchars($_POST['newlname']));
		$newemail = addslashes(htmlspecialchars($_POST['newemail']));
		$newbio = addslashes(htmlspecialchars($_POST['newbio']));

		$needComma = false;
		$sql = "UPDATE " . $config['tableprefix'] . "user SET ";
		if(strlen($newpass) != 0) {
			$sql .= "pass = SHA1('$newpass')";
			$needComma = true;
		}
		if(strlen($newfname) != 0) {
			if($needComma)
				$sql .= ",";
			else
				$needComma = true;
			$sql .= "firstname = '$newfname'";
		}
		if(strlen($newfname) != 0) {
			if($needComma)
				$sql .= ",";
			else
				$needComma = true;
			$sql .= "lastname = '$newlname'";
		}
		if(strlen($newfname) != 0) {
			if($needComma)
				$sql .= ",";
			else
				$needComma = true;
			$sql .= "email = '$newemail'";
		}
		if(strlen($newfname) != 0) {
			if($needComma)
				$sql .= ",";
			$sql .= "bio = '$newbio'";
		}
		if($userLevel > 0) {
			if($needComma)
				$sql .= ",";
			$sql .= "userLevel = $userLevel";
		}
		$sql .= " where userid='$uid'";

		if($needComma == false) { //we never set anything to be updated
			$html .= "<p>No new information set, update Aborted.</p>";
		} else if (!$this->m_db->runQuery($sql)) {
			$html .= 'A database error occurred in processing your '.
				"submission.\nIf this error persists, please ".
				'contact us.';
		} else {
			$html .= "<p>User information updated</p>";
			$_SESSION['firstName'] = $newfname;
			$_SESSION['lastName'] = $newlname;
			$_SESSION['email'] = $newemail;
			$_SESSION['bio'] = $newbio;
		}

		return $html;
	}

	function createUser() {
		if(isset($_GET['action'])) {
			if($_GET['action'] == "process") {
				$html = $this->processCreateUser(0); 
			}
		} else {
			$html = $this->createUserForm();
		}

		return $html;
	}

	function createUserForm() {
		$html .= "<p>Fill out the following form to create a user account.  Your password will be emailed to you at the email address provided.</p>\n";
		$html .= "<p><font class=\"star\">*</font>indicates a required field</p>\n";
		$html .= "<form method=\"post\" action=\"user/process_createUser.html\">\n";

		$html .= $this->createUserFormText(0);
		$html .= "</form>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		
		return $html;

	}

	function createUserFormText($userLevel) {
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Create New User</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>User ID</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<div><input name=\"newid\" type=\"text\" maxlength=\"100\" size=\"25\" /><font class=\"star\">*</font></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>First Name</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"newfname\" type=\"text\" maxlength=\"100\" size=\"25\" /><font class=\"star\">*</font></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Last Name</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"newlname\" type=\"text\" maxlength=\"100\" size=\"25\" /><font class=\"star\">*</font></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Email Address</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><input name=\"newemail\" type=\"text\" maxlength=\"100\" size=\"25\" /><font class=\"star\">*</font></div>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Other Info</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea wrap=\"soft\" name=\"newnotes\" rows=\"5\" cols=\"30\">$bio</textarea></div>\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		if($userLevel>0)
			$html .= $this->userLevelText($userLevel);
		$html .= "</div>\n";
		$html .= "<input type=\"reset\" value=\"Reset Form\" />\n";
		$html .= "<input type=\"submit\" name=\"submitok\" value=\"Create User\" />\n";
		return $html;
	}

	function processCreateUser($userLevel) {
		GLOBAL $config;
		$html = "<h3>Processing new user registration...</h3>\n";  
		if ($_POST['newid']=='' || $_POST['newfname']==''
		    || $_POST['newlname']=='' || $_POST['newemail']=='') {
	       $html .= 'One or more required fields were left blank. ' .
   		         	'Please <a href="user/createUser.html">return to the form</a>' .
					' fill out the missing fields and try again.';
			return $html;
		}

		// Check for existing user with the new id
		$sql = "SELECT COUNT(*) FROM " . $config['tableprefix'] . "user WHERE userid = '$_POST[newid]'";
 	  	$this->m_db->runQuery($sql);
		$row = $this->m_db->getNextRow();
		
		if ($row[0] > 0) {
			$html .= "A user already exists with your chosen userid.\n".
            	  	 'Please try another.';
		} else {

	 		$newpass = substr(md5(time()),0,6);
			$newuser = addslashes(htmlspecialchars($_POST['newid']));
			$newfname = addslashes(htmlspecialchars($_POST['newfname']));
			$newlname = addslashes(htmlspecialchars($_POST['newlname']));
			$newemail = addslashes(htmlspecialchars($_POST['newemail']));
			$newbio = addslashes(htmlspecialchars($_POST['newbio']));
	
			$sql = "INSERT INTO " . $config['tableprefix'] . "user SET
   		            userid = '$newuser',
   	    	        pass = SHA1('$newpass'),
            	   firstname = '$newfname',
    	           lastname = '$newlname',
       	    	    email = '$newemail',
       		        bio = '$newnotes'";
			if($userLevel>0)
				$sql .= ",userLevel = '$userLevel'";
		
			if (!$this->m_db->runQuery($sql)) {
    	   		$html .= 'A database error occurred in processing the '.
        	   		  	 "submission.\nIf this error persists, please ".
          			  	 'contact us.';
			} else {
				$domain = $config['domain'];
				$admin = $config['admin'];

				$html .= "Processing complete.  An email containing login information has been sent to the address entered.";

				$msg = "You are someone else has registered a user account with this email address at $domain, and we welcome you.  Your login information can be found below.

	User ID:  $_POST[newid]
	Password: $newpass

You can change your password once you have logged in.";

				mail($_POST['newemail'], "Password for $domain", $msg, "From: $admin@$domain");
			}
		}
	
		return $html;
	}

	function login() {	
		if(isset($_GET['action'])) {
			if($_GET['action'] == "process") {
				$html = $this->processLogin(); 
			}
		} else {
			$html = $this->loginForm();
		}

		return $html;
	}
	function loginForm() {
		if(isset($_SESSION['uid'])) {
			$html = "<p>You are currently logged in as " . $_SESSION['uid']
					. ".  If this is not you or you would like to end your "
					. "session, click <a href=\"user/logout.html\">"
					. "here to logout</a>.</p>\n";
		} else {
			$html .= "<p>Login with your username and password below, or if you do not have an account, feel free to <a href=\"user/createuser.html\">create an account</a>.</p>\n";
			$html .= "<form  method=\"post\" action=\"user/process_login.html\">\n";
			$html .= "<div>\n";
			$html .= "<div class=\"group\">\n";
			$html .= "<div class=\"groupTitle\">Login</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
	   	 	$html .= "\t\tUserName:\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
   		 	$html .= "\t\t<input class=\"login\" name=\"uid\" type=\"text\" />\n";
			$html .= "\t</div>\n";
//			$html .= "<div class=\"clearer\">&nbsp;</div>\n";
			$html .= "</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
   		 	$html .= "\t\tPassword:\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
   		 	$html .= "\t\t<input class=\"login\" name=\"pwd\" type=\"password\" />\n";
			$html .= "\t</div>\n";
//			$html .= "<div class=\"clearer\">&nbsp;</div>\n";
			$html .= "</div>\n";
			
			$html .= "</div>\n";
		
   			$html .= "<input type=\"submit\" value=\"Log in\" />\n"; 
			$html .= "</div>\n";
			$html .= "</form>\n";
		}	
		return $html;
	}
	
	function processLogin() {
		GLOBAL $config;
		$uid = isset($_POST['uid']) ? $_POST['uid'] : $_SESSION['uid'];
		$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : $_SESSION['pwd'];
	
		if(!isset($uid) || $uid == '' || $pwd == '') {
			$html = "<p>One or more fields left blank, please enter below.</p>";
			$html .= $this->loginForm();
			
			return $html;
		}

		//previous logic assures us that at least SESSION is set
		if($_POST['uid'] == $_SESSION['uid'] || !isset($_POST['uid'])) {
			$html = "<p>You are currently logged in as " . $_SESSION['uid']
					. ".  If this is not you or you would like to end your "
					. "session, click <a href=\"user/logout.html\">"
					. "here to logout</a>.</p>\n";
			return $html;
		} else {
			$uid = addslashes($uid);
			$pwd = addslashes($pwd);



			$sql = "SELECT * FROM ". $config['tableprefix'] . "user WHERE
   	    			userid = '$uid' AND pass = SHA1('$pwd')";
		
			$result = $this->m_db->runQuery($sql);
		
			if (!$result) {
 				$html = 'A database error occurred while checking your '.
				         'login details. If this error persists, please '.
   	    				 'contact us.';
				return $html;
			}

			if ($this->m_db->getNumRows() == 0) {
				$html = "<p>Login failed.  Either User ID or password is incorrect. Please try again.</p>\n";
				$html .= $this->loginForm();

				return $html;
			}

			$this->setSessionData($this->m_db->getRowObject());

			$html = "<p>Login Complete.  Welcome " . $_SESSION['firstName'] . ". If you would like you can continue on to your <a href=\"user/index.html\">User Account page</a>.</p>\n";

			return $html;
		}
	}

	function logout() {
		$_SESSION = array();
		session_destroy();
		unset($_COOKIE[session_name()]);
		$_SESSION['userLevel'] = 0;

		$html = "<h3>Logging out...</h3>\n";
		$html .= "Logout complete.  You can now <a href=\"user/login.html\">log back in</a> or use the unrestricted areas of the site";

		return $html;
	}	

	function setSessionData($dbuser) {
		$_SESSION['uid'] = $dbuser->userid;
		$_SESSION['firstName'] = $dbuser->firstname;
		$_SESSION['lastName'] = $dbuser->lastname;
		$_SESSION['email'] = $dbuser->email;
		$_SESSION['bio'] = $dbuser->bio;
		$_SESSION['userLevel'] = $dbuser->userLevel;
	}
}
?>
