<?php

include_once("pages/page.php");
include_once("classes/file.php");

class news extends page
{
	function news($db) {
		//call parent ctor
		$this->page();
		$this->m_db = $db;
		$this->m_pageType = "news";
		
		//we select or information here so block info will be ready before we
		//get text
		GLOBAL $config;
		$catId = $config['catId'];
		$sql = "select id, title, pageUrl, headline, category, data, hideLeftBlocks, hideRightBlocks, minUserLevel  from " . $config['tableprefix']. $this->m_pageType.  " where pageUrl = '" . $this->m_name . "'";
		
		if($this->m_name != "index") 
			$sql .= " and category ='" . $catId . "'";
		
		$this->m_db->runQuery($sql);
		if($this->m_db->getNumRows() > 1)
			echo "Error getting News page contents";
		else { 
			$this->m_page = $this->m_db->getRowObject();
			if($this->m_name == "index")
				$this->m_page->category = $catId;
		}
	}

	function getDisplayText() {
		GLOBAL $config;
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
				$html .= $this->getHeadlinesText();
			} else {
//				if($this->m_page->showTitle)
					$html .= "<h3>" . $this->m_page->title . "</h3>";
				$showPage = 1;
				if(isset($_GET['action'])) {
					if($_GET['action'] = "showPage")
						$showPage = $_GET['item'];
				}
				if($showPage == 1) {
					if(strlen($config['iconExt']) != 0) { //set in parseCategoryUrl
						$html .= "<div class=\"headlineIcon\">\n";
						$html .= "<img src=\"images/catIcons/" . $this->m_page->category . $config['iconExt'] .
							" \" alt=\"Category Icon\" />\n";
						$html .= "</div>\n";
					}
					$html .= "<p>" . $this->m_page->headline . "</p>\n";
					$html .= "<div class=\"clearer\">&nbsp;</div>\n";
				}

				$html .= "<p>" . $this->getDisplayPageText() . "</p>\n";;
			}
		} else { 
			$html .= "<p>You do not have access to this news page...</p>\n";
		}

		return $html;
	}

	function getHeadlinesText() {
		GLOBAL $config;
		
		$sql = "select headline from " . $config['tableprefix'] . "news where pageUrl = 'index';";
	
		$this->m_db->runQuery($sql);	
		$object = $this->m_db->getRowObject();
		
		$html = $object->headline;

		$catCache = new catCache($this->m_db);
		$catIds = $catCache->lineage($this->m_page->category, false, false);
		$cat = new treeItems($this->m_db, "cat", "");
		//array_push($catIds, $this->m_page->category);
		$catIds = array_merge($catIds, $catCache->ancestory($this->m_page->category, false));
		$catIdsString = implode(" or category = ", $catIds);

		$sql = "select id, title, category, submitter, submitDateTime, headline, pageUrl from " . $config['tableprefix'] . "news where minUserLevel < 2 and pageUrl != 'index' and (category = " . $catIdsString . ")";
		$sql .= " order by 'id' desc limit 0,10;";
		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();
		if($numItems == 0 )
			$html .= "No news items found.";
		else {
			for($i=0; $i<$numItems; ++$i) {
				$newsItems[$i] = $this->m_db->getRowObject();
			}
			foreach($newsItems as $newsItem) {
				$catPath = $cat->getItemPath($newsItem->category);
				$html .= "<div class=\"headline\">\n";
				$html .= "<div class=\"headlineTitle\">\n";
				$html .= "<a href=\"news/" .$catPath . $newsItem->pageUrl. ".html\">";
				$html .= $newsItem->title . "</a>";
				$html .= "<h6>Posted by: " .$newsItem->submitter . " " 
				. date("M d, Y h:i a", strtotime($newsItem->submitDateTime))
				. "</h6></div>\n";
				if(strlen($config['iconExt']) != 0) { //set in getItemPath
					$html .= "<div class=\"headlineIcon\">\n";
					$html .= "<img src=\"images/catIcons/" . $newsItem->category . $config['iconExt'] .
							" \" alt=\"Category Icon\" />\n";
					$html .= "</div>\n";
				}
				$html .= $newsItem->headline;	
				$html .= "<div class=\"clearer\">&nbsp;</div>\n";
				$html .= "</div>\n";
			}
		}
		return $html;	
	}

	function getAdminSQL($title, $category) {
		GLOBAL $config;
		$sql = "select id, title, category, pageUrl, headline, data, eventId, hideLeftBlocks, hideRightBlocks, minUserLevel  from " . $config['tableprefix']. $this->m_pageType . " where title = '" . addslashes($_POST[$title]) . "' and category = '". $_POST[$category] . "';";
		
		return $sql;
	}	

	function admin() {
		GLOBAL $config;
		GLOBAL $extraHead;
		GLOBAL $bodyOnLoad;
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"js/ts_picker.js\"></script>\n";
		$tinymce = "<!-- tinyMCE -->\n";
		$tinymce .= "<script language=\"javascript\" type=\"text/javascript\" src=\"/js/tiny_mce/tiny_mce.js\"></script>\n";
		$tinymce .= "<script language=\"javascript\" type=\"text/javascript\">\ntinyMCE.init({mode : \"textareas\", theme : \"advanced\", content_css : \"/themes/cc1/styletm.css\",\n";
		$tinymce .= "plugins : \"advimage,emotions,contextmenu\",\n";
		$tinymce .= "theme_advanced_buttons3_add : \"emotions\",\n";
		//$tinymce .= "add_form_submit_trigger : \"false\",\n";
		$tinymce .= "external_image_list_url : \"../js/myfiles/imgdd.js\",\n";
		$tinymce .= "external_link_list_url : \"../js/myfiles/linkdd.js\"\n";
		$tinymce .= "});\n</script>\n<!-- /tinyMCE -->\n";

		$extraHead .= $tinymce;
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"themes/" . $config['themeName'] . "/scroller.js\"></script>\n";
		$bodyOnLoad = "onload=\"scrollIt()\"";

		$html = "<h3>Add/Edit News Pages</h3>\n";

		$html .= "<form id=\"addContent\" name=\"addContent\" method=\"post\" action=\"" . $this->m_pageType . "/admin.html\" onsubmit=\"setScrollPos();\">\n";
	    $html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";
		$categories = new treeItems($this->m_db, "cat","");		
		$html .= $categories->getTreeItemsAndItemsForm($this);
	
		$this->processPostArray($activeFolder, $categories, 
								$categories->getPostArray());

		$categories->setType("parentCat");
		$catHtml = $categories->getParentCatsForm($activeFolder, $this);
	
		$html .= "<p><font class=\"star\">*</font>indicates a required field</p>\n";
		if(isset($_POST['updateId'])) {
    		$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
    		$html .= "<input name=\"eventId\" type=\"hidden\" value=\"" . $_POST['eventId'] ."\"  />\n";
		}
		$html .= "<div>\n";
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Naming and Grouping</div>\n";
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
    	$html .= "\t\t<div>Category:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry formRowWhite\">\n";
		$html .= $catHtml;
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">News Page Headline</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Headline:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea class=\"mce\" wrap=\"soft\" name=\"headline\" rows=\"10\">" . $_POST['headline'] . "</textarea></div>\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Toggle Image/Link Category Filter:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
		$html .= "<input type=\"submit\" name=\"toggleImgFilter\" value=\"Toggle\" />\n";
		$ifhtml = "";
		$curCatOnly = $this->toggleImgFilterText($ifhtml);
		$html .= $ifhtml;
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		
		$html .= "</div>\n";
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">News Page Content</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Content:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea class=\"mce\" wrap=\"soft\" name=\"pageContent\" rows=\"30\">" . $_POST['pageContent'] . "</textarea></div>\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Toggle Image/Link Category Filter:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
		$html .= "<input type=\"submit\" name=\"toggleImgFilter\" value=\"Toggle\" />\n";
		$html .= $ifhtml;
		
		$file = new file();
		$outPath = $_SERVER["DOCUMENT_ROOT"] . "/js/myfiles";
		$file->createTinyMCEImageList("images", $outPath, $_POST['parentCatActive'], $curCatOnly);
		$file->createTinyMCEContentList($this->m_db, $outPath, $_POST['parentCatActive'], $curCatOnly);
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= "</div>\n";
		
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Advanced</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Event Calendar:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
		$addEvent;	
		if($_POST['addEvent'] == 'on')
			$addEvent = " checked=\"on\" ";
    	$html .= "\t\t<input name=\"addEvent\" type=\"checkbox\"";
		$html .= "$addEvent>Show Item in Event Calendar</input>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t Start Date: <input name=\"eventDateStart\" type=\"text\" maxlength=\"12\" size=\"16\" value=\"" . $_POST['eventDateStart'] . "\" />\n";
		$html .= "<a href=\"javascript:show_calendar('document.addContent.eventDateStart', document.addContent.eventDateStart.value);\"><img src=\"images/icons/cal.gif\" border=\"0\" alt=\"Pick date\"></a>";
    	$html .= "\t\t End Date: <input name=\"eventDateEnd\" type=\"text\" maxlength=\"12\" size=\"16\" value=\"" . $_POST['eventDateEnd'] . "\" />\n";
		$html .= "<a href=\"javascript:show_calendar('document.addContent.eventDateEnd', document.addContent.eventDateEnd.value);\"><img src=\"images/icons/cal.gif\" border=\"0\" alt=\"Pick date\"></a>";

		$html .= "\t</div>\n";
		$html .= "</div>\n";

		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
		$html .= "\t\t Start Time: <select name=\"eventTimeStartHour\">\n";
		$isSelected = "";	
		for($i=1; $i<=12; ++$i) {
			if($_POST['eventTimeStartHour'] == $i)
				$isSelected = "selected=\"true\"";	
			$html .= "\t\t<option value=\"$i\" $isSelected>$i</option>\n";
			$isSelected = "";	
		}
		$html .= "\t\t</select>\n";
		$html .= "\t\t <select name=\"eventTimeStartMin\">\n";
		$isSelected = "";	
		for($i=0; $i<60; $i+=15) {
			if($_POST['eventTimeStartMin'] == $i)
				$isSelected = "selected=\"true\"";	
			$html .= "\t\t<option value=\"$i\" $isSelected>" . str_pad($i, 2, '0') . "</option>\n";
			$isSelected = "";	
		}
		$html .= "\t\t</select>\n";
		$html .= "\t\t <select name=\"eventTimeStartAM\">\n";
		$isSelected = "";	
		if($_POST['eventTimeStartAM'] == "AM")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"0\" $isSelected>AM</option>\n";
		$isSelected = "";	
		if($_POST['eventTimeStartAM'] == "PM")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"1\" $isSelected>PM</option>\n";
		$html .= "\t\t</select>\n";
		
		$html .= "\t\t End Time: <select name=\"eventTimeEndHour\">\n";
		$isSelected = "";	
		for($i=1; $i<=12; ++$i) {
			if($_POST['eventTimeEndHour'] == $i)
				$isSelected = "selected=\"true\"";	
			$html .= "\t\t<option value=\"$i\" $isSelected>$i</option>\n";
			$isSelected = "";	
		}
		$html .= "\t\t</select>\n";
		$html .= "\t\t <select name=\"eventTimeEndMin\">\n";
		$isSelected = "";	
		for($i=0; $i<60; $i+=15) {
			if($_POST['eventTimeEndMin'] == $i)
				$isSelected = "selected=\"true\"";	
			$html .= "\t\t<option value=\"$i\" $isSelected>" . str_pad($i, 2, '0') . "</option>\n";
			$isSelected = "";	
		}
		$html .= "\t\t</select>\n";
		$html .= "\t\t <select name=\"eventTimeEndAM\">\n";
		$isSelected = "";	
		if($_POST['eventTimeEndAM'] == "AM")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"0\" $isSelected>AM</option>\n";
		$isSelected = "";	
		if($_POST['eventTimeEndAM'] == "PM")
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t<option value=\"1\" $isSelected>PM</option>\n";
		$html .= "\t\t</select>\n";
    	$html .= "\t\t<h6>Check and fill out info. to add News Item to Event Calendar.  </h6>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";

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
			$html .= "<input type=\"submit\" name=\"submitedit\" value=\"Submit News Item Edit\" />\n";
		else
			$html .= "<input type=\"submit\" name=\"submitadd\" value=\"Add News Item\" />\n";
		$html .= "<input type=\"submit\" name=\"delete\" value=\"Delete Item\" />\n";
		$html .= "</div>\n";
		$html .= "</form>\n";

		return $html;
	}
	
	function processAdmin() {
		GLOBAL $config;
		if(isset($_POST['cancel'])) {
			$html = "<p>Add/Edit cancelled.</p>";
		} else if(isset($_POST['delete']) ) {
			$errored = false;
			if($_POST['eventId'] >= 1) {
				$sql = "delete from " . $config['tableprefix'] . "events where id = '" . $_POST['eventId'] . "'";
				if (!$this->m_db->runQuery($sql) || $this->m_db->getNumRowsAffected() != 1) {
					$html .= '<p>A database error occurred in removing related event item.</p>';
					$errored = true;
				} else {
					$html .= "<p>Related event item removed.</p>";
					$_POST['eventId'] = -1;	
					unset($_POST['addEvent']);
					unset($_POST['eventDateStart']);
					unset($_POST['eventTimeStartHour']);
					unset($_POST['eventTimeStartMin']);
					unset($_POST['eventTimeStartAM']);
					unset($_POST['eventDateEnd']);
					unset($_POST['eventTimeEndHour']);
					unset($_POST['eventTimeEndMin']);
					unset($_POST['eventTimeEndAM']);
				}
			}

			if(!$errored) {	
				$sql = "delete from " . $config['tableprefix'] . $this->m_pageType . " where id = '" . $_POST['updateId'] . "'";
				if (!$this->m_db->runQuery($sql) || $this->m_db->getNumRowsAffected() != 1) {
					$html = '<p>A database error occurred in processing your '.
						"submission.\nIf this error persists, please ".
						'contact us.</p>';
				} else {
					$html = "<p>Page deleted.</p>";
					unset($_POST['updateId']);
					unset($_POST['title']);
					unset($_POST['category']);
					unset($_POST['pageUrl']);
					unset($_POST['headline']);
					unset($_POST['pageContent']);
					unset($_POST['hideLeftBlocks']);
					unset($_POST['hideRightBlocks']);
					unset($_POST['minUserLevel']);
				}
			}
		} else {
			if($_POST['title'] == "") {
				$html .= "<p>Please enter a title.</p>";
			} else if($_POST['parentCatActive'] == "") {
				$html .= "<p>Please select a parent category.</p>";
			} else if($_POST['headline'] == "") {
				$html .= "<p>Please enter headline.</p>";
			} else {

				$title = addslashes(htmlspecialchars($_POST['title']));
				$category = $_POST['parentCatActive'];
				$pageContent = addslashes($_POST['pageContent']);
				$duplicate = $this->m_db->isDuplicateName("title", $title, 
						$config['tableprefix']."content", 
						"category", $category);
				if($duplicate) {
					$html .= "<p>An item with the same title already exists in this category.\nPlease choose another title.</p>";
				} else {
					$headline = addslashes($_POST['headline']);
					$hideLeftBlocks = 0;
					if($_POST['hideLeftBlocks'] == 'on')
						$hideLeftBlocks = 1;
					$hideRightBlocks = 0;
					if($_POST['hideRightBlocks'] == 'on')
						$hideRightBlocks = 1;
					$pageUrl = addslashes(htmlspecialchars($_POST['pageUrl']));
					$minUserLevel = $_POST['minUserLevel'];

					$pageUrl = $this->processPageUrl($pageUrl, $title);

					//events
					$eventTimeStartHour = $_POST['eventTimeStartHour'];
					$eventTimeEndHour = $_POST['eventTimeEndHour'];
					if(isset($_POST['addEvent'])) {
						if($_POST['eventTimeStartAM'] == 1) {
							$eventTimeStartHour += 12;
						}
						if($_POST['eventTimeEndAM'] == 1) {
							$eventTimeEndHour += 12;
						}
						$start = explode('/', trim($_POST['eventDateStart']));
						$dateStrStart = $start[2]. "-" .  $start[0] . "-" . $start[1] . " " 
							. $eventTimeStartHour . ":"	
							. $_POST['eventTimeStartMin'];

						$end = explode('/', trim($_POST['eventDateEnd']));
						$dateStrEnd = $end[2]. "-" . $end[0] . "-" . $end[1] . " " 
							. $eventTimeEndHour . ":"
							. $_POST['eventTimeEndMin'];

					} 

					$errored = false;	
					if($_POST['submitadd']) {
						$user = $_SESSION['uid'];	
						$eventId = -1;	
						$content = $headline . $pageContent;
						if ($_POST['addEvent'] == 'on') {
							$eventSql = "INSERT INTO ". $config['tableprefix'] . "events SET
								title = '$title',
									  pageUrl = '$pageUrl',
									  category = '$category',
									  data = '$content',
									  submitDateTime = NOW(),
									  submitter = '$user',
									  startDateTime = '$dateStrStart',
									  endDateTime = '$dateStrEnd',
									  hideLeftBlocks = '$hideLeftBlocks',
									  hideRightBlocks = '$hideRightBlocks',
									  minUserLevel = '$minUserLevel'";
							$eventAdded = "Event Item $title added.";

							if(!$this->m_db->runQuery($eventSql)) {
								$html = '<p>A database error occurred in processing your '.
									"submission.\nIf this error persists, please ".
									'contact us.</p>';
								$errored = true;
							} else {
								$html = "<p>$eventAdded</p>";
								$eventId = $this->m_db->getLastInsertId();
								$_POST['eventId'] = $eventId;	
							}
						} 

						if(!$errored) {

							$sql = "INSERT INTO ". $config['tableprefix'] . $this->m_pageType . " SET
								title = '$title',
									  pageUrl = '$pageUrl',
									  category = '$category',
									  data = '$pageContent',
									  headline = '$headline',
									  submitDateTime = NOW(),
									  submitter = '$user',
									  eventId = '$eventId',
									  hideLeftBlocks = '$hideLeftBlocks',
									  hideRightBlocks = '$hideRightBlocks',
									  minUserLevel = '$minUserLevel'";

							$added = "News Item $title added.";
							if (!$this->m_db->runQuery($sql)) {
								$html = '<p>A database error occurred in processing your '.
									"submission.\nIf this error persists, please ".
									'contact us.</p>';
								$sql = "delete from " . $config['tableprefix'] . "events where id = '" . $eventId . "'";
								if (!$this->m_db->runQuery($sql) || $this->m_db->getNumRowsAffected() != 1) {
									$html .= '<p>A database error occurred in cleaning up the failed insert.</p>';
								} else {
									$html .= "<p>Event item cleaned up.</p>";
								}
							} else {
								$html .= "<p>$added</p>";
								$_POST['updateId'] = $this->m_db->getLastInsertId();
							}
						}
					} else {
						$eventId = $_POST['eventId'];	
						$content = $headline . $pageContent;
						if ($_POST['addEvent'] == 'on') {
							if($eventId == -1) {
								$eventSql = "INSERT INTO ". $config['tableprefix'] . "events SET
									title = '$title',
										  pageUrl = '$pageUrl',
										  category = '$category',
										  data = '$content',
										  submitDateTime = NOW(),
										  submitter = '$user',
										  startDateTime = '$dateStrStart',
										  endDateTime = '$dateStrEnd',
										  hideLeftBlocks = '$hideLeftBlocks',
										  hideRightBlocks = '$hideRightBlocks',
										  minUserLevel = '$minUserLevel'";
								$eventAdded = "Event Item $title added.";

								if(!$this->m_db->runQuery($eventSql)) {
									$html = '<p>A database error occurred in processing your '.
										"submission.\nIf this error persists, please ".
										'contact us.</p>';
									$errored = true;
								} else {
									$html = "<p>$eventAdded</p>";
									$eventId = $this->m_db->getLastInsertId();
									$_POST['eventId'] = $eventId;	
								}

							} else {
								$eventSql = "UPDATE ". $config['tableprefix'] . "events SET
									title = '$title',
										  pageUrl = '$pageUrl',
										  category = '$category',
										  data = '$pageContent',
										  startDateTime = '$dateStrStart',
										  endDateTime = '$dateStrEnd',
										  hideLeftBlocks = '$hideLeftBlocks',
										  hideRightBlocks = '$hideRightBlocks',
										  minUserLevel = '$minUserLevel'
											  where id = '" . $_POST['eventId'] . "'";

								$eventUp = "Event Item $title updated.";
								if (!$this->m_db->runQuery($eventSql)) {
									$html = '<p>A database error occurred in processing your '.
										"submission.\nIf this error persists, please ".
										'contact us.</p>';
									$errored = true;
								} else {
									$html = "<p>$eventUp</p>";
								}
							}
						} else if($eventId != -1) {
							$sql = "delete from " . $config['tableprefix'] . "events where id = '" . $eventId . "'";
							if (!$this->m_db->runQuery($sql) || $this->m_db->getNumRowsAffected() != 1) {
								$html .= '<p>A database error occurred in removing related event item.</p>';
								$errored = true;
							} else {
								$html .= "<p>Related event item removed.</p>";
								$eventId= -1;
								$_POST['eventId'] = -1;	
								unset($_POST['addEvent']);
								unset($_POST['eventDateStart']);
								unset($_POST['eventTimeStartHour']);
								unset($_POST['eventTimeStartMin']);
								unset($_POST['eventTimeStartAM']);
								unset($_POST['eventDateEnd']);
								unset($_POST['eventTimeEndHour']);
								unset($_POST['eventTimeEndMin']);
								unset($_POST['eventTimeEndAM']);
							}
						}

						if(!$errored) {
							$sql = "UPDATE ". $config['tableprefix'] . $this->m_pageType . " SET
								title = '$title',
									  pageUrl = '$pageUrl',
									  category = '$category',
									  data = '$pageContent',
									  headline = '$headline',
									  eventId = '$eventId',
									  hideLeftBlocks = '$hideLeftBlocks',
									  hideRightBlocks = '$hideRightBlocks',
									  minUserLevel = '$minUserLevel'
										  where id = '" . $_POST['updateId'] . "'";

							$added = "News Item $title updated.";
							if (!$this->m_db->runQuery($sql)) {
								$html .= '<p>A database error occurred in processing your '.
									"submission. If this news item also exists as an event this " .
									"error might have caused your news item and event item to become " .
									"out of sync.\nIf this error persists, please ".
									'contact us.</p>';
							} else {
								if($_POST['submitadd']) 
									$_POST['updateId'] = $this->m_db->getLastInsertId();
								$html .= "<p>$added</p>";
							}
						}
					}
				}
			}
		}
		$html .= $this->admin();
		return $html;
	}
	
	function processTreeItemsFormPostArray($activeFolder, $postArray) {	
			GLOBAL $config;
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
				$_POST['headline'] = $editPage->headline;
				$_POST['pageContent'] = $editPage->data;
				$_POST['eventId'] = $editPage->eventId;

				if($_POST['eventId'] != -1) {
					$sql = "select id, startDateTime, endDateTime from " . $config['tableprefix']. "events where id = '" . $_POST['eventId'] . "';";
					$this->m_db->runQuery($sql);
					$event = $this->m_db->getRowObject();
					
					$dateTime = explode(' ', $event->startDateTime);	
					$date = explode('-', $dateTime[0]);
					$_POST['eventDateStart'] = $date[1]. "/" .  $date[2] . "/" . $date[0]; 
					$time = explode(':', $dateTime[1]);
					$_POST['eventTimeStartHour'] = (int)$time[0];
					$_POST['eventTimeStartMin'] = (int)$time[1];
					$_POST['eventTimeStartAM'] = 1;
					if($_POST['eventTimeStartHour'] > 12) {
						$_POST['eventTimeStartHour'] -= 12;
						$_POST['eventTimeStartAM'] = 0;
					}
					
					$dateTime = explode(' ', $event->endDateTime);	
					$date = explode('-', $dateTime[0]);
					$_POST['eventDateEnd'] = $date[1]. "/" .  $date[2] . "/" . $date[0]; 
					$time = explode(':', $dateTime[1]);
					$_POST['eventTimeEndHour'] = (int)$time[0];
					$_POST['eventTimeEndMin'] = (int)$time[1];
					$_POST['eventTimeEndAM'] = 1;
					if($_POST['eventTimeEndHour'] > 12) {
						$_POST['eventTimeEndHour'] -= 12;
						$_POST['eventTimeEndAM'] = 0;
					}
					$_POST['addEvent'] = 'on';
				} else {
					$_POST['addEvent'] = 'off';
					unset($_POST['eventDateStart']);
					unset($_POST['eventTimeStartHour']);
					unset($_POST['eventTimeStartMin']);
					unset($_POST['eventTimeStartAM']);
					unset($_POST['eventDateEnd']);
					unset($_POST['eventTimeEndHour']);
					unset($_POST['eventTimeEndMin']);
					unset($_POST['eventTimeEndAM']);
				}
				if($editPage->hideLeftBlocks == 1)
					$_POST['hideLeftBlocks'] = 'on';
				if($editPage->hideRightBlocks == 1)
					$_POST['hideRightBlocks'] = 'on';
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
				$_POST['addEvent'] = 'off';
				unset($_POST['eventDateStart']);
				unset($_POST['eventTimeStartHour']);
				unset($_POST['eventTimeStartMin']);
				unset($_POST['eventTimeStartAM']);
				unset($_POST['eventDateEnd']);
				unset($_POST['eventTimeEndHour']);
				unset($_POST['eventTimeEndMin']);
				unset($_POST['eventTimeEndAM']);
				$activeFolder = -1;
			}

		return $activeFolder;
	}
	
	function getItemsFormText($category, $postArray) {
		GLOBAL $config;
		$sql = "select title from " . $config['tableprefix'] . $this->m_pageType . " where category ='" . $category . "' and minUserLevel <= " . $_SESSION['userLevel'] . " and editable=1 order by id desc";
//echo $sql;
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
