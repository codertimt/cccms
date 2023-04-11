<?php

include_once("pages/page.php");
include_once("classes/file.php");

class admin extends page 
{

	var $m_adminClass;

	function admin($db) {
		GLOBAL $config;
		$this->m_db = $db;
		
		$this->page();
		
		$this->m_pageType = "admin";
	}

	function getPageCategory() {
		return 1;
	}

	function getDisplayText() {
		GLOBAL $config;
		GLOBAL $extraHead;
		GLOBAL $bodyOnLoad;
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"themes/" . $config['themeName'] . "/scroller.js\"></script>\n";
		$bodyOnLoad .= "onload=\"scrollIt()\"";
		
		if($_SESSION['userLevel'] >= 3) {
			$evalStr = '$html .= $this->' . $this->m_name . '();';
			eval($evalStr);
			$html .="<form>\n";
	    	$html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";
			$html .="</form>\n";
		} else {
			$html = "<p>You do not have access to this page.</p>";
		}

		return $html;
	}	
	//need to over load these since user 	
	function hideLeftBlocks() {
		return 0;
	}
	
	function hideRightBlocks() {
		return 1;
	}

	function getPageTitle() {
		return $this->m_name;
	}

	function index() {
		$html = "<h3>Administration Tools</h3>\n";
		$html .= "<div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"content/admin.html\">"
				. "<img src=\"images/icons/content.gif\" border=\"0\" /></a><br />"
				. "<a href=\"content/admin.html\">"
				. "Content Pages"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"news/admin.html\">"
				. "<img src=\"images/icons/news.gif\" border=\"0\" /></a><br />"
				. "<a href=\"news/admin.html\">"
				. "News Headlines & Pages"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"events/admin.html\">"
				. "<img src=\"images/icons/events.gif\" border=\"0\" /></a><br />"
				. "<a href=\"events/admin.html\">"
				. "Event Calendar"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"admin/categories.html\">"
				. "<img src=\"images/icons/categories.gif\" border=\"0\" /></a><br />"
				. "<a href=\"admin/categories.html\">"
				. "Site Categories"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"admin/menus.html\">"
				. "<img src=\"images/icons/menus.gif\" border=\"0\" /></a><br />"
				. "<a href=\"admin/menus.html\">"
				. "Menu Items"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"gallery/admin.html\">"
				. "<img src=\"images/icons/gallery.gif\" border=\"0\" /></a><br />"
				. "<a href=\"gallery/admin.html\">"
				. "Photos & Galleries"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"admin/media.html\">"
				. "<img src=\"images/icons/media.gif\" border=\"0\" /></a><br />"
				. "<a href=\"admin/media.html\">"
				. "Website Media and Downloads"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"user/admin.html\">"
				. "<img src=\"images/icons/users.gif\" border=\"0\" /></a><br />"
				. "<a href=\"user/admin.html\">"
				. "User Accounts"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"admin/email.html\">"
				. "<img src=\"images/icons/email.gif\" border=\"0\" /></a><br />"
				. "<a href=\"admin/email.html\">"
				. "Email Accounts"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"adminIconOuter\">"
				. "<div class=\"adminIconInner\">"
				. "<a href=\"admin/blocks.html\">"
				. "<img src=\"images/icons/categories.gif\" border=\"0\" /></a><br />"
				. "<a href=\"admin/blocks.html\">"
				. "Site Blocks"
				. "</a>\n"
				. "</div>\n"
				. "</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";

		return $html;
	}

	function email() {
		GLOBAL $config;
		$html = "<h3>Add/Edit Email Accounts</h3>\n";
		
		$html .= "<p><font class=\"star\">***</font>Important: Please Read<font class=\"star\">***</font></p>\n";
		$html .= "<p>Email accounts are seperate entities from the user accounts on your web site. As such, they must be setup from within the main server control panel.  You should see a login dialog which needs to be populated with your Site Administration username and password.  This information is different from that which you are currently logged in with and should be provided to you.</p>\n";

		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Email Accounts</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "<iframe width=\"95%\" height=\"600px\" src=\"http://" . $config['domain'] . ":2082/frontend/simskins/mail/pops.php\" style=\"background:#ffffff\"></iframe>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		return $html;
	}

	function categories() {
		if(isset($_POST['delete'])) {
			$_POST['scrollPos'] = 0;
			if(isset($_POST['confirm']))
				$html .= $this->processCategories();
			else if(isset($_POST['deny'])) {
				$html .= "<p>Delete Cancelled</p>\n";	
				unset($_POST['delete']);
				$html .= $this->categories();
			} else {
				$html .= $this->confirmDelete($_SERVER['SCRIPT_URL']);
				return $html;
			}
		} else if(isset($_POST['cancel'])
				|| isset($_POST['submitadd'])
				|| isset($_POST['submitedit'])) {
			$_POST['scrollPos'] = 0;
			$html .= $this->processCategories();
		} else {
			$html = "<h3>Add/Edit Categories</h3>\n";
			$html .= "<form enctype=\"multipart/form-data\" id=\"addCat\" method=\"post\" action=\"" . $_SERVER['SCRIPT_URL'] . "\" onsubmit=\"setScrollPos();\">\n";
	    	$html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";
			//read db and get categories as array of category objects
			$categories = new treeItems($this->m_db, "cat", "category");		
			//$this->m_adminClass = $categories;
			$html .= $categories->getTreeItemsAndItemsForm($categories);
			$categories->processPostArray($activeFolder, $categories,
										$categories->getPostArray());	
			
			$fileName = "images/catIcons/" . $_POST['updateId'] . $_POST['catIconExt'];

			$file = new file();
			$outPath = $_SERVER["DOCUMENT_ROOT"] . "/js/myfiles";
			$images = $file->createTinyMCEImageList("images", $outPath, $_POST['updateId'], false);
			
			$categories->setType("parentCat");
			$catHtml = $categories->getParentCatsForm($activeFolder, $categories);

			if(isset($_POST['updateId'])) {
	   	 		$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
	   	 		$html .= "<input name=\"catIconExt\" type=\"hidden\" value=\"" . $_POST['catIconExt'] ."\"  />\n";
			}
			$html .= "<div>\n";
			$html .= "<div class=\"group\">\n";
			$html .= "<div class=\"groupTitle\">Add/Edit</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
	    	$html .= "\t\t<div>Category Name:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
	   	 	$html .= "\t\t<input name=\"tItemName\" type=\"text\" value=\"" . $_POST['tItemName'] ."\" maxlength=\"100\" size=\"60\" />\n";
			$html .= "\t</div>\n";
			$html .= "<div class=\"clearer\">&nbsp;</div>\n";
			$html .= "</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
   		 	$html .= "\t\t<div>Parent Category:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry formRowWhite\">\n";
			$html .= $catHtml;
			$html .= "\t</div>\n";
			$html .= "</div>\n";
			$html .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"" . 200*1024 . "\" />";
			$html .= "</div>\n";
			

			$html .= "<div class=\"group\">\n";
			$html .= "<div class=\"groupTitle\">Advanced</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
   		 	$html .= "\t\t<div>Display of Content:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
			$checked = "";	
			if($_POST['subSite'] == 1)
				$checked = " checked=\"on\" ";
			$html .= "\t\t<input name=\"subSite\" type=\"checkbox\"";
			$html .= "$checked>Function as independent subsite.</input>";
			$html .= "<h6>If selected this category will act as an independent site.  It will no longer display content/news/events from its parent or sibling categories. Only content from itself and its children will be displayed.</h6>";
			$html .= "\t</div>\n";
			$html .= "</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
   		 	$html .= "\t\t<div>Category Visuals:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
			$checked = "";	
			if($_POST['ownMenu'] == 1)
				$checked = " checked=\"on\" ";
			$html .= "\t\t<input name=\"ownMenu\" type=\"checkbox\"";
			$html .= "$checked>Override Main Menu</input>";
			$html .= "<h6>Select for the ability to completely replace the Main Menu with a category specific one.</h6>";
			$html .= "\t</div>\n";
			$html .= "</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
   		 	$html .= "\t\t<div></div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
	   	 	$html .= "\t\tOverride Site Theme <input name=\"ownTheme\" type=\"text\" value=\"" . $_POST['ownTheme'] ."\" maxlength=\"30\" size=\"40\" />\n";
			$html .= "<h6>Enter a theme name to override the site theme for this category and its subcategories.  Currently a name will have to be provided to you, in the future you will be able to choose from our available themes.</h6>";
			$html .= "\t</div>\n";
			$html .= "</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
   		 	$html .= "\t\t<div>Upload Icon:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
    		$html .= "\t\t<input name=\"catIcon\" type=\"file\" maxlength=\"100\" size=\"50\" />\n";
			$html .= "\t</div>\n";
			$html .= "</div>\n";
			
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
   		 	$html .= "\t\t<div>Or Choose Icon:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
    		$html .= "\t\t<select name=\"catIconDD\" type=\"select\">\n";
				$html .= "\t\t\t<option value=\"\"></option>\n";
			foreach($images as $image) {
				$html .= "\t\t\t<option value=\"images/$image\">$image</option>\n";
			}
			$html .= "\t\t</select>\n";
			$html .= "<h6>You may either upload an image directly via the Broswe button or select from an image already on the server from the dropdown list.</h6>";
			$html .= "\t</div>\n";
			$html .= "</div>\n";
			$html .= "<img src=\"$fileName\" alt=\"No Icon Associated\" />\n";
			$html .= "</div>\n";

			if(isset($_POST['updateId']))
				$html .= "<input type=\"submit\" name=\"submitedit\" value=\"Submit Category Edit\" />\n";
			else
				$html .= "<input type=\"submit\" name=\"submitadd\" value=\"Add New Category\" />\n";
			$html .= "<input type=\"submit\" name=\"delete\" value=\"Delete Category\" />\n";
			$html .= "</div>\n";
			$html .= "</form>\n";

		}
		return $html;
	}

	function processCategories() {
		GLOBAL $config;
		if(isset($_POST['cancel'])) {
			$html = "<p>Add/Edit cancelled.</p>";
			unset($_POST['cancel']);
			$html .= $this->categories();
		} else if(isset($_POST['delete'])) {
			$inUseBy = array();
			$sql = "select category from " . $config['tableprefix'] . "content where category = '" . $_POST['updateId'] . "'";
			if ($this->m_db->runQuery($sql)) {
				if($this->m_db->getNumRows() > 0)
					array_push($inUseBy, "Content Pages");
			}
			$sql = "select category from " . $config['tableprefix'] . "events where category = '" . $_POST['updateId'] . "'";
			if ($this->m_db->runQuery($sql)) {
				if($this->m_db->getNumRows() > 0)
					array_push($inUseBy, "Events");
			}
			$sql = "select category from " . $config['tableprefix'] . "news where category = '" . $_POST['updateId'] . "'";
			if ($this->m_db->runQuery($sql)) {
				if($this->m_db->getNumRows() > 0)
					array_push($inUseBy, "News Items");
			}
			$sql = "select category from " . $config['tableprefix'] . "gallery where category = '" . $_POST['updateId'] . "'";
			if ($this->m_db->runQuery($sql)) {
				if($this->m_db->getNumRows() > 0)
					array_push($inUseBy, "Galleries");
			}
			$sql = "select category from " . $config['tableprefix'] . "siteMedia where category = '" . $_POST['updateId'] . "'";
			if ($this->m_db->runQuery($sql)) {
				if($this->m_db->getNumRows() > 0)
					array_push($inUseBy, "Content Page Media");
			}
			$sql = "select category from " . $config['tableprefix'] . "menuItems where category = '" . $_POST['updateId'] . "'";
			if ($this->m_db->runQuery($sql)) {
				$numRows = $this->m_db->getNumRows();
				if($numrows > 1) {
					array_push($inUseBy, "Menu Items");
				} else if($numRows == 1 && sizeof($inUseBy) ==0) {
					$sql = "delete from " . $config['tableprefix'] . "menuItems where category = '" . $_POST['updateId'] . "' and parent = -1";
					if (!$this->m_db->runQuery($sql)) 
						$html .= "<p>Error removing Main Menu item for category.</p>";
				}
			}

			$sql = "select iconExt from " . $config['tableprefix'] . "categories where id = '" . $_POST['updateId'] . "'";
			if ($this->m_db->runQuery($sql)) {
				if($this->m_db->getNumRows() > 0) {
					$row = $this->m_db->getRowObject();
					$ext = $row->iconExt;
				}
			}
			
			if($_POST['updateId'] == 1) {
				$html .= "<p>Root tree Item cannot be changed.</p>";
				unset($_POST['delete']);
				$html .= $this->categories();
			} else if(sizeof($inUseBy) != 0) {
				$html .= "Category cannot be deleted.  It is still in use by ";
				foreach($inUseBy as $thing) {
					$html .= "$thing, ";
				}
				unset($_POST['delete']);
				unset($_POST['updateId']);
				$html .= $this->categories();
					
			} else {
				$sql = "delete from " . $config['tableprefix'] . "categories where id = '" . $_POST['updateId'] . "'";
				if (!$this->m_db->runQuery($sql)) {
					$html = '<p>A database error occurred in processing your '.
						"submission.\nIf this error persists, please ".
						'contact us.</p>';
				} else {
					$html = "<p>Page deleted.</p>";
					if($ext != "") {
						$id = $_POST['updateId'];
						unlink("images/catIcons/".$id.$ext);
						unlink("images/catIcons/t_".$id.$ext);
					}
					unset($_POST['delete']);
					unset($_POST['updateId']);
					unset($_POST['tItemName']);
					unset($_POST['parentCatActive']);
					$html .= $this->categories();
				}
			}
		} else {
			if($_POST['tItemName'] == "") {
				$html .= "<p>Please enter a category name.</p>";
			} else if($_POST['parentCatActive'] == "") {
				$html .= "<p>Please select a parent category.</p>";
			} else { 

				$name = addslashes(htmlspecialchars($_POST['tItemName']));
				$parent = $_POST['parentCatActive'];
				if(isset($_POST['updateId']))
					$id = addslashes(htmlspecialchars($_POST['updateId']));
				else
					$id = -1;

				if($_POST['ownMenu'] == 'on')
					$ownMenu = 1;
				else
					$ownMenu = 0;
				
				if($_POST['subSite'] == 'on')
					$subSite = 1;
				else
					$subSite = 0;
				$theme = addslashes(htmlspecialchars($_POST['ownTheme']));

				$duplicate = $this->m_db->isDuplicateName("name", $name, 
						$config['tableprefix']."categories", 
						"parent", $parent);
				if($duplicate && $_POST['submitadd']) {
					$html .= "<p>An category with the same name already exists in this category.\nPlease choose another name.</p>";
				} else {
					if($id == 1) {
						$html .= "<p>Root tree Item cannot be changed.</p>";
					} else 	if($parent == $id) {
						$html .= "<p>A category cannot specify itself as a parent category.</p>";
					} else {	
						$ext = "";
						if(sizeof($_FILES) > 0 && $_FILES['catIcon']['name'] != "") {
							$filePath = $_SERVER['DOCUMENT_ROOT'] . "/images/catIcons/";
							$newFile = $_FILES['catIcon'];
							$ext = substr($newFile['name'], strrpos($newFile['name'], "."));
						} else if($_POST['catIconDD'] != "") {
							$filePath = $_SERVER['DOCUMENT_ROOT'] . "/images/catIcons/";
							$ext = substr($_POST['catIconDD'], strrpos($_POST['catIconDD'], "."));
						}

						if($_POST['submitadd']) {	
							//add main menu item for the category toggle adminActive to make available
							$menuId = $this->getNextMenuId(0);
							$sql = "INSERT INTO ". $config['tableprefix'] . "menuItems SET
									name = 'Main Menu for $name',
									 active = '0',
									 menuId = '$menuId',
									 editable = '0',
									 adminActive = $ownMenu";
							if (!$this->m_db->runQuery($sql)) {
								$html .= "<p>An error occured adding a Main Menu for the category.</p>";
							} else {
								$menuId = $this->m_db->getLastInsertId();
							}
							$sql = "INSERT INTO ". $config['tableprefix'] . "categories SET
									name = '$name',
									parent = '$parent',
									iconExt = '$ext',
									ownMenu = $ownMenu,
									themeName = '$ownTheme',
									hideParentItems = $subSite,
									menuId = $menuId";

							$added = "Category $name added.";
						} else {
							$sql = "UPDATE ". $config['tableprefix'] . "categories SET
									name = '$name',
							        parent = '$parent',
									iconExt = '$ext',
									themeName = '$ownTheme',
									hideParentItems = $subSite,
									ownMenu = $ownMenu
									where id = '" . $_POST['updateId'] . "'";
							$added = "Category $name updated.";

							$category = $_POST['updateId'];
						}
						if (!$this->m_db->runQuery($sql)) {
							$html = '<p>A database error occurred in processing your '.
								"submission.\nIf this error persists, please ".
								'contact us.</p>';

						} else {
							if($_POST['submitadd']) {
								$category = $this->m_db->getLastInsertId();
								$_POST['updateId'] = $category;

								if($menuId != -1) {
									$sql = "UPDATE ". $config['tableprefix'] . "menuItems SET
											category = '$category'
											where id = '$menuId'";
									if (!$this->m_db->runQuery($sql)) 
										$html .= "<p>An error occured updating menu id.</p>";
								}
								$sql = "UPDATE ". $config['tableprefix'] . "categories SET
										menuId = '$menuId'
										where id = '$category'";
								if (!$this->m_db->runQuery($sql)) 
									$html .= "<p>An error occured update menu id in category.</p>";
					
							} else { //update 
								$category = $_POST['updateId'];
								$sql = "UPDATE ". $config['tableprefix'] . "menuItems SET
										adminActive = $ownMenu 
										where category = '$category' and parent = -1";
								if (!$this->m_db->runQuery($sql)) 
									$html .= "<p>An error occured updating Main Menu.</p>";
								
							}

							if(isset($_FILES['catIcon']['name']) && $_FILES['catIcon']['name'] != "") {
								include_once("classes/media.php");
								$newFile['name'] = $category . $ext;
								$newMedia = new media($this->m_db, 
										$filePath, 
										20*1024*1024, 
										$newFile,
										-1,
										true);
								$html = $newMedia->processUpload();

								$sql = "select iconExt from " 
									. $config['tableprefix'] 
									. "categories where id = " . $_POST['updateId'];

								$this->m_db->runQuery($sql);
								$editItem = $this->m_db->getRowObject();
								$_POST['catIconExt'] = $editItem->iconExt;

							} else if($_POST['catIconDD'] != "") {
								if($_POST['submitadd'])
									$_POST['updateId'] = $this->m_db->getLastInsertId();

								copy($_POST['catIconDD'], $filePath.$category.$ext);
								copy($_POST['catIconDD'], $filePath."t_".$category.$ext);
								$_POST['catIconExt'] = $ext;
							}
							$html .= "<p>$added</p>";
						}
					}
				}
					unset($_POST['submitadd']);
					unset($_POST['submitedit']);
					$html .= $this->categories();
			}
		}

		return $html;

	}

	function isItemEditable($table, $item) {
		GLOBAL $config;
		$sql = "select editable from " . $config['tableprefix'] . $table . " where id ='" . $item . "';";
		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();
		if($numItems != 1)
			return -1;

		$dbItem = $this->m_db->getRowObject();
		return $dbItem->editable;
	}
	
	function getMenuId($parent) {
		GLOBAL $config;
		$sql = "select menuId from " . $config['tableprefix'] . "menuItems where id ='" . $parent . "';";
		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();

		if($numItems == 1) {
			$item = $this->m_db->getRowObject();
			$id = $item->menuId;
		} else {
			$id = -1;
		}	
		return  $id;
	}
	
	function getNextMenuId($parent) {
		GLOBAL $config;
		$sql = "select menuId from " . $config['tableprefix'] . "menuItems order by menuId desc limit 0,1";
		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();

		if($numItems == 1) {
			$item = $this->m_db->getRowObject();
			$id = $item->menuId+1;
		} else {
			$id = -1;
		}	
		return  $id;
	}

	function processMenuOrder($location, $parent, $active, $name) {
		GLOBAL $config;
		$sql = "select name, orderNum from " . $config['tableprefix'] . "menuItems where parent ='" . $parent . "'and id != '" . $active . "' order by orderNum;";
		
		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();
		$items = array();	
		if($location != "none") {
			//update all items with new order
			if($location == "first")
				array_push($items, $name);
			for($i=0; $i<$numItems; ++$i) {
				$item = $this->m_db->getRowObject();
				array_push($items, $item->name);
				if($item->name == $location)
					array_push($items, $name);
			}

			$orderNum = 0;
			foreach($items as $item) {
				$sql = "UPDATE ". $config['tableprefix'] . "menuItems SET
						orderNum = '$orderNum'
						where name = '" . $item . "'";
				if (!$this->m_db->runQuery($sql)) {
					$html = '<p>A database error occurred in processing your '.
						"submission.\nIf this error persists, please ".
						'contact us.</p>';
				} else {
					$html = "<p>Item order set correctly.</p>";
				}
				++$orderNum;
			}
					
		} else {
			//if we just added and "none" force to be last
			if($_POST['submitadd']) {
				$sql = "UPDATE ". $config['tableprefix'] . "menuItems SET
						orderNum = '$numItems'
						where name = '" . $name. "'";
				if (!$this->m_db->runQuery($sql)) {
					$html = '<p>A database error occurred in processing your '.
						"submission.\nIf this error persists, please ".
						'contact us.</p>';
				} else {
					$html = "<p>Item order set correctly.</p>";
				}
			}
		}	

		return $html;
	}

	function processMenus(){
		GLOBAL $config;
		if(isset($_POST['updateId']) && $this->isItemEditable("menuItems", $_POST['updateId']) != 1) {
			$html = "Current item is either a grouping tree entry or a menu block name, editing is not allow.";
			$html .= $this->menus();
		} else {
			if(isset($_POST['cancel'])) {
				$html = "<p>Add/Edit cancelled.</p>";
				unset($_POST['cancel']);
				$html .= $this->categories();
			} else if(isset($_POST['delete'])) {
				$sql = "delete from " . $config['tableprefix'] . "menuItems where id = '" . $_POST['updateId'] . "'";
				if (!$this->m_db->runQuery($sql)) {
					$html = '<p>A database error occurred in processing your '.
						"submission.\nIf this error persists, please ".
						'contact us.</p>';
				} else {
					$html = "<p>Menu Item deleted.</p>";
					unset($_POST['delete']);
					unset($_POST['updateId']);
					unset($_POST['tItemName']);
					unset($_POST['menuParent']);
					unset($_POST['menuCat']);
					unset($_POST['catActive']);
					$html .= $this->menus();
				}
			} else {
				if(!isset($_POST['parentCatActive'])) {
					$html .= "<p>Please select a menu category.</p>";
				} 

				if(!isset($_POST['menuCatActive'])) {
					$html .= "<p>Please select a parent menu.</p>";
				} 


				$name = addslashes(htmlspecialchars($_POST['tItemName']));
				$parent = $_POST['parentMenuActive'];
				$menuId = $this->getMenuId($parent);	
				$category = $_POST['parentCatActive'];
				if($_POST['menuIsActive'] == 'on')
					$menuIsActive = 1;
				else
					$menuIsActive = 0;
				$minUserLevel = $_POST['minUserLevel'];
				$maxUserLevel = $_POST['maxUserLevel'];
				if(isset($_POST['updateId']))
					$id = addslashes(htmlspecialchars($_POST['updateId']));
				else
					$id = -1;
				$link = addslashes(htmlspecialchars($_POST['menuLink']));

				if($id == 1) {
					$html .= "<p>Root tree items cannot be changed.</p>";
				} else 	if($parent == $id) {
					$html .= "<p>A menu item cannot specify itself as a parent menu item.</p>";
				} else {	

					if($_POST['submitadd']) {
						$sql = "INSERT INTO ". $config['tableprefix'] . "menuItems SET
							name = '$name',
								 parent = '$parent',
								 link = '$link',
								 menuId = '$menuId',
								 minUserLevel = '$minUserLevel',
								 maxUserLevel = '$maxUserLevel',
								 active = '$menuIsActive',
								 category = '$category'";

						$added = "Menu Item $name added.";
					} else {
						$sql = "UPDATE ". $config['tableprefix'] . "menuItems SET
								name = '$name',
								parent = '$parent',
								link = '$link',
								menuId = '$menuId',
								minUserLevel = '$minUserLevel',
								maxUserLevel = '$maxUserLevel',
								active = '$menuIsActive',
								category = '$category'
								where id = '" . $_POST['updateId'] . "'";
						$added = "Menu Item $name updated.";
					}
					if (!$this->m_db->runQuery($sql)) {
						$html = '<p>A database error occurred in processing your '.
							"submission.\nIf this error persists, please ".
							'contact us.</p>';
					} else {
						$html = $this->processMenuOrder($_POST['menuOrder'],
														$parent,
														$_POST['updateId'],
														$name);
						$html .= "<p>$added</p>";
					}
				}
				unset($_POST['submitadd']);
				unset($_POST['submitedit']);
				$html .= $this->menus();
			}
		}
		return $html;

	}

	function menus() {
		if(isset($_POST['delete'])) {
			$_POST['scrollPos'] = 0;
			if(isset($_POST['confirm']))
				$html .= $this->processMenus();
			else if(isset($_POST['deny'])) {
				$html .= "<p>Delete Cancelled</p>\n";	
				unset($_POST['delete']);
				$html .= $this->menus();
			} else {
				$html .= $this->confirmDelete($_SERVER['SCRIPT_URL']);
				return $html;
			}
		} else if(isset($_POST['cancel'])
				|| isset($_POST['submitadd'])
				|| isset($_POST['submitedit'])) {
			$_POST['scrollPos'] = 0;
			$html .= $this->processMenus();
		} else {
			$html = "<h3>Add/Edit Menu Items</h3>\n";
			$html .= "<form enctype=\"multipart/form-data\" id=\"addMenu\" method=\"post\" action=\"\" onsubmit=\"setScrollPos();\">\n";
	    	$html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";
			//read db and get categories as array of category objects
			$menuItems = new treeItems($this->m_db, "menu", "menu");		
			$html .= $menuItems->getTreeItemsAndItemsForm($menuItems);

			$menuItems->processPostArray($activeFolder, $menuItems, 
					$menuItems->getPostArray());

			$menuItems->setType("parentMenu");
			$menuHtml = $menuItems->getParentCatsForm($activeFolder, $menuItems);
			if(isset($_POST['updateId']))
				$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
			$html .= "<div>\n";
			$html .= "<div class=\"group\">\n";
			$html .= "<div class=\"groupTitle\">Add/Edit</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
			$html .= "\t\t<div>Menu Item Name:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
			$html .= "\t\t<input name=\"tItemName\" type=\"text\" value=\"" . $_POST['tItemName'] ."\" maxlength=\"100\" size=\"60\" />\n";
			$html .= "\t</div>\n";
			$html .= "<div class=\"clearer\">&nbsp;</div>\n";
			$html .= "</div>\n";

			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
			$html .= "\t\t<div>Parent Menu:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formRowWhite\">\n";
//			$html .= "\t<div class=\"formTree\">\n";
			$html .= $menuHtml;
//			$html .= "\t</div>\n";

			$html .= "\t</div>\n";
			$html .= "</div>\n";
			$html .= "<div class=\"clearer\">&nbsp;</div>\n";
			/*	
				$html .= "<div class=\"formRow\">\n";
				$html .= "\t<div class=\"formHeading\">\n";
				$html .= "Menu Category:\n";
				$html .= "\t</div>\n";
				$html .= "\t<div class=\"formRowWhite\">\n";
				$html .= "\t<div class=\"formTree\">\n";
				$categories = new treeItems($this->m_db, "parentCat", "menu");		
				$activeCatFolder = -1;
				$categories->processPostArray($activeCatFolder, $categories,
				$categories->getPostArray());	
				$catHtml = $categories->getParentCatsForm($activeCatFolder, $categories);
				$html .= $catHtml;
				$html .= "\t</div>\n";
				$html .= "\t</div>\n";
				$html .= "</div>\n";
			 */
			$categories = new treeItems($this->m_db, "parentCat", "menu");		
			$activeCatFolder = -1;
			$html .= "\t\t<div><h6>Enter the URL you would like to link to in the edit box or choose from the pages in the tree.</h6></div>\n";

			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
			$html .= "\t\tChoose Menu Item Link:\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formRowWhite\">\n";
			include_once("classes/allPages.php");
			$allPages = new allPages($this->m_db);	

			//read db and get categories as array of category objects
			$activeCatFolder = -1;
			$categories->setType("cat");
			$categories->getAllItemsForm($tItemHtml, $itemHtml, $categories);

			$html .= "\t<div class=\"formTree\">\n";
			$html .= $tItemHtml;
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formList\">\n";
			$html .= $itemHtml;
			$html .= "\t</div>\n";
			$html .= "<div class=\"clearer\">&nbsp;</div>\n";

			$html .= "\t</div>\n";
			$html .= "</div>\n";

			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
			$html .= "\t\tCurrent Menu Item Link:\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
			$html .= "\t\t<input name=\"menuLink\" type=\"edit\" maxlength=\"100\" size=\"60\" value=\"" . $_POST['menuLink'] . "\" />\n";
			$html .= "\t</div>\n";
			$html .= "</div>\n";

			$html .= "</div>\n";

			$html .= "<div class=\"group\">\n";
			$html .= "<div class=\"groupTitle\">Advanced</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
			$html .= "\t\t<div>Menu Item Order:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
			$html .= "\t\t<select name=\"menuOrder\">\n";
			$html .= "\t\t<option value=\"none\"></option>\n";
			$html .= "\t\t<option value=\"first\">Make item 'first'.</option>\n";
			$items = $menuItems->getItemsAsList($_POST['parentMenuActive'], $_POST['updateId']);
			foreach($items as $item) {
				$html .= "\t\t<option value=\"$item\">After $item</option>\n";
			}
			$html .= "\t\t</select>\n";
			$html .= "\t\t<h6>Reorder by either marking it as 'first' or choosing the menu item you would like for it to come <b>after</b>.</h6>";
			$html .= "\t</div>\n";
			$html .= "</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
			$html .= "\t\t<div>Menu Item Active:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
			$checked;
			if(isset($_POST['menuIsActive']) || !isset($_POST['updateId']))
				$checked = " checked=\"on\" ";
			else
				$checked = "";
			$html .= "\t\t<input name=\"menuIsActive\" type=\"checkbox\"";
			$html .= "$checked>Menu Item is Active</input>";
			$html .= "\t\t<h6>If check the menu item will be visible in your menu. Otherwise, it will not.</h6>";
			$html .= "\t</div>\n";
			$html .= "<div class=\"clearer\">&nbsp;</div>\n";
			$html .= "</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
			$html .= "\t\t<div>Min User Level:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
			$html .= "\t\t<select name=\"minUserLevel\">\n";
			$isSelected = "";	
			if($_POST['minUserLevel'] == 0)
				$isSelected = "selected=\"true\"";	
			$html .= "\t\t<option value=\"0\" $isSelected>Anonymous</option>\n";
			$isSelected = "";	
			if($_POST['minUserLevel'] == 1)
				$isSelected = "selected=\"true\"";	
			$html .= "\t\t<option value=\"1\" $isSelected>Registered User</option>\n";
			$isSelected = "";	
			if($_POST['minUserLevel'] == 2)
				$isSelected = "selected=\"true\"";	
			$html .= "\t\t<option value=\"2\" $isSelected>Content Administrator</option>\n";
			$isSelected = "";	
			if($_POST['minUserLevel'] == 3)
				$isSelected = "selected=\"true\"";	
			$html .= "\t\t<option value=\"3\" $isSelected>Site Administrator</option>\n";
			$html .= "\t\t</select><br />\n";
			$html .= "\t\t<h6>Choose the minimum user level required for viewing this item.</h6>";
			$html .= "\t</div>\n";
			$html .= "</div>\n";

			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
			$html .= "\t\t<div>Max User Level:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
			$html .= "\t\t<select name=\"maxUserLevel\">\n";
			$isSelected = "";
			$selected = false;
			if($_POST['maxUserLevel'] == "0") {
				$isSelected = "selected=\"true\"";	
				$selected = true;
			}
			$html .= "\t\t<option value=\"0\" $isSelected>Anonymous</option>\n";
			$isSelected = "";
			if(!$selected) {
				if($_POST['maxUserLevel'] == "1") {
					$isSelected = "selected=\"true\"";
					$selected = true;
				}
			}
			$html .= "\t\t<option value=\"1\" $isSelected>Registered User</option>\n";
			$isSelected = "";
			if(!$selected) {
				if($_POST['maxUserLevel'] == "2") {
					$isSelected = "selected=\"true\"";	
					$selected = true;
				}
			}
			$html .= "\t\t<option value=\"2\" $isSelected>Content Administrator</option>\n";
			$isSelected = "";
			if(!$selected) {
				$isSelected = "selected=\"true\"";	
			}
			$html .= "\t\t<option value=\"3\" $isSelected>Site Administrator</option>\n";
			$html .= "\t\t</select><br />\n";
			$html .= "\t\t<h6>Choose the maximum user level item is visible for.</h6>";
			$html .= "\t</div>\n";
			$html .= "</div>\n";

			$html .= "</div>\n";

			if(isset($_POST['updateId']))
				$html .= "<input type=\"submit\" name=\"submitedit\" value=\"Menu Item Edit\" />\n";
			else
				$html .= "<input type=\"submit\" name=\"submitadd\" value=\"Add New Menu Item\" />\n";
			$html .= "<input type=\"submit\" name=\"delete\" value=\"Delete Menu Item\" />\n";
			$html .= "</div>\n";
			$html .= "</form>\n";

		}
		return $html;
	}

	function media() {
		$categories = new treeItems($this->m_db, "cat", "media");		
		$categories->parseImageButtonText("imageDel");
		$categories->parseImageButtonText("imageUpdate");
		if(isset($_POST['imageUpdate'])) {
			$_POST['scrollPos'] = 0;
			$html .= $this->updateMedia();
		}
		else if(isset($_POST['cancel'])
				|| isset($_POST['add'])
				|| isset($_POST['edit'])
				|| isset($_POST['delete'])) {
			$_POST['scrollPos'] = 0;
			$html .= $this->processMedia();
		}
		else if(isset($_POST['imageDel'])) {
			$_POST['scrollPos'] = 0;
			$html .= $this->delMedia();
		}
		else
			$html .= $this->dispMedia();

		return $html;
	}

	function delMedia() {
		$filePath = "images/" . $_POST['catActive'] . "/";
		include_once("classes/media.php");
		$media = new media($this->m_db, 
				$filePath, 
				20*1024*1024, 
				"",
				-1,
				"");
		$media->delMedia();

		return $this->dispMedia();

	}

	function updateMedia() {
		$filePath = "images/" . $_POST['catActive'] . "/";
		include_once("classes/media.php");
		$media = new media($this->m_db, 
				$filePath, 
				20*1024*1024, 
				"",
				-1,
				"");
		$html .= $media->updateMedia();

		$html .= $this->dispMedia();

		return $html;
	}

	function dispMedia() {
		GLOBAL $config;

		$html = "<h3>Add/Remove Media & Downloads</h3>\n";

		$html .= "<p><font class=\"star\">*</font>indicates a required field</p>\n";

		$html .= "<form id=\"addContent\" enctype=\"multipart/form-data\" method=\"post\" action=\"" . $this->m_pageType . "/media.html\" onsubmit=\"setScrollPos();\">\n";
	    $html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";

		$categories = new treeItems($this->m_db, "cat", "media");		
		$html .= $categories->getTreeItemsAndItemsForm($categories);

		$categories->processPostArray($activeFolder, $categories,
										$categories->getPostArray());	

		$categories->setType("parentCat");
		$catHtml = $categories->getParentCatsForm($activeFolder, $categories);

		$activeImg = $categories->parseImageButtonText("imageEdit");
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Media/Downloads</div>\n";
		if(isset($_POST['catActive']) && $_POST['catActive'] >= 1) {
			$filePath = "images/" . $_POST['catActive'] . "/";
			include_once("classes/media.php");
			$media = new media($this->m_db, 
					$filePath, 
					20*1024*1024, 
					"",
					-1,
					"");
			$html .= $media->dispMediaAdmin($activeImg);
		} else {
			$html .= "No category chosen.";
		}
		$html .= "</div>\n";

		if(isset($_POST['updateId'])) {
			//	$html .= "<form enctype=\"multipart/form-data\" method=\"post\" action=\"" . $this->m_pageType . "/gallery_admin.html\">\n";
			$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
		}
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Add/Edit Media/Downloads</div>\n";

		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t\t<div>Category:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry formRowWhite\">\n";
		//			$html .= "<input type=\"hidden\" name=\"lastFolder\" value=\"" 
		//				. $activeFolder. "\" />\n";

		$html .= $catHtml;
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t\t<div>Downloadable:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$checked = " checked=\"on\" ";
		if($_POST['download'] != 'on')
			$checked = "";
    	$html .= "\t\t<input name=\"download\" type=\"checkbox\" $checked>Mark as a User Downloadable File.</input>\n";
		$html .= "</div>\n";
		$html .= "<h6>Check this box for the item to show up under this Category in the Downloads section.</h6>";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Description:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<div><textarea wrap=\"soft\" name=\"data\" rows=\"10\" cols=\"60\">" . $media->data . "</textarea></div>\n";
		$html .= "</div>\n";
		$html .= "</div><br />\n";
		$html .= "<h6>****Fill out the above before selecting a file to upload.  For security reasons the filename selected cannot be cached between page loads.****</h6>";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t\t<div>File(s):<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"" . 20*1024*1024 . "\" />";
		$html .= "\t<div class=\"formRow\">\n";
		$html .= "\t\t<input name=\"newMedia\" type=\"file\" value=\"asdf" . $_POST['newMedia'] . "\" maxlength=\"100\" size=\"50\" />\n";
		$html .= "\t</div>\n";
		$html .= "<h6>Hint: 'Zip' several files up and upload the zip file.</h6>";

		$category = $activeFolder;
		if($category == -1)
			$category = $_POST['catActive'];

		$html .= "<input type=\"hidden\" name=\"lastFolder\" value=\"" 
			. $category . "\" />\n";
		$html .= "<input type=\"hidden\" name=\"title\" value=\"" 
			. $_POST['title'] . "\" />\n";
		$html .= "<input type=\"hidden\" name=\"text\" value=\"" 
			. $_POST['text'] . "\" />\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";

		$html .= "</div>\n";


		$html .= "</div>\n";


		$html .= "<input type=\"submit\" name=\"cancel\" value=\"Cancel\" />\n";
		if(isset($_POST['updateId']))
			$html .= "<input type=\"submit\" name=\"edit\" value=\"Edit Media\" />\n";
		else
			$html .= "<input type=\"submit\" name=\"add\" value=\"Add Media\" />\n";
		$html .= "<input type=\"submit\" name=\"delete\" value=\"Delete Media\" />\n";
		$html .= "</form>\n";


		return $html;
	}

	function processMedia() {
		// create the server picture file in the newly created server directory
		$category = $_POST['parentCatActive'];
		if($category <= 0) {
			$html = "<p>Category must be set for upload.</p>";
			$html .= $this->dispMedia();
			return $html;
		}

		$filePath = $_SERVER['DOCUMENT_ROOT'] . "/images/" . $category . "/";
		if(isset($_POST['add'])) {
			if(!is_dir($filePath)) {
				$rc = mkdir($filePath, 0777);
				$rc = chmod($filePath, 0777);
			}
			include_once("classes/media.php");
			$newMedia = new media($this->m_db, 
					$filePath, 
					20*1024*1024, 
					$_FILES['newMedia'], 
					-1,
					false);
			$html = $newMedia->processUpload();
		} else if(isset($_POST['edit'])) {
			if(!is_dir($catdirname)) {
				$rc = mkdir($catdirname, 0777);
				$rc = chmod($catdirname, 0777);
			}
			if($rc) {
				$oldDirName = $_SERVER['DOCUMENT_ROOT'] . "/images/" . $_POST['catActive'] . "/" . $galId;
				$rc = rename($oldDirName, $dirname);
			}

		} else if(isset($_POST['delete'])) {
			//	$rc = $this->rmdirr($dirname);
		} 


		$html .= $this->dispMedia();
		return $html;

	}

	function blocks() {
		$categories = new treeItems($this->m_db, "cat", "media");		
		if(isset($_POST['cancel'])
				|| isset($_POST['add'])
				|| isset($_POST['edit'])
				|| isset($_POST['delete']))
			$html .= $this->processBlock();
		else
			$html .= $this->dispBlock();

		return $html;
	}

	function dispBlock() {
		GLOBAL $config;

		$html = "<h3>Add/Remove Page Blocks</h3>\n";

		$html .= "<p><font class=\"star\">*</font>indicates a required field</p>\n";

		$html .= "<form id=\"addBlock\" method=\"post\" action=\"" . $this->m_pageType . "/blocks.html\" onsubmit=\"setScrollPos();\">\n";
	    $html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";

		$categories = new treeItems($this->m_db, "cat", "blocks");		
		$html .= $categories->getTreeItemsAndItemsForm($categories);

		$categories->processPostArray($activeFolder, $categories,
										$categories->getPostArray());	

		$activeFolder = -1;
		$categories->setType("parentCat");
		$catHtml = $categories->getParentCatsForm($activeFolder, $categories);

		$addEditHeader = "Add New Block";
		if(isset($_POST['updateId'])) {
			$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
			$html .= "<input name=\"itemId\" type=\"hidden\" value=\"" . $_POST['itemId'] ."\"  />\n";
			$html .= "<div class=\"group\">\n";
			$html .= "<div class=\"groupTitle\">Block Specific Settings</div>\n";
			$blockType = $_POST['blockType'];
			include_once("blocks/$blockType.php");
			$evalStr = '$block = new ' . $blockType . 
						'($_POST["updateId"], $_POST["location"], $this->m_db, $_POST["parentCatActive"]);';
			eval($evalStr);
	
			$html .= $block->getAdminFormText($this);
			$html .= "</div>\n";

			$addEditHeader = "Shared Settings";
		}

		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">$addEditHeader</div>\n";

		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t\t<div>Name:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
		$html .= "\t\t<input name=\"name\" type=\"text\" value=\"" . $_POST['name'] . "\" maxlength=\"100\" size=\"60\" />\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t\t<div>Block Type:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
		$html .= "\t\t<select name=\"blockType\" style=\"width:120px\">\n";
		$isSelected = "";	
		if($_POST['blockType'] == "menu")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"menu\" $isSelected>Menu Block</option>\n";
		$isSelected = "";	
		if($_POST['blockType'] == "eventBlock")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"eventBlock\" $isSelected>Calendar Block</option>\n";
		$isSelected = "";	
		if($_POST['blockType'] == "htmlBlock")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"htmlBlock\" $isSelected>Content Block</option>\n";
		$isSelected = "";	
		if($_POST['blockType'] == "relatedBlock")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"relatedBlock\" $isSelected>Related Block</option>\n";
		$html .= "\t\t</select><br />\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t\t<div>Location:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
		$html .= "\t\t<select name=\"location\" style=\"width:120px\">\n";
		$isSelected = "";	
		if($_POST['location'] == "h")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"h\" $isSelected>Header</option>\n";
		$isSelected = "";	
		if($_POST['location'] == "r")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"r\" $isSelected>Right</option>\n";
		$isSelected = "";	
		if($_POST['location'] == "l")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"l\" $isSelected>Left</option>\n";
		$isSelected = "";	
		if($_POST['location'] == "f")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"f\" $isSelected>Footer</option>\n";
		$html .= "\t\t</select><br />\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t\t<div>Category:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry formRowWhite\">\n";

		$html .= $catHtml;
		$html .= "\t</div>\n";

		$html .= "</div>\n";

		$category = $activeFolder;
		if($category == -1)
			$category = $_POST['catActive'];

		$html .= "<input type=\"hidden\" name=\"lastFolder\" value=\"" 
			. $category . "\" />\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";

		$html .= "</div>\n";


		$html .= "</div>\n";


		$html .= "<input type=\"submit\" name=\"cancel\" value=\"Cancel\" />\n";
		if(isset($_POST['updateId']))
			$html .= "<input type=\"submit\" name=\"edit\" value=\"Update Block\" />\n";
		else
			$html .= "<input type=\"submit\" name=\"add\" value=\"Add Block\" />\n";
		$html .= "<input type=\"submit\" name=\"delete\" value=\"Delete Block\" />\n";
		$html .= "</form>\n";


		return $html;
	}

	function processBlock() {
		GLOBAL $config;
		if(isset($_POST['cancel'])) {
			$html = "<p>Add/Edit cancelled.</p>";
			$html .= $this->admin();
		} else if(isset($_POST['delete'])) {
			$blockType = $_POST['blockType'];
			include_once("blocks/$blockType.php");
			$evalStr = '$block = new ' . $blockType . 
				'(0, $_POST["location"], $this->m_db, "");';
			eval($evalStr);
			$rc = $block->processAdminDelete($_POST['itemId']);

			if($rc) {
				$sql = "delete from " . $config['tableprefix'] . "blocks where id = '" . $_POST['updateId'] . "'";
				if (!$this->m_db->runQuery($sql)) {
					$html = '<p>A database error occurred in processing your '.
						"submission.\nIf this error persists, please ".
						'contact us.</p>';
				} else {
					$html = "<p>Block deleted.</p>";
					unset($_POST['updateId']);
					unset($_POST['name']);
					unset($_POST['location']);
					unset($_POST['blockType']);
					unset($_POST['category']);
					unset($_POST['parentCatActive']);
				}
			} else {
				$html = "<p>Error removing item, delete aborted</p>";
			}
		} else {
			if(!isset($_POST['parentCatActive'])) {
				$html .= "<p>Please select a parent category.</p>";
			} 
			if(!isset($_POST['name'])) {
				$html .= "<p>Please enter a name.</p>";
			} 
			$name = addslashes(htmlspecialchars($_POST['name']));
			$category = $_POST['parentCatActive'];
			$class = $_POST['blockType'];
			$position = $_POST['location'];

			$blockType = $_POST['blockType'];
			include_once("blocks/$blockType.php");
			$evalStr = '$block = new ' . $blockType . 
				'(0, $_POST["location"], $this->m_db, $category);';
			eval($evalStr);

			if(!isset($_POST['updateId'])) {	
				//add "item" to blocktype table
				$rc = $block->processAdminInsert($this, $name, $category);
				
				if($rc) {
					$itemId = $this->m_db->getLastInsertId();
					$sql = "INSERT INTO ". $config['tableprefix'] . "blocks SET
						name = '$name',
							 category = '$category',
							 class = '$class',
							 itemId = '$itemId',
							 position = '$position'";

					if (!$this->m_db->runQuery($sql)) {
						//updateblock with itemid failed undo block insert and processAdminInsert
						$rc = $block->processAdminDelete($itemId);
						$html = "Error inserting into block item, backing out change.";
					} else {
						$html = "Block $name added.";
					}
				} else {
						$html = "Block $name NOT added.";
				}
			} else {
				$rc = $block->processAdminUpdate($name, $_POST['itemId']);

				if($rc) {
					$sql = "UPDATE ". $config['tableprefix'] . "blocks SET
						name = '$name',
							 category = '$category',
							 class = '$class',
							 position = '$position'
								 where id = '" . $_POST['updateId'] . "'";
					if (!$this->m_db->runQuery($sql)) {
						$html = "Error updating  block item, block and blockitem might be out of sync.";
					} else {
						$html = "Block $name updated.";
					}
				} else {
					$html = "Block $name NOT updated.";
				}
			}
		} 

		$html .= $this->dispBlock();
		return $html;

	}
}
?>
