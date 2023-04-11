<?php

//include_once('classes/categories.php');
include_once('classes/treeItems.php');

class page
{
	var $m_pageType;
	var $m_page; //our row object from whichever type of page we are
	var $m_db;
	var $m_name;
	var $m_myuri;
	
	function page() {
		//default parameters for our pages.  If a pageType has any non-default
		//parameters those need to be defined in the subclass	
		$this->m_name  = $_GET['name'];
		
		if(!isset($this->m_name))
			$this->m_name = "index";
	
		$this->m_myuri = $_SERVER["REQUEST_URI"];	
	}
	
	function hideLeftBlocks() {
		return $this->m_page->hideLeftBlocks;
	}
	
	function hideRightBlocks() {
		return $this->m_page->hideRightBlocks;
	}
	
	function confirmDelete($formAction) {
		$html = "<h3>Confirm Delete</h3>\n";
		$pos = strrpos($formAction, "popup");
		if($pos != 0)
			$formAction = substr($formAction, 0, $pos+1);
		$html .= "<form id=\"deleteIt\" name=\"deleteIt\" method=\"post\" action=\"$formAction\">\n";
		$html .= "<p><font class=\"star\">***</font>You are about to permenantly delete this item.  Do you wish to continue?<font class=\"star\">***</font></p>\n";
		$numItems = sizeof($_POST);
		$keys = array_keys($_POST);
		for($i=0; $i < $numItems; $i++) {
			$key = $keys[$i];
			$val = htmlspecialchars($_POST[$key]);
    		$html .= "<input name=\"$key\" type=\"hidden\" value=\"$val\"  />\n";
		}
		$html .= "<input type=\"submit\" name=\"confirm\" value=\"   Yes   \" />\n";
		$html .= "<input type=\"submit\" name=\"deny\" value=\"    No    \" />\n";
		$html .= "</form>\n";

		return $html;
	}

	function toggleImgFilterText(&$html) {
		$cutCatOnly = false;
		if(isset($_POST['toggleImgFilter'])) {
			if($_POST['imgFilter'] !== "1") {
	    		$html .= "<input name=\"imgFilter\" type=\"hidden\" value=\"1\"  />\n";
	    		$html .= "\t\t<h6>Only images/links for the current parent category will be shown when inserting images.</h6>";
				$curCatOnly = true;
			} else {
	    		$html .= "<input name=\"imgFilter\" type=\"hidden\" value=\"0\"  />\n";
	    		$html .= "\t\t<h6>Images/links for all categories will be shown when inserting images.</h6>";

			}	
		} else {
	    	$html .= "<input name=\"imgFilter\" type=\"hidden\" value=\"" . $_POST['imgFilter'] . "\"  />\n";
			if($_POST['imgFilter'] !== "1") {
    			$html .= "\t\t<h6>Images/links for all categories will be shown when inserting images.</h6>";
			}
			else {
	    		$html .= "\t\t<h6>Only images/links for the current parent category will be shown when inserting images.</h6>";
				$curCatOnly = true;
			}
		}
		return $curCatOnly;
	}

	function getPageTitle() {
		return $this->m_page->title;
	}

	function getPageCategory() {
		return $this->m_page->category;
	}

	function getDisplayPageText() {
		$showPage = 1;
		if(isset($_GET['action'])) {
			if($_GET['action'] = "showPage")
				$showPage = $_GET['item'];
		}
		$pages = explode("[PAGE]", $this->m_page->data);
		$html = $pages[$showPage-1];

		if(sizeof($pages) > 1) {
			$uri = $_SERVER["REQUEST_URI"];
			$pos = strrpos($uri, "/");
			$path = substr($uri, 0, $pos);

			for($i=1; $i<=sizeof($pages); ++$i) {
				if($i == $showPage)
					$html .= " $i ";
				else {
					$html .= "<a href=\"" . $path . "/showPage_" . $this->m_name . "_$i.html\">";
					$html .= " $i ";
					$html .= "</a>";
				}
			}
		}
		
		return $html;
	}

	function processTreeItemsFormPostArray($activeFolder, $postArray) {	
			if($activeFolder != -1)
				unset($_POST[$postArray['page']]);
	
			if(isset($_POST[$postArray['page']])) {
				$activeFolder = $_POST[$postArray['active']];
				$sql = $this->getAdminSQL($postArray['page'], $postArray['active']);
				$this->m_db->runQuery($sql);
				$editPage = $this->m_db->getRowObject();
				$_POST['updateId'] = $editPage->id;
				$_POST['title'] = $editPage->title;
				$_POST['category'] = $editPage->category;
				$_POST['pageUrl'] = $editPage->pageUrl;
				if(isset($editPage->headline))
					$_POST['headline'] = $editPage->headline;
				$_POST['pageContent'] = $editPage->data;
				if($editPage->hideLeftBlocks == 1)
					$_POST['hideLeftBlocks'] = 'on';
				else
					$_POST['hideLeftBlocks'] = 'off';
				
				if($editPage->hideRightBlocks == 1)
					$_POST['hideRightBlocks'] = 'on';
				else
					$_POST['hideRightBlocks'] = 'off';
				
				$_POST['minUserLevel'] = $editPage->minUserLevel;
			} else if(isset($_POST['newItem'])) {
				unset($_POST['updateId']);
				unset($_POST['title']);
				unset($_POST['category']);
				unset($_POST['pageUrl']);
				unset($_POST['headline']);
				unset($_POST['pageContent']);
				unset($_POST['hideLeftBlocks']);
				unset($_POST['hideRightBlocks']);
				unset($_POST['minUserLevel']);
				$activeFolder = -1;
			}

		return $activeFolder;
	}
	
	function processPageUrl($pageUrl, $title) {
					
		if($pageUrl == "") {
			$pageUrl =  $title;
		}

		$pageUrl = ereg_replace(' ', '-', $pageUrl);
		$pageUrl = ereg_replace('/', '-', $pageUrl);
		$pageUrl = preg_replace('![^a-zA-Z0-9_-]!', '', $pageUrl);
		$pageUrl = strtolower($pageUrl);

		$_POST['pageUrl'] = $pageUrl;

		return $pageUrl;
	}
	
	function processPostArray(&$activeFolder, $cats, $postArray) {
		if(isset($_POST[$postArray['page']])) {
			$activeFolder = $_POST[$postArray['active']];
		}
		else 
			$activeFolder = -1;
		
		//unset($_POST[$postArray['page']]);
		//unset($_POST[$postArray['folder']]);
		//unset($_POST['itemInfoForm']);
	}

	function getItemsFormText($category, $postArray) {
		GLOBAL $config;
		$sql = "select title from " . $config['tableprefix'] . $this->m_pageType . " where category ='" . $category . "' and minUserLevel <= " . $_SESSION['userLevel'] . " and editable=1";
		$this->m_db->runQuery($sql);
		$numPages = $this->m_db->getNumRows();
		for($i=0; $i<$numPages; ++$i) {
			$page = $this->m_db->getRowObject();
	
			$pageHtml .= "<img src=\"images/html.gif\" alt=\"folder\" /> ";
			$pageHtml .= "<input class=\"button\" name=\""
						. $postArray['page'] . "\" value=\""; 
			$pageHtml .= $page->title . "\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" /><br />\n";
		}

		return $pageHtml;
	}

}
?>
