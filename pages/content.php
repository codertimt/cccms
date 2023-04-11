<?php

include_once("pages/page.php");
include_once("classes/file.php");

class content extends page
{
	function content($db) {
		//call parent ctor
		$this->page();
		$this->m_db = $db;
		$this->m_pageType = "content";

		//we select or information here so block info will be ready before we
		//get text
		GLOBAL $config;
		$catId = $config['catId'];
		$sql = "select id, title, pageUrl, data, category, hideLeftBlocks, hideRightBlocks, minUserLevel  from " . $config['tableprefix']. $this->m_pageType.  " where pageUrl = '" . $this->m_name . "'";

		if($this->m_name != "index" && $this->m_name != "downloads") 
			$sql .= " and category ='" . $catId . "'";
		$this->m_db->runQuery($sql);
		if($this->m_db->getNumRows() > 1)
			echo "Error getting page contents";
		else { 
			$this->m_page = $this->m_db->getRowObject();
			if($this->m_name == "index" || $this->m_name == "downloads")
				$this->m_page->category = $catId;
		}
	}

	function getDisplayText() {
		if($_SESSION['userLevel'] >= $this->m_page->minUserLevel) {
			if($this->m_name == "admin") {
				if(isset($_POST['delete'])) {
					$_POST['scrollPos'] = 0;
					if(isset($_POST['confirm']))
						$html .= $this->processAdmin();
					else if(isset($_POST['deny'])) {
						$html .= "<p>Delete Cancelled</p>\n";	
						$html .= $this->admin();
					} else {
						$html .= $this->confirmDelete($this->m_pageType . "/admin.html");
						return $html;
					}
				} else if(isset($_POST['cancel'])
					|| isset($_POST['submitadd'])
					|| isset($_POST['submitedit'])) {
					$_POST['scrollPos'] = 0;
					$html .= $this->processAdmin();
				} else {
					$html .= $this->admin();
				}
			} else if($this->m_name == "index") {
				$html .= $this->getContentSiteMap();
			} else if($this->m_name == "downloads") {
				$html .= $this->getDownloads();
			} else {
				if($this->m_page->showTitle)
					$html .= "<h6>" . $this->m_page->title . "</h6>";
				
				$html .= $this->getDisplayPageText();
			}
		} else { 
			$html .= "<p>You do not have access to this page...</p>\n";
		}

		return $html;
	}

	function getDownloads() {
		GLOBAL $config;
		$html = "<p>" . $this->m_page->data . "</p>";
		$html .= "<p>The following subcategories also contain downloads...</p>";
		$subHtml = "";
		$catCache = new catCache($this->m_db);
		$catIds = $catCache->lineage($this->m_page->category, false, true);
		$catNames = $catCache->lineage($this->m_page->category, true, true);

		foreach($catIds as $catId) {
			$subCatIds = $catCache->lineage($catId);
			array_push($subCatIds, $catId);
			$subCatIdsString = implode(" or category = ", $subCatIds);
			$sql = "select category, download from cc_siteMedia where (category=$subCatIdsString) and (download = 1)";
			$this->m_db->runQuery($sql);
			$numMedia = $this->m_db->getNumRows();
			if($numMedia >= 1) {
				$uri = $_SERVER["REQUEST_URI"];
				$pos1 = strrpos($uri, '/');
				$path = substr($uri, 1, $pos1);
				$subHtml .= "<a href=\"$path" . $catNames[$id]. "/downloads.html\">$catNames[$id]</a> ";
				$html .= $subHtml . " ";
			}
			$id++;
		}
		$html .= "<br />";
		$html .= "<br />";
		$html .= "<h3>Downloads for this Category</h3>";

		$sql = "select category, fileType, name, download, data from " . $config['tableprefix']. "siteMedia where (download = 1) and (category = " . $this->m_page->category . ") order by 'name' asc;";
		if (!$this->m_db->runQuery($sql)) {
    	  	$html .= "<p>A database error occurred retrieving site map</p>/n";
		} else {
			$numRows = $this->m_db->getNumRows();
			if($numRows > 0) {
				for($i=0; $i<$numRows; ++$i) {
					$download = $this->m_db->getRowObject();
					$html .= "<div class=\"group\">\n";
					$html .= "<div class=\"groupTitle\">\n";
					$html .= $download->name;
					$html .= "</div>";
					
					$html .= "<p>" . $download->data . "</p>";

					$html .= "<a href=\"" . "images/" . $this->m_page->category
							. "/" . $download->name . "\">Download File</a>"; 
				}
			}
		}
		
		return $html;
	}

	function getContentSiteMap() {
		GLOBAL $config;
		$numCols = 2;
		$html = "<h3>Site Map - Content Pages</h3>";
		$html .= "<p>" . $this->m_page->data . "</p>";
	
		$cat = new treeItems($this->m_db, "cat", "");
		$catCache = new catCache($this->m_db);	
		$catIds = $catCache->lineage($this->m_page->category, false, false);
		array_push($catIds, $this->m_page->category);
		$catIdsString = implode(" or category = ", $catIds);
		$sql = "select title, category, pageUrl, minUserLevel from " . $config['tableprefix']. $this->m_pageType . " where (minUserLevel <= '" . $_SESSION['userLevel'] . "') and (category = " . $catIdsString . ") order by 'category' asc;";
		if (!$this->m_db->runQuery($sql)) {
    	  	$html .= "<p>A database error occurred retrieving site map</p>/n";
		} else {
			$html .= "<div class=\"sitemap\">\n";
			$nextCol = true;
			$totalCols = 1;
			$numRows = $this->m_db->getNumRows();
			$divisor = ceil($numRows/$numCols);
			if($divisor*$numCols == $numRows)
				++$divisor;
			if($numRows > 0) {
				$page = $this->m_db->getRowObject();
				$prevCat = '';
				for($i=0; $i<$numRows; ++$i) {
					if($nextCol) {
						$html .= "<div class=\"sitemapOuter\">\n";
						$nextCol = false;
					}
				
					if($prevCat != $page->category) {
						$lineage = $cat->getItemLineage($page->category);
				
						$catName = implode("/", $lineage);	
						$html .= "<div class=\"sitemapInner\">\n";
						$html .= "<div class=\"sitemapTitle\"><h3>" . $catName . "</h3></div>\n";
						$html .= "<ul>\n";
					}
			
					$catPath = "/";	
					$pos = strpos($catName, "/");
					if($pos != 0)
						$catPath = "/" . substr($catName, $pos+1) . "/";
					$html .= "\t<li><a href=\"content" . $catPath . $page->pageUrl . ".html\">" . $page->title . "</a></li>\n";
					if($i+1<$numRows) {
						$prevCat = $page->category;
						$page = $this->m_db->getRowObject();
						if($prevCat != $page->category) {
							$html .= "</ul></div>\n";
							if($i >= $divisor-1 && $totalCols < $numCols) {
								$html .= "</div>\n";
								$nextCol = true;
								++$totalCols;
							}
						}
					} else {
						$html .= "</ul></div></div>\n";
					}

				}
			}
			$html .= "<div class=\"clearer\">&nbsp;</div>\n";
			$html .= "</div>\n";
		}
		
		return $html;
	}
	

	function getAdminSQL($title, $category) {
		GLOBAL $config;
		$sql = "select id, title, category, pageUrl, data, hideLeftBlocks, hideRightBlocks, minUserLevel  from " . $config['tableprefix']. $this->m_pageType . " where title = '" . addslashes($_POST[$title]) . "' and category = '". $_POST[$category]. "';";
	
		return $sql;
	}
	
	function admin() {
		GLOBAL $config;
		GLOBAL $extraHead;
		GLOBAL $bodyOnLoad;

		$tinymce = "<!-- tinyMCE -->\n";
		$tinymce .= "<script language=\"javascript\" type=\"text/javascript\" src=\"/js/tiny_mce/tiny_mce.js\"></script>\n";
		$tinymce .= "<script language=\"javascript\" type=\"text/javascript\">\ntinyMCE.init({mode : \"textareas\", theme : \"advanced\", content_css : \"/themes/cc1/styletm.css\",\n";
		$tinymce .= "plugins : \"advimage,emotions,contextmenu,table,flash\",\n";
		$tinymce .= "theme_advanced_buttons3_add_before : \"tablecontrols,separator\",\n";
		$tinymce .= "theme_advanced_buttons3_add : \"emotions\",\n";
		$tinymce .= "theme_advanced_buttons3_add : \"flash\",\n";
		//$tinymce .= "add_form_submit_trigger : \"false\",\n";
		$tinymce .= "external_image_list_url : \"../js/myfiles/imgdd.js\",\n";
		$tinymce .= "extended_valid_elements : \"img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name]\",\n";
		$tinymce .= "external_link_list_url : \"../js/myfiles/linkdd.js\"\n";
		$tinymce .= "});\n</script>\n<!-- /tinyMCE -->\n";

		$extraHead = $tinymce;
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"themes/" . $config['themeName'] . "/scroller.js\"></script>\n";
		$bodyOnLoad = "onload=\"scrollIt()\"";
		$html = "<h3>Add/Edit Content Pages</h3>\n";
	
		$html .= "<form name=\"admin\" id=\"admin\" method=\"post\" action=\"" . $this->m_pageType . "/admin.html\" onsubmit=\"setScrollPos();\">\n";
		
	    $html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";
		$categories = new treeItems($this->m_db, "cat","");		
		$html .= $categories->getTreeItemsAndItemsForm($this);
		
		$this->processPostArray($activeFolder, $categories, 
								$categories->getPostArray());

		$categories->setType("parentCat");
		$catHtml = $categories->getParentCatsForm($activeFolder, $this);
		
		$html .= "<p><font class=\"star\">*</font>indicates a required field</p>\n";
		if(isset($_POST['updateId']))
	    	$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
		$html .= "<div>\n";
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Naming and Grouping</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Title:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<input name=\"title\" type=\"text\" value=\"" . $_POST['title'] ."\" maxlength=\"100\" size=\"60\" />\n";
    	$html .= "\t\t<input name=\"showTitle\" type=\"checkbox\">Show Title</input>\n";
		$html .= "\t</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Category:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry formRowWhite\">\n";
		$html .= $catHtml;
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
	
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Page Content</div>\n";
		$html .= "<div class=\"formRow\">\n";
/*
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Content:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
*/
//		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea class=\"mce\" wrap=\"soft\" name=\"pageContent\" rows=\"30\">" . $_POST['pageContent'] . "</textarea></div>\n";
//		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Toggle Image/Link Category Filter:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
		$html .= "<input type=\"submit\" name=\"toggleImgFilter\" value=\"Toggle\" />\n";
		$curCatOnly = $this->toggleImgFilterText($html);
		
		$file = new file();
		$outPath = $_SERVER["DOCUMENT_ROOT"] . "/js/myfiles";
		$file->createTinyMCEImageList("images", $outPath, $_POST['parentCatActive'], $curCatOnly);
		$file->createTinyMCEContentList($this->m_db, $outPath, $_POST['parentCatActive'], $curCatOnly);


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
		$checkedLeft = " checked=\"on\" ";
		if($_POST['hideLeftBlocks'] != 'on')
			$checkedLeft = "";
    	$html .= "\t\t<input name=\"hideLeftBlocks\" type=\"checkbox\"";
		$html .= "$checkedLeft>Hide Left Blocks</input>";
		$checkedRight = " checked=\"on\" ";
		if($_POST['hideRightBlocks'] != 'on')
			$checkedRight = "";
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
			$html .= "<input type=\"submit\" name=\"submitedit\" value=\"Submit Page Edit\" />\n";
		else
			$html .= "<input type=\"submit\" name=\"submitadd\" value=\"Add New Page\" />\n";
		$html .= "<input type=\"submit\" name=\"delete\" value=\"Delete Page\" />\n";
		$html .= "</div>\n";
		$html .= "</form>\n";

		return $html;
	}
	
	function processAdmin() {
		$html = "";
		GLOBAL $config;
		if(isset($_POST['cancel'])) {
			$html .= "<p>Add/Edit cancelled.</p>";
			$html .= $this->admin();
		} else if(isset($_POST['delete'])) {
			$sql = "delete from " . $config['tableprefix'] . $this->m_pageType . " where id = '" . $_POST['updateId'] . "'";
			if (!$this->m_db->runQuery($sql)) {
				$html .= '<p>A database error occurred in processing your '.
					"submission.\nIf this error persists, please ".
					'contact us.</p>';
			} else {
				$html = "<p>Page deleted.</p>";
				unset($_POST['delete']);
				unset($_POST['title']);
				unset($_POST['pageContent']);
				unset($_POST['parentCatActive']);
			}
		} else {
			if($_POST['title'] == "") {
				$html .= "<p>Please enter a title.</p>";
			} else if($_POST['parentCatActive'] == "") {
				$html .= "<p>Please select a parent category.</p>";
			} else if($_POST['pageContent'] == "") {
				$html .= "<p>Please enter content.</p>";
			} else {
				$title = addslashes(htmlspecialchars($_POST['title']));
				$showTitle = addslashes(htmlspecialchars($_POST['showTitle']));
				$category = $_POST['parentCatActive'];

				$duplicate = $this->m_db->isDuplicateName("title", $title, 
						$config['tableprefix']."content", 
						"category", $category);
				if($duplicate && $_POST['submitadd']) {
					$html .= "<p>An item with the same title already exists in this category.\nPlease choose another title.</p>";
				} else {
					$pageContent = addslashes($_POST['pageContent']);
					$hideLeftBlocks = 0;
					if($_POST['hideLeftBlocks'] == 'on')
						$hideLeftBlocks = 1;
					$hideRightBlocks = 0;
					if($_POST['hideRightBlocks'] == 'on')
						$hideRightBlocks = 1;
					$pageUrl = addslashes(htmlspecialchars($_POST['pageUrl']));
					$minUserLevel = $_POST['minUserLevel'];

					$pageUrl = $this->processPageUrl($pageUrl, $title);
					

					if($_POST['submitadd']) {
						$sql = "INSERT INTO ". $config['tableprefix'] . $this->m_pageType . " SET
							title = '$title',
								  showTitle = '$showTitle',
								  pageUrl = '$pageUrl',
								  category = '$category',
								  data = '$pageContent',
								  hideLeftBlocks = '$hideLeftBlocks',
								  hideRightBlocks = '$hideRightBlocks',
								  minUserLevel = '$minUserLevel'";

						$added = "Content page $title added.";
					} else {
						$sql = "UPDATE ". $config['tableprefix'] . $this->m_pageType . " SET
							title = '$title',
								  showTitle = '$showTitle',
								  pageUrl = '$pageUrl',
								  category = '$category',
								  data = '$pageContent',
								  hideLeftBlocks = '$hideLeftBlocks',
								  hideRightBlocks = '$hideRightBlocks',
								  minUserLevel = '$minUserLevel'
									  where id = '" . $_POST['updateId'] . "'";

						$added = "Content page $title updated.";
					}
					if (!$this->m_db->runQuery($sql)) {
						$html = '<p>A database error occurred in processing your '.
							"submission.\nIf this error persists, please ".
							'contact us.</p>';
					} else {
						if($_POST['submitadd']) 
							$_POST['updateId'] = $this->m_db->getLastInsertId();
						$html = "<p>$added</p>";
					}
				}
			}
		}

		$html .= $this->admin();
		return $html;
	}
}

?>
