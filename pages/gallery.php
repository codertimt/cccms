<?php

include_once("pages/page.php");
include_once("classes/file.php");
include_once("classes/media.php");

class gallery extends page
{
	var $m_adminType;
	var $m_galleryId;
	var $m_catId;

	function gallery($db) {
		//call parent ctor
		$this->page();
		$this->m_db = $db;
		$this->m_pageType = "gallery";

		//we /select or information here so block info will be ready before we
		//get text
		GLOBAL $config;
		$catId = $config['catId'];
		$this->m_catId = $catId;
		$sql = "select id, showTitle, title, category, pageUrl, data, hideLeftBlocks, hideRightBlocks, minUserLevel,recordType  from " . $config['tableprefix']. $this->m_pageType.  " where pageUrl = '" . $this->m_name . "'";
		if($this->m_name != "index") 
			$sql .= " and category ='" . $catId . "'";
		$this->m_db->runQuery($sql);
		
		if($this->m_db->getNumRows() > 1) {
			$sql2 = $sql . " and category ='" . $catId . "'";
echo $sql2;		
			$this->m_db->runQuery($sql2);
			$numRows = $this->m_db->getNumRows();
			if($this->m_db->getNumRows() < 1) {
				$sql .= " and category =0";
				$this->m_db->runQuery($sql);
				if($this->m_db->getNumRows() != 1) {
					echo "Error finding gallery page.";
				}
			}
		}
//used to be an else stmt...left the brackets
		  {
			$this->m_page = $this->m_db->getRowObject();
			if($this->m_page->recordType == 2) {
				$this->m_galleryId = $this->m_page->id;
				$sql = "select showTitle, rows, cols, hideLeftBlocks, hideRightBlocks, minUserLevel from " . $config['tableprefix']. $this->m_pageType.  " where recordType=0 and category ='" . $this->m_catId . "';";
				$this->m_db->runQuery($sql);

				if($this->m_db->getNumRows() != 1) {
					//$html .= "Error getting gallery page";
					$sql = "select showTitle, rows, cols, hideLeftBlocks, hideRightBlocks, minUserLevel from " . $config['tableprefix']. $this->m_pageType.  " where recordType=0 and category=1";
					$this->m_db->runQuery($sql);


				}
				$parentPage = $this->m_db->getRowObject();
				$this->m_page->showTitle = $parentPage->showTitle;
				$this->m_page->hideLeftBlocks = $parentPage->hideLeftBlocks;
				$this->m_page->hideRightBlocks = $parentPage->hideRightBlocks;
				$this->m_page->minUserLevel = $parentPage->minUserLevel;
				$this->m_page->rows = $parentPage->rows;
				$this->m_page->cols = $parentPage->cols;

			} else if($this->m_page->recordType == 0) {
				$this->m_page->category = $catId;
				$this->m_page->rows = 4;
				$this->m_page->cols = 4;
			}
		}
		
	}

	function getDisplayText() {
		GLOBAL $config;
		
		if($_SESSION['userLevel'] >= $this->m_page->minUserLevel) {
			if($this->m_name == "admin") {
				$categories = new treeItems($this->m_db, "cat", "category");		
				$categories->parseImageButtonText("imageDel");
				$categories->parseImageButtonText("imageUpdate");
				if(isset($_POST['delete']) || isset($_POST['deleteGal'])) {
					$_POST['scrollPos'] = 0;
					if(isset($_POST['confirm'])) {
						if($_GET['action'] == "category") 
							$html .= $this->processSetup();
						else
							$html .= $this->processAdmin();
					}
					else if(isset($_POST['deny'])) {
						$html .= "<p>Delete Cancelled</p>\n";	
						if($_GET['action'] == "category") 
							$html .= $this->setup();
						else
							$html .= $this->admin();
					} else {
						if($_GET['action'] == "category") 
							$formPage = "/gallery_setup.html";
						else 
							$formPage = "/gallery_admin.html";
						$html .= $this->confirmDelete($this->m_pageType . $formPage);
						return $html;
					}
				} else if(isset($_POST['cancel']) 
					|| isset($_POST['addGal'])
					|| isset($_POST['addMedia'])
					|| isset($_POST['submitadd'])
					|| isset($_POST['editGal'])
					|| isset($_POST['submitedit'])) {
					$_POST['scrollPos'] = 0;
					if(isset($_GET['action'])) {
						if($_GET['action'] == "category") 
							$html .= $this->processSetup();
						 else if($_GET['action'] == "gallery")
							$html .= $this->processAdmin();
						 else 
							$html .= $this->processAdminMedia();
					} else {
						$html .=$this->processAdmin();
					}
				}
				else if(isset($_POST['imageDel'])) {
					$html .= $this->delMedia();
				}
				else if(isset($_POST['imageUpdate'])) {
					$html .= $this->updateMedia();
				}
				else {
					if(isset($_GET['action'])) {
						if($_GET['action'] == "category") 
							$html .= $this->doSetup();
						 else if($_GET['action'] == "gallery")
							$html .= $this->admin();
						 else 
							$html .= $this->adminMedia();
					} else {
						$html .= $this->admin();
					}

				}
			} else {
				$html .= "<div class=\"group\">\n";
				$html .= "<div class=\"groupTitle\">" 
						. $this->m_page->title. "</div>\n";
				$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		
				$category = $this->m_catId;
				$galleryId = $this->m_galleryId;
				$filePath = "gallery/" . $category . "/" . $galleryId . "/";
				$media = new media($this->m_db, 
								  $filePath, 
								  20*1024*1024, 
								  "",
								  $galleryId,
								  false);
				$html .= $media->dispMedia($this->m_page,
											$this->m_page->rows,
											$this->m_page->cols);
				$html .= "</div>";
				
			}
		} else { 
			$html .= "<p>You do not have access to this page...</p>\n";
		}
		return $html;
	}
	
	function delMedia() {
		$filePath = "gallery/" . $category . "/" . $galleryId . "/";
		include_once("classes/media.php");
		$media = new media($this->m_db, 
						   $filePath, 
						   20*1024*1024, 
				     	   "",
						   $this->m_galleryId,
							false);
		$media->delMedia();

		return $this->admin();

	}
	
	function updateMedia() {
		$filePath = "gallery/" . $category . "/" . $galleryId . "/";
		include_once("classes/media.php");
		$media = new media($this->m_db, 
						   $filePath, 
						   20*1024*1024, 
				     	   "",
						   $this->m_galleryId,
							false);
		$media->updateMedia();

		return $this->admin();

	}

	function getAdminSQL($activeFolder, $title, $cat) {
		GLOBAL $config;
		if($this->m_adminType == "setup") {
			$sql = "select id, title, category, pageUrl, data, hideLeftBlocks, hideRightBlocks, minUserLevel  from " . $config['tableprefix']. $this->m_pageType . " where category = '". $activeFolder  . "' and recordType = 0;";
		} else {
			$sql = "select id, title, category, pageUrl, data, hideLeftBlocks, hideRightBlocks, minUserLevel  from " . $config['tableprefix']. $this->m_pageType . " where title = '" . $_POST[$title] . "' and category = '". $_POST[$cat] . "';";
		}	
		return $sql;
	}

	function adminHeader() {
		$html = "<p><a href=\"gallery/category_admin.html\">Associate Gallery with Category</a>";
		$html .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"gallery/gallery_admin.html\">Add/Edit Albums</a>";
		$html .= "<br /><br /></p>\n";

		return $html;
	}
	
	function admin() {
		GLOBAL $config;
		GLOBAL $extraHead;
		GLOBAL $bodyOnLoad;
		
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"themes/" . $config['themeName'] . "/scroller.js\"></script>\n";
		$bodyOnLoad = "onload=\"scrollIt()\"";

		$html = "<h3>" . $this->m_page->title . "</h3>\n";
		
		$html .= "<form id=\"addContent\" enctype=\"multipart/form-data\" method=\"post\" action=\"" . $this->m_pageType . "/gallery_admin.html\" onsubmit=\"setScrollPos();\">\n";
	    $html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";
		$html .= $this->adminHeader();
	
		$categories = new treeItems($this->m_db, "cat", "category");		
		$html .= $categories->getTreeItemsAndItemsForm($this);
		
		$this->processPostArray($activeFolder, $categories, 
								$categories->getPostArray());
	
		$categories->setType("parentCat");
		$catHtml = $categories->getParentCatsForm($activeFolder, $this);

		if(isset($_POST['catFolderIcon']) || isset($_POST['catFolderTitle']))
			unset($_POST['thumbPage']);

		if(isset($_POST['thumbPage']))
	    	$html .= "<input name=\"thumbPage\" type=\"hidden\" value=\"" . $_POST['thumbPage'] ."\"  />\n";

		$html .= "<p><font class=\"star\">*</font>indicates a required field</p>\n";
		$activeImg = $categories->parseImageButtonText("imageEdit");
		if((isset($_POST['updateId']) && $activeFolder != -1) 
		|| isset($_POST['thumbPage']) 
		|| isset($_POST['back']) 
		|| isset($_POST['imageEdit']) 
		|| isset($_POST['imageDel']) 
		|| isset($_POST['imageUpdate'])) {
	    	$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
			$html .= "<div class=\"group\">\n";
			$html .= "<div class=\"groupTitle\">Media</div>\n";
			$html .= "<div class=\"formRow\">\n";
			$html .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"" . 20*1024*1024 . "\" />";
    		$html .= "\t\t<input name=\"newMedia\" type=\"file\" maxlength=\"100\" size=\"50\" />\n";
			$html .= "<input type=\"submit\" name=\"addMedia\" value=\"Add Media\" />\n";
			$html .= "<h6>Hint: 'Zip' several files up and upload the zip file.</h6>";
					
			$category = $activeFolder;
			if($category == -1)
				$category = $_POST['catActive'];
			$galleryId = $_POST['updateId'];
   		    $filePath = "gallery/" . $category . "/" . $galleryId . "/";
			$media = new media($this->m_db, 
							  $filePath, 
							  20*1024*1024, 
							  "",
							  $galleryId,
								false);
			$html .= $media->dispMediaAdmin($activeImg);

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
		//	$html .= "</form>";
		}

		if(isset($_POST['updateId']))
	    	$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
		$html .= "<div>\n";
		
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Album Information</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Title:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<input name=\"title\" type=\"text\" value=\"" . $_POST['title'] ."\" maxlength=\"100\" size=\"60\" />\n";
		$html .= "\t</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Gallery:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formRowWhite\">\n";
		$html .= "<input type=\"hidden\" name=\"lastFolder\" value=\"" 
				. $activeFolder. "\" />\n";
					
		$html .= $catHtml;
		$html .= "\t</div>\n";
    	$html .= "\t\t<h6>Each category can be associated with one gallery using the menu item at the page top. If a category/gallery association is not made, that particular categories gallery will use default settings.</h6>";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Description:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea wrap=\"soft\" name=\"text\" rows=\"8\" cols=\"54\">" . $_POST['text'] . "</textarea></div>\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		
		$html .= "</div>\n";
		
		$html .= "<input type=\"submit\" name=\"cancel\" value=\"Cancel\" />\n";
		if(isset($_POST['updateId']))
			$html .= "<input type=\"submit\" name=\"editGal\" value=\"Edit Album\" />\n";
		else
			$html .= "<input type=\"submit\" name=\"addGal\" value=\"Add Album\" />\n";
		$html .= "<input type=\"submit\" name=\"deleteGal\" value=\"Delete Album\" />\n";
	
		$html .= "</div>\n";
		$html .= "</form>\n";

		return $html;
	}
	
	function processAdmin() {
		GLOBAL $config;
		$action = "";
		$category = $_POST['parentCatActive'];
		if(isset($_POST['cancel'])) {
			$html = "<p>Add/Edit cancelled.</p>";
			$html .= $this->admin();
		} else if(isset($_POST['deleteGal'])) {
			$sql = "delete from " . $config['tableprefix'] . $this->m_pageType . " where id = '" . $_POST['updateId'] . "'";
			if (!$this->m_db->runQuery($sql)) {
    	   		$html = '<p>A database error occurred in processing your '.
        	   		  	 "submission.\nIf this error persists, please ".
          			  	 'contact us.</p>';
			} else {
				$sql = "delete from " . $config['tableprefix'] . "media where galleryId = '" . $_POST['updateId'] . "'";
				if (!$this->m_db->runQuery($sql)) {
    	   			$html = '<p>A database error occurred in processing your '.
        	   			  	 "submission.\nIf this error persists, please ".
          				  	 'contact us.</p>';
				} else {
					$action = "del";
					$this->galleryDirectories($category, $action); 
					$html = "<p>Album deleted.</p>";
					unset($_POST['delete']);
					unset($_POST['title']);
					unset($_POST['text']);
					unset($_POST['parentCatActive']);
					unset($_POST['updateId']);
				}
			}
		} else if($_POST['addGal'] || $_POST['editGal']) {	
			if($_POST['title'] == "") {
				$html .= "<p>Please enter a title.</p>";
			} else if($_POST['parentCatActive'] == "") {
				$html .= "<p>Please select a parent category.</p>";
			} else if($_POST['text'] == "") {
				$html .= "<p>Please enter a gallery description</p>";
			} else {
				$title = addslashes(htmlspecialchars($_POST['title']));
				$galDesc = addslashes($_POST['text']);

				if($pageUrl == "") {
					$pageUrl = ereg_replace(' ', '-', $title);
					$pageUrl = strtolower($pageUrl);
				}

				if($_POST['addGal']) {	
					$sql = "INSERT INTO ". $config['tableprefix'] . $this->m_pageType . " SET
						title = '$title',
							  showTitle = '',
							  pageUrl = '$pageUrl',
							  category = '$category',
							  data = '$galDesc',
							  hideLeftBlocks = '',
							  hideRightBlocks = '',
							  minUserLevel = ''";

					$action = "add";
					$added = "Album $title added.";
				} else if($_POST['editGal']) {
					$sql = "UPDATE ". $config['tableprefix'] . $this->m_pageType . " SET
						title = '$title',
							  showTitle = '',
							  pageUrl = '$pageUrl',
							  category = '$category',
							  data = '$galDesc',
							  hideLeftBlocks = '',
							  hideRightBlocks = '',
							  minUserLevel = ''
								  where id = '" . $_POST['updateId'] . "'";

					$action = "update";
					$added = "Album $title updated.";
				}
				if (!$this->m_db->runQuery($sql)) {
					$html = '<p>A database error occurred in processing your '.
						"submission.\nIf this error persists, please ".
						'contact us.</p>';
				} else {
					if(!isset($_POST['updateId']))
						$_POST['updateId'] = $this->m_db->getLastInsertId();
					if(!$this->galleryDirectories($category, $action)) {
						$html = "<p>Filesystem error, creating gallery direcotry</p>";
					} else {
						$html = "<p>$added</p>";
					}
				}
			}
		} else if($_POST['addMedia'] || $_POST['editMedia']) {	
			if($_POST['addMedia']) {
				$html .= $this->addMedia();
			} else if($_POST['editMedia']) {
			}
		} else {
			$html = "<p>Unknown processsing command given</p>";
		}

		$html .= $this->admin();
		return $html;
	}

	function addMedia() {
		// create the server picture file in the newly created server directory
		$category = $_POST['lastFolder'];
		$galleryId = $_POST['updateId'];
		$filePath = $_SERVER['DOCUMENT_ROOT'] . "/gallery/" . $category . "/" . $galleryId . "/";

		$newMedia = new media($this->m_db, 
							  $filePath, 
							  20*1024*1024, 
							  $_FILES['newMedia'], 
							  $galleryId,
								false);
		$html = $newMedia->processUpload();
		
		return $html;
	}

	function rmdirr($dir) {
		if($objs = glob($dir."/*")){
			foreach($objs as $obj) {
				is_dir($obj)? $this->rmdirr($obj) : unlink($obj);
			}
		}
		rmdir($dir);
	}

	function galleryDirectories($category, $action) {
		$rc = true;
   	    $catdirname = $_SERVER['DOCUMENT_ROOT'] . "/gallery/" . $category;
		$galId = $_POST['updateId'];

		$dirname = $catdirname . "/" . $galId;
		if($action == "add") { //create direcotry
			if(!is_dir($catdirname)) {
				$rc = mkdir($catdirname, 0777);
				$rc = chmod($catdirname, 0777);
			}
			if($rc) {
				$rc = mkdir($dirname, 0777);
				$rc = chmod($dirname, 0777);
			}

		} else if($action == "update") {
			if(!is_dir($catdirname)) {
				$rc = mkdir($catdirname, 0777);
				$rc = chmod($catdirname, 0777);
			}
			if($rc) {
				$oldDirName = $_SERVER['DOCUMENT_ROOT'] . "/gallery/" . $_POST['catActive'] . "/" . $galId;
				$rc = rename($oldDirName, $dirname);
			}
			
		} else if($action == "del"){ // remove directory and contents
			$rc = $this->rmdirr($dirname);
		} 

		return $rc;
	}
	
	function processTreeItemsFormPostArray($activeFolder, $postArray) {	
		GLOBAL $config;
		$_POST['catId'] = $activeFolder;
		$sql = $this->getAdminSQL($activeFolder, 
								  $postArray['page'], $postArray['active']);
		$this->m_db->runQuery($sql);
		$numRows = $this->m_db->getNumRows();
		if($numRows != 0) {
			$editCat = $this->m_db->getRowObject();
			if($this->m_adminType == "setup") {
				if(isset($_POST[$postArray['folder']]) 
					|| isset($_POST[$postArray['folderTitle']])) {
					$_POST['updateId'] = $editCat->id;
					$_POST['title'] = $editCat->title;
					$_POST['text'] = $editCat->data;
					$_POST['pageUrl'] = $editPage->pageUrl;
					if($editPage->hideLeftBlocks == 1)
						$_POST['hideLeftBlocks'] = 'on';
					if($editPage->hideRightBlocks == 1)
						$_POST['hideRightBlocks'] = 'on';
					$_POST['minUserLevel'] = $editPage->minUserLevel;
				}
			} else {
				if($activeFolder != -1)
					unset($_POST[$postArray['page']]);
				if(isset($_POST[$postArray['page']])) {
					$activeFolder = $_POST[$postArray['active']];
					$_POST['updateId'] = $editCat->id;
					$_POST['title'] = $editCat->title;
					$_POST['text'] = $editCat->data;
				}
			}
		} else {
			if($this->m_adminType == "setup") {
					unset($_POST['updateId']);
					unset($_POST['title']);
					unset($_POST['category']);
					unset($_POST['pageUrl']);
					unset($_POST['text']);
					unset($_POST['hideLeftBlocks']);
					unset($_POST['hideRightBlocks']);
					unset($_POST['minUserLevel']);
			} else {
				if(isset($_POST['newItem'])) { 
					unset($_POST['updateId']);
					unset($_POST['title']);
					unset($_POST['text']);
				}
			}
		}
		return $activeFolder;
	}
	
	function processPostArray(&$activeFolder, $cats, $postArray) {
		if($this->m_adminType == "setup") {
			if(isset($_POST['catId']))
				$activeFolder = $_POST['catId'];
			else 
				$activeFolder = -1;
		} else {
			if (isset($_POST['addGal'])
					|| isset($_POST['editGal'])
					|| isset($_POST['addMedia'])
					|| isset($_POST['editMedia'])) {
				$activeFolder = $_POST[$postArray['active']];
			}
			else if(isset($_POST[$postArray['page']]))
				$activeFolder = $_POST[$postArray['active']];
			else 
				$activeFolder = -1;
		}
	}

	function getItemsFormText($category, $postArray) {
		if($this->m_adminType == "setup") {
			return "";	
		} else {
			GLOBAL $config;
			$sql = "select title from " . $config['tableprefix'] . $this->m_pageType . " where category ='" . $category . "' and recordType = 2;";
			$this->m_db->runQuery($sql);
			$numPages = $this->m_db->getNumRows();
			for($i=0; $i<$numPages; ++$i) {
				$page = $this->m_db->getRowObject();
	
				$galleryHtml .= "<img src=\"images/html.gif\" alt=\"folder\" /> ";
				$galleryHtml .= "<input class=\"button\" name=\""
						. $postArray['page'] . "\" value=\""; 
				$galleryHtml .= $page->title . "\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" /><br />\n";
			}

			return $galleryHtml;
		}
	}
	
	function doSetup() {
		GLOBAL $config;
		GLOBAL $extraHead;
		GLOBAL $bodyOnLoad;
		
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"themes/" . $config['themeName'] . "/scroller.js\"></script>\n";
		$bodyOnLoad = "onload=\"scrollIt()\"";
		$this->m_adminType = "setup";
		$html = "<h3>" . $this->m_page->title . "</h3>\n";
		
		$html .= $this->adminHeader();
	
		$html .= "<form id=\"sectionInfo\" method=\"post\" action=\"\" onsubmit=\"setScrollPos();\">\n";
	    $html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";
		$categories = new treeItems($this->m_db, "cat", "category");		
		$html .= $categories->getTreeItemsAndItemsForm($this);

		$this->processPostArray($activeFolder, $categories, 
								$categories->getPostArray());
		
		$html .= "<p><font class=\"star\">*</font>indicates a required field</p>\n";
		if(isset($_POST['updateId']))
	    	$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
		$html .= "<div>\n";
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Photo Category Info.</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Category</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
		if($activeFolder != -1)
	    	$html .= "\t\t<h3>" . $categories->getActiveItemName($activeFolder) . "</h3>\n";
		$html .= "\t</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Gallery Title:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<input name=\"title\" type=\"text\" value=\"" . $_POST['title'] ."\" maxlength=\"100\" size=\"60\" />\n";
    	$html .= "\t\t<input name=\"showTitle\" type=\"checkbox\" checked=\"1\">Show Title</input>\n";
		$html .= "\t</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Gallery Desc.:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea wrap=\"soft\" name=\"text\" rows=\"16\" cols=\"70\">" . $_POST['text'] . "</textarea></div>\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Advanced</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Block Hiding:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
		$checkedLeft;	
		if($_POST['hideLeftBlocks'] == 'on')
			$checkedLeft = " checked=\"on\" ";
    	$html .= "\t\t<input name=\"hideLeftBlocks\" type=\"checkbox\"";
		$html .= "$checkedLeft>Hide Left Blocks</input>";
		$checkedRight;	
		if($_POST['hideRightBlocks'] == 'on')
			$checkedRight = " checked=\"on\" ";
    	$html .= "\t\t<input name=\"hideRightBlocks\" type=\"checkbox\" ";
		$html .= "$checkedRight>Hide Right Blocks</input><br />";
    	$html .= "\t\t<h6> Hide blocks for this page unless block is specified to be shown 'Always'.</h6>";
		$html .= "\t</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Link Name:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<input name=\"pageUrl\" type=\"text\" maxlength=\"100\" size=\"60\" value=\"" . $_POST['pageUrl'] . "\" /><br />\n";
    	$html .= "\t\t<h6> Specify a nice name for that will be used as the link to this page.  Otherwise a name will be generated from the title.</h6>";
		$html .= "\t</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Permissions:</div>\n";
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
    	$html .= "\t\t<h6>Choose the minimum user level required for viewing this page.</h6>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		
		$html .= "</div>\n";
		$html .= "<input type=\"submit\" name=\"cancel\" value=\"Cancel\" />\n";
		if(isset($_POST['updateId']))
			$html .= "<input type=\"submit\" name=\"submitedit\" value=\"Edit Association\" />\n";
		else
			$html .= "<input type=\"submit\" name=\"submitadd\" value=\"Add Association\" />\n";
		$html .= "<input type=\"submit\" name=\"delete\" value=\"Delete Association\" />\n";
		$html .= "</div>\n";
		$html .= "</form>\n";

		return $html;
	}
	
	function processSetup() {
		GLOBAL $config;
		if(isset($_POST['cancel'])) {
			$html = "<p>Add/Edit cancelled.</p>";
			$html .= $this->doSetup();
		} else if(isset($_POST['delete'])) {
			$sql = "delete from " . $config['tableprefix'] . $this->m_pageType . " where id = '" . $_POST['updateId'] . "'";
			if (!$this->m_db->runQuery($sql)) {
    	   		$html = '<p>A database error occurred in processing your '.
        	   		  	 "submission.\nIf this error persists, please ".
          			  	 'contact us.</p>';
			} else {
				$html = "<p>Page deleted.</p>";
			}
		} else {
			if(!isset($_POST['catActive'])) {
				$html .= "<p>Please select a parent category.</p>";
			} 
			if(!isset($_POST['title'])) {
				$html .= "<p>Please enter a title.</p>";
			} 
			if(!isset($_POST['text'])) {
				$html .= "<p>Please enter content.</p>";
			}
 
			$title = addslashes(htmlspecialchars($_POST['title']));
			$showTitle = addslashes(htmlspecialchars($_POST['showTitle']));
			$category = $_POST['catActive'];
			$pageContent = addslashes($_POST['text']);
			$hideLeftBlocks = 0;
			if($_POST['hideLeftBlocks'] == 'on')
				$hideLeftBlocks = 1;
			$hideRightBlocks = 0;
			if($_POST['hideRightBlocks'] == 'on')
				$hideRightBlocks = 1;
			$pageUrl = addslashes(htmlspecialchars($_POST['pageUrl']));
			$minUserLevel = $_POST['minUserLevel'];
			
			$pageUrl = "index";
				
			if($_POST['submitadd']) {	
				$sql = "INSERT INTO ". $config['tableprefix'] . $this->m_pageType . " SET
						title = '$title',
						showTitle = '$showTitle',
						pageUrl = '$pageUrl',
						category = '$category',
						data = '$pageContent',
						hideLeftBlocks = '$hideLeftBlocks',
						hideRightBlocks = '$hideRightBlocks',
						minUserLevel = '$minUserLevel',
						recordType = 0";

				$added = "Category/Gallery Association added.";
			} else {
				$sql = "UPDATE ". $config['tableprefix'] . $this->m_pageType . " SET
						title = '$title',
						showTitle = '$showTitle',
						category = '$category',
						data = '$pageContent',
						hideLeftBlocks = '$hideLeftBlocks',
						hideRightBlocks = '$hideRightBlocks',
						minUserLevel = '$minUserLevel'
						where id = '" . $_POST['updateId'] . "'";

				$added = "Category/Gallery Association updated.";
			}
			if (!$this->m_db->runQuery($sql)) {
    	   		$html = '<p>A database error occurred in processing your '.
        	   		  	 "submission.\nIf this error persists, please ".
          			  	 'contact us.</p>';
			} else {
				$html = "<p>$added</p>";
			}
		}

		$html .= $this->doSetup();
		return $html;
	}
}

?>
