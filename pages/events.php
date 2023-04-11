<?php

include_once("pages/page.php");
include_once("classes/file.php");
include_once("classes/calFactory.php");
include_once("classes/htmlFactory.php");

class events extends page
{
	function events($db) {
		//call parent ctor
		$this->page();
		$this->m_db = $db;
		$this->m_pageType = "events";
		
		//we select or information here so block info will be ready before we
		//get text
		GLOBAL $config;
		$catId = $config['catId'];
		
		$sql = "select id, title, pageUrl, data, hideLeftBlocks, hideRightBlocks, startDateTime, endDateTime, minUserLevel  from " . $config['tableprefix']. $this->m_pageType.  " where pageUrl = '" . $this->m_name . "'";

		if($this->m_name != "index" && $this->m_name != "day")
			$sql .= " and category = " . $catId;
		
		$this->m_db->runQuery($sql);
		if($this->m_db->getNumRows() > 1)
			echo "Error getting Events page contents";
		else { 
			$this->m_page = $this->m_db->getRowObject();
//			if($this->m_name == "index")
				$this->m_page->category = $catId;
		}
	}

	function getDisplayText() {
		GLOBAL $config;
		if($_SESSION['userLevel'] >= $this->m_page->minUserLevel) {
			if($this->m_name == "admin") {
				$html .= $this->getCalendar(true);
			}
			else if($this->m_name == "adminpopup") {
				if(isset($_POST['delete'])) {
					$_POST['scrollPos'] = 0;
					if(isset($_POST['confirm']))
						$html .= $this->processAdmin();
					else if(isset($_POST['deny'])) {
						$html .= "<p>Delete Cancelled</p>\n";	
						$html .= $this->admin();
					} else {
						$html .= $this->confirmDelete($this->m_myuri);
						return $html;
					}
				} else if(isset($_POST['submitedit'])) {
					$html .= $this->processAdmin();
				} else if(isset($_POST['cancel']) 
					|| isset($_POST['submitadd'])) {
					$_POST['scrollPos'] = 0;
					$html .= $this->processAdmin();
				} else {
					$html .= $this->admin();
				}
			} else if($this->m_name == "index") {
				$html .= $this->getCalendar(false);
			} else if($this->m_name == "day") {
				$html .= $this->getDay();
			} else {
				$html .= $this->getEvent();
			}
		} else { 
			$html .= "<p>You do not have access to this events page...</p>\n";
		}

		return $html;
	}

	function getDay() {
		GLOBAL $config;
		//$uri = $_SERVER["REQUEST_URI"];
		//$pos = strrpos($uri, '/');
		//$path = substr($uri, 1, $pos);
	
		$date  = $_GET['item'];
		$year = substr($date, 0, 4);
		$month = substr($date, 4, 2);
		$day = substr($date, 6, 2);
	
		$dayBegin = substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2) . " 00:00:00";
		$dayEnd = substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2) . " 23:59:59";
		
		$catCache = new catCache($this->m_db);
		$catIds = $catCache->lineage($this->m_page->category, false, false);
		$cat = new treeItems($this->m_db, "cat", "");
		array_push($catIds, $this->m_page->category);
		$catIdsString = implode(" or category = ", $catIds);

		$sql = "select id, title, category, pageUrl, data, startDateTime, endDateTime, hideLeftBlocks, hideRightBlocks, minUserLevel, eventType from " . $config['tableprefix']. $this->m_pageType . " where (startDateTime >= '" . $dayBegin . "' and startDateTime <= '" . $dayEnd . "') or(endDateTime >= '" . $dayBegin . "' and endDateTime <= '" . $dayEnd . "') and (category = ". $catIdsString . ");";

		$fDate = date("F d, Y", strtotime($dayBegin));
		$html = "<h3>$fDate</h3>";
		if ($this->m_db->runQuery($sql) && $this->m_db->getNumRows() > 0) {
			$numRows = $this->m_db->getNumRows();

			for($row=0; $row<$numRows; ++$row) {
				$displayEvent = false;
				$event = $this->m_db->getRowObject();
				$startDate = getdate(strtotime($event->startDateTime));	
				$endDate = getdate(strtotime($event->endDateTime));	
				$startDay = $startDate["mday"];
				$endDay = $endDate["mday"];
				$inc = -1;
				/*
				if($event->eventType == 2) {
					$recur = $event;
					$inc = 0;
					$sDateInfo = getdate(strtotime($recur->startDateTime));
					while($sDateInfo["mon"] != $month) {
						$recur->startDateTime = strftime("%Y-%m-%d %H:%M:00", 
								strtotime("$recur->startDateTime +7 days"));
						$recur->endDateTime = strftime("%Y-%m-%d %H:%M:00", 
								strtotime("$recur->endDateTime +7 days"));
						$sDateInfo = getdate(strtotime($recur->startDateTime));
						$inc += 7;
					}	
					while($sDateInfo["mon"] == $month && !$displayEvent) {
						$eDateInfo = getdate(strtotime($recur->endDateTime));
						$sDay = $sDateInfo["mday"];
						while($sDay <= $eDateInfo["mday"]) {
							if($sDay == $day) { 
								$displayEvent = true;
								$event = $recur;
							}
							++$sDay;
						}
						$recur->startDateTime = strftime("%Y-%m-%d %H:%M:00", 
								strtotime("$recur->startDateTime +7 days"));
						$recur->endDateTime = strftime("%Y-%m-%d %H:%M:00", 
								strtotime("$recur->endDateTime +7 days"));
						$inc += 7;

						$sDateInfo = getdate(strtotime($recur->startDateTime));
					}
					$inc -=7;
					$type = "d";
				} else if($event->eventType == 3) {
					$mOffset = $month-(int)($datePartsStart[1]);
					$event->startDateTime = strftime("%Y-%m-%d %H:%M:00", 
							strtotime("$event->startDateTime +$mOffset months"));
					$event->endDateTime = strftime("%Y-%m-%d %H:%M:00", 
							strtotime("$event->endDateTime +$mOffset months"));
					
					$sDateInfo = getdate(strtotime($event->startDateTime));
					$eDateInfo = getdate(strtotime($event->endDateTime));
					if($sDateInfo["mday"] <= $day && $eDateInfo["mday"] >= $day) {
						$displayEvent=true;
						$inc = $mOffset;
						$type = "m";
					}
				} else*/ {
					$displayEvent=true;
				}	

				if($displayEvent) {
					$html .= "<div class=\"event\">\n";
					$catPath = $cat->getItemPath($event->category);
					$html .= "<div class=\"eventTitle\">" 
						. "<h3>" 
						. date("M d, h:i a", strtotime($event->startDateTime))
						. " - "
						. date("M d, h:i a", strtotime($event->endDateTime))
						. "</h3>" 
						. "<br />"
						. "<a href=\"events/" . $catPath;
					if($inc > 0)
						$html .= "disp_";
					$html .= $event->pageUrl;
					if($inc > 0)
						$html .= "_$type" . $inc;
					$html .= ".html\">" 
						. $event->title	
						. "</a>"
						. "</div>";
					$html .= "</div>\n";
				}
			}
		} else {
			$html .= "No events for this day.";
		}
		return $html;

	}

	function getEvent() {
		$dateOffset = "";
		if(isset($_GET["item"])) {
			$recur = true;
			$dateOffset = "+" . substr($_GET["item"], 1);
			if(substr($_GET["item"], 0,1) == "d")
				$dateOffset .= " days";
			else
				$dateOffset .= " months";
		}
		$html = "<div><h3>" . $this->m_page->title . "</h3>" 
			. date("M d, h:i a", strtotime($this->m_page->startDateTime . $dateOffset))
			. " - "
			. date("M d, h:i a", strtotime($this->m_page->endDateTime . $dateOffset))
			. "</div>\n";
				
		$html .= "<p>" . $this->m_page->data . "</p>";

		return $html;
		
	}

	function getCalendar($admin) {
		GLOBAL $config;
	//	$uri = $_SERVER["REQUEST_URI"];
	//	$pos = strpos($uri, '/');
	//	$path = substr($uri, 0, $pos);
		//$path = $this->m_pageType . "/";
		$uri = $_SERVER["REQUEST_URI"];
		$uri = substr($uri, 1);
		$pos = strpos($uri, '/');
		$pos2 = strrpos($uri, '/');
		$path = substr($uri, $pos+1, $pos2-$pos);
			
		$timestamp = time();
		$date = getdate ($timestamp);
		$today = $date['mday'];

		if(isset($_GET['item'])) {
			$date  = $_GET['item'];
			$year = substr($date, 0, 4);
			$month = substr($date, 4, 2);
		} else {
			$curDay = getdate();
			$year = $curDay['year'];
			$month = $curDay['mon'];
		}

		if($month == 1) {
			$prevYM = $year-1 . "12";
			$nextYM = $year . str_pad($month+1, 2, "0", STR_PAD_LEFT); 
		} else if($month == 12) {
			$prevYM = $year . str_pad($month-1, 2, "0", STR_PAD_LEFT); 
			$nextYM = $year+1 . "01";
		} else {
			$prevYM = $year . str_pad($month-1, 2, "0", STR_PAD_LEFT); 
			$nextYM = $year . str_pad($month+1, 2, "0", STR_PAD_LEFT); 
		}

		$calFactory = new calFactory($this->m_db, false);

		$calFactory->init($today, $month, $year);

		//get events
		$calFactory->initEvents($this->m_page->category, $this->m_pageType);
		if($admin)
			$myPage = "admin";
		else
			$myPage = "index";
	
		$fDate = date("F Y", mktime(0,0,0, $month, 1, $year));
		$html = "<div class=\"calHeader\"><h3>$fDate</h3>";
		$html .= "<div class=\"navButton left\">\n";
		$html .= "<a href=\"events/" . $path . "disp_$myPage"."_$prevYM" . ".html\">< Prev</a>";
		$html .= "</div>";

		$html .= "<div class=\"navButton right\">\n";
		$html .= "<a href=\"events/" . $path . "disp_$myPage"."_$nextYM" . ".html\">Next ></a>";
		$html .= "</div>";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>";

		$html .= $calFactory->getCalendarHtml($admin);

		return $html;

	}

	function getAdminSQL($title, $category) {
		GLOBAL $config;

		$sql = "select id, title, category, pageUrl, data, startDateTime, endDateTime, hideLeftBlocks, hideRightBlocks, minUserLevel  eventType, recurId from " . $config['tableprefix']. $this->m_pageType . " where pageUrl = '" . addslashes($_POST[$title]) . "' and category = '". $_POST[$category] . "'";
		return $sql;
	}	

	function admin() {
		GLOBAL $config;
		GLOBAL $extraHead;
		GLOBAL $bodyOnLoad;
		
		if($_GET['action'] == "popup")
			$ispopup = true;
	
		if($ispopup) {
			if(!isset($_POST['catActive'])) {
				$_POST['catActive'] = $_GET['catActive'];
				$_POST['catPageEdit'] = $_GET['catPageEdit'];
			}
			if(!isset($_POST['eventDateStart'])) {
				$myYear = substr($_GET['addDate'], 0, 4);
				$myDay = (int)substr($_GET['addDate'], 4, 2);
				$myMonth = (int)substr($_GET['addDate'], 6, 2);

				$_POST['eventDateStart'] = $myDay . "/" . $myMonth . "/" . $myYear;
				$_POST['eventDateEnd'] = $myDay . "/" . $myMonth . "/" . $myYear;
			}
			if(isset($_POST['newItem'])) {
				unset($_POST['catActive']);
				unset($_POST['catPageEdit']);
			}	
		}
		
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"js/ts_picker.js\"></script>\n";
		$tinymce = "<!-- tinyMCE -->\n";
		$tinymce .= "<script language=\"javascript\" type=\"text/javascript\" src=\"/js/tiny_mce/tiny_mce.js\"></script>\n";
		$tinymce .= "<script language=\"javascript\" type=\"text/javascript\">\ntinyMCE.init({mode : \"textareas\", theme : \"advanced\", content_css : \"/themes/" . $config['themeName'] . "/styletm.css\",\n";
		$tinymce .= "plugins : \"advimage,emotions,contextmenu\",\n";
		$tinymce .= "theme_advanced_buttons3_add : \"emotions\",\n";
		//$tinymce .= "add_form_submit_trigger : \"false\",\n";
		$tinymce .= "external_image_list_url : \"../js/myfiles/imgdd.js\",\n";
		$tinymce .= "external_link_list_url : \"../js/myfiles/linkdd.js\"\n";
		$tinymce .= "});\n</script>\n<!-- /tinyMCE -->\n";

		$extraHead .= $tinymce;
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"themes/" . $config['themeName'] . "/scroller.js\"></script>\n";
		$bodyOnLoad = "onload=\"scrollIt()\"";
		$i =1;
		$html = "<h3>Add/Edit Events Pages</h3>\n";
		$html .= "<form id=\"addContent\" name=\"addContent\" method=\"post\" action=\"" . $this->m_myuri . "\" onsubmit=\"setScrollPos();\">\n";
		$html .= "<input type=\"submit\" name=\"newItem\" value=\""; 
		$html .= "New Item\" />\n";
		$html .= "<input type=\"submit\" name=\"cancel\" onclick=\"window.close();\" value=\"Close\" />\n";
	    $html .= "<input name=\"scrollPos\" type=\"hidden\" value=\"" . $_POST['scrollPos'] . "\"  />\n";
		$categories = new treeItems($this->m_db, "cat","");		
		$tihtml = $categories->getTreeItemsAndItemsForm($this);
	
		if(!$ispopup)
			$html .= $tihtml;
		$this->processPostArray($activeFolder, $categories, 
								$categories->getPostArray());

		$categories->setType("parentCat");
		$catHtml = $categories->getParentCatsForm($activeFolder, $this);
	
		$html .= "<p><font class=\"star\">*</font>indicates a required field</p>\n";
		if(isset($_POST['updateId'])) {
    		$html .= "<input name=\"updateId\" type=\"hidden\" value=\"" . $_POST['updateId'] ."\"  />\n";
		}
		if(isset($_POST['recurId'])) {
    		$html .= "<input name=\"recurId\" type=\"hidden\" value=\"" . $_POST['recurId'] ."\"  />\n";
		}
		$html .= "<div>\n";
		if($_POST['recurId'] > 0) {
			$html .= html_formatGroup("Recurring Edit Type", $this->recurEditInfo());
		}
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
		$html .= "<div class=\"groupTitle\">Scheduling</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Event Date and Time:</div>\n";
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
		$html .= "\t\t Start: <select name=\"eventTimeStart\">\n";
		$isSelected = "";	
		$aorp = "AM";
		for($i=0; $i<24; ++$i) {
			for($j=0; $j<4; ++$j) {
				$time24 = str_pad($i, 2, '0', STR_PAD_LEFT) . ":" . str_pad($j*15, 2, '0', STR_PAD_LEFT);
				$time = $i;
				if($i == 0) {
					$time = 12;
				} else if($i > 12) {
					$time -= 12;
					$aorp = "PM";
				}
				$time = str_pad($time, 2, '0', STR_PAD_LEFT) . ":" . str_pad($j*15, 2, '0', STR_PAD_LEFT) . " "  . $aorp;
				if(isset($_POST['eventTimeStart']) && $_POST['eventTimeStart'] == $time24)
					$isSelected = "selected=\"true\"";	
				else
					$isSelected = "";
				$html .= "\t\t<option value=\"$time24\" $isSelected>$time</option>\n";
			}
			$isSelected = "";	
		}
		$html .= "\t\t</select>\n";
		
		$html .= "\t\t End: <select name=\"eventTimeEnd\">\n";
		$isSelected = "";	
		$aorp = "AM";
		for($i=0; $i<24; ++$i) {
			for($j=0; $j<4; ++$j) {
				$time24 = str_pad($i, 2, '0', STR_PAD_LEFT) . ":" . str_pad($j*15, 2, '0', STR_PAD_LEFT);
				$time = $i;
				if($i == 0) {
					$time = 12;
				} else if($i > 12) {
					$time -= 12;
					$aorp = "PM";
				}
				$time = str_pad($time, 2, '0', STR_PAD_LEFT) . ":" . str_pad($j*15, 2, '0', STR_PAD_LEFT) . " "  . $aorp;
				if(isset($_POST['eventTimeEnd']) && $_POST['eventTimeEnd'] == $time24)
					$isSelected = "selected=\"true\"";	
				else
					$isSelected = "";
				$html .= "\t\t<option value=\"$time24\" $isSelected>$time</option>\n";
			}
			$isSelected = "";	
		}
		$aorp = "PM";
		$isSelected = "";	
		for($i=0; $i<12; ++$i) {
			for($j=0; $j<4; ++$j) {
				$time = (string)$i . ":" . str_pad($j*15, 2, '0') . " "  . $aorp;
				$time24 = (string)($i+12) . ":" . str_pad($j*15, 2, '0');
				if($_POST['eventTimeEnd'] == $time24)
					$isSelected = "selected=\"true\"";	
				$html .= "\t\t<option value=\"$time24\" $isSelected>$time</option>\n";
			}
			$isSelected = "";	
		}
		$html .= "\t\t</select>\n";
		$html .= "\t\tType: <select name=\"eventType\">\n";
		$isSelected = "";
		$typeNum = 0;
		if($_POST['eventType'] == $typeNum)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t\t<option value=\"$typeNum\" $isSelected>Standard</option>\n";
		++$typeNum;
		$isSelected = "";
		if($_POST['eventType'] == $typeNum)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t\t<option value=\"$typeNum\" $isSelected>All Day</option>\n";
		++$typeNum;
		$isSelected = "";
		if($_POST['eventType'] == $typeNum)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t\t<option value=\"$typeNum\" $isSelected>Recurs Weekly by Day</option>\n";
		++$typeNum;
		$isSelected = "";
		if($_POST['eventType'] == $typeNum)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t\t<option value=\"$typeNum\" $isSelected>Recurs Bi-Weekly by Day</option>\n";
		++$typeNum;
		$isSelected = "";
		if($_POST['eventType'] == $typeNum)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t\t<option value=\"$typeNum\" $isSelected>Recurs Monthly by Date</option>\n";
		++$typeNum;
		$isSelected = "";
		if($_POST['eventType'] == $typeNum)
			$isSelected = "selected=\"true\"";	
		$html .= "\t\t\t<option value=\"$typeNum\" $isSelected>Recurs Monthly by Day</option>\n";
		++$typeNum;
			
		$html .= "\t\t</select><br />\n";
    	$html .= "\t\t<h6>Click calendar icon to pick dates and then select the times from the dropdowns.  End date can be left blank for single day events. End date for recurring events represents when the event will stop recurring and as such rerurring events can only be single day events.  If End date is left blank on a recurring event, it will recur for one year.</h6>";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Event Description</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Description:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea class=\"mce\" wrap=\"soft\" name=\"pageContent\" rows=\"20\">" . $_POST['pageContent'] . "</textarea></div>\n";
		$html .= "\t</div>\n";
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
		$html .= "<input type=\"submit\" name=\"cancel\" onclick=\"window.close();\" value=\"Cancel\" />\n";
		if(isset($_POST['updateId']))
			$html .= "<input type=\"submit\" name=\"submitedit\" value=\"Submit Events Item Edit\" />\n";
		else
			$html .= "<input type=\"submit\" name=\"submitadd\" value=\"Add Events Item\" />\n";
		$html .= "<input type=\"submit\" name=\"delete\" value=\"Delete Item\" />\n";
		$html .= "</div>\n";
		$html .= "</form>\n";

		return $html;
	}
	
	function processAdmin() {
		GLOBAL $config;
		if(isset($_POST['cancel'])) {
			$html = "<p>Add/Edit cancelled.</p>";
		} else if(isset($_POST['delete'])) {
			if($_POST['recurId'] > 0 && $_POST['actOnAll'] == "true")
				$sql = "delete from " . $config['tableprefix'] . $this->m_pageType . " where recurId = '" . $_POST['recurId'] . "'";
			else
				$sql = "delete from " . $config['tableprefix'] . $this->m_pageType . " where id = '" . $_POST['updateId'] . "'";

			if (!$this->m_db->runQuery($sql)) {
				$html = '<p>A database error occurred in processing your '.
					"submission.\nIf this error persists, please ".
					'contact us.</p>';
			} else {
				$html = "<p>Page deleted.</p>";
				unset($_POST['updateId']);
				unset($_POST['title']);
				unset($_POST['category']);
				unset($_POST['pageUrl']);
				unset($_POST['pageContent']);
				unset($_POST['hideLeftBlocks']);
				unset($_POST['hideRightBlocks']);
				unset($_POST['minUserLevel']);
				unset($_POST['eventDateStart']);
				unset($_POST['eventTimeStart']);
				unset($_POST['eventDateEnd']);
				unset($_POST['eventTimeEnd']);
				unset($_POST['eventType']);
			}
		} else {
			if($_POST['title'] == "") {
				$html .= "<p>Please enter a title.</p>";
			} else if($_POST['parentCatActive'] == "") {
				$html .= "<p>Please select a parent category.</p>";
			} else if($_POST['pageContent'] == "") {
				$html .= "<p>Please enter content.</p>";
			} else if($_POST['eventType'] != 1 && $_POST['eventTimeStart'] == "") {
				$html .= "<p>Please enter Start Time.</p>";
			} else if($_POST['eventType'] != 1 && $_POST['eventTimeEnd'] == "") {
				$html .= "<p>Please enter End Time.</p>";
			} else {
				$title = addslashes(htmlspecialchars($_POST['title']));
				$pageUrl = addslashes(htmlspecialchars($_POST['pageUrl']));
				$pageUrl = $this->processPageUrl($pageUrl, $title);
				$category = $_POST['parentCatActive'];
				$duplicateTitle = $this->m_db->isDuplicateName("title", $title, 
						$config['tableprefix']."events", 
						"category", $category);
				$duplicateUrl = $this->m_db->isDuplicateName("pageUrl", $pageUrl, 
							$config['tableprefix']."events", 
							"category", $category);
				if(($duplicateTitle || $duplicateUrl) && $_POST['submitadd']) {
					if($duplicateTitle) {
						$html .= "<p>An item with the same title already exists in this category.\nPlease choose another title.</p>";
						unset($_POST['pageUrl']);
					} else {
						$html .= "<p>An item with the same page link name already exists in this category.\nPlease correct the link name manually in the advanced options area.</p>";
					}
				
				} else {
					$pageContent = addslashes($_POST['pageContent']);
					$hideLeftBlocks = 0;
					if($_POST['hideLeftBlocks'] == 'on')
						$hideLeftBlocks = 1;
					$hideRightBlocks = 0;
					if($_POST['hideRightBlocks'] == 'on')
						$hideRightBlocks = 1;
					$minUserLevel = $_POST['minUserLevel'];

					//events
					$eventTimeStart = $_POST['eventTimeStart'];
					$eventTimeEnd = $_POST['eventTimeEnd'];

					$start = explode('/', trim($_POST['eventDateStart']));
					$dateStrStart = $start[2]. "-" .  $start[0] . "-" . $start[1] . " " 
						. $eventTimeStart;

					$end = explode('/', trim($_POST['eventDateEnd']));
					$dateStrEnd = $end[2]. "-" . $end[0] . "-" . $end[1] . " " 
						. $eventTimeEnd;
					
					if($_POST['eventType'] == 1 || $_POST['eventType'] == 0) {
						if($_POST['eventTimeEnd'] == "") {
							$dateStrEnd = $dateStrStart;
						}
					} else {
						if($_POST['eventTimeEnd'] == "") {
							$TSDiff = 365*24*60*60; //1 year
						} else {
							$startTS = strtotime($dateStrStart);
							$endTS = strtotime($dateStrEnd);
							$TSDiff = $endTS-$startTS;
						}
						//all recurring events are single day now...
						$dateStrEnd = $dateStrStart;
					}

					//type
					$eventType = $_POST['eventType'];
					$skipNum = 7;
					$skipStr = "days";
					if($eventType == 2	&& !isset($_POST['updateId'])) {
						$repeatNum = $TSDiff/604800+1;
						$skipNum = 7;
						$skipStr = "days";
					}
					else if($eventType == 3	&& !isset($_POST['updateId'])) {
						$repeatNum = ($TSDiff/604800+1)/2;
						$skipNum = 14;
						$skipStr = "days";
					}
					else if($eventType == 4 && !isset($_POST['updateId'])) {
						$repeatNum = (($end[0] - $start[0] +12)%12) + 1;
						$skipNum = 1;
						$skipStr = "months";
					}
					else if($eventType == 5 && !isset($_POST['updateId'])) {
						$repeatNum = (($end[0] - $start[0] +12)%12) + 1;
						$selMonth = $start[0];
						$selDay = $start[1];
						$selYear = $start[2];
						
						if($selDay < 8)
							$whichWeek = 0;
						else if($selDay < 15)
							$whichWeek = 1;
						else if($selDay < 22)
							$whichWeek = 2;
						else if($selDay < 29)
							$whichWeek = 3;
						else
							$whichWeek = 4;
								
						$dateInfo = getdate(mktime(0,0,0, $selMonth, $selDay, $selYear));
						$selWeekDay = $dateInfo["wday"];
						$dayOffset = 7 + $selWeekDay;

						$eventLength = strtotime($dateStrEnd) - strtotime($dateStrStart);
					}
					else
						$repeatNum = 1;

					$errored = false;
					$doInsert = true;
					$calFactory = new calFactory($this->m_db, false);

					for($i=0; $i<$repeatNum && !$errored; ++$i) {	
						if($_POST['submitadd']) {
							$user = $config['dbuser'];	
							$eventSql = "INSERT INTO ". $config['tableprefix'] 
									. "events SET
								title = '$title',
								pageUrl = '$pageUrl',
								category = '$category',
								data = '$pageContent',
								submitDateTime = NOW(),
								submitter = '$user',
								startDateTime = '$dateStrStart',
								endDateTime = '$dateStrEnd',
								hideLeftBlocks = '$hideLeftBlocks',
								hideRightBlocks = '$hideRightBlocks',
								minUserLevel = '$minUserLevel',
								eventType = '$eventType',
								recurId = '$recurId'";
							$eventAdded = "Event Item $title added.";
							if($doInsert) {
								if(!$this->m_db->runQuery($eventSql)) {
									$errored = true;
								} else {
									$html = "<p>$eventAdded</p>";
									$_POST['updateId'] = $this->m_db->getLastInsertId();
								}
							} else {
								$doInsert = true;
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
									  minUserLevel = '$minUserLevel',
									  eventType = '$eventType'";
							if($_POST['actOnAll'] == "true")
								$eventSql .= "where recurId = '" . $_POST['recurId'] . "'";
							else
								$eventSql .= "where id = '" . $_POST['updateId'] . "'";

							$eventUp = "Event Item $title updated.";
							if (!$this->m_db->runQuery($eventSql)) {
								$errored = true;
							} else {
								$html = "<p>$eventUp</p>";
							}
						}
						//get next dates for recurring events...
						if($i == 0) {
							$recurId = $_POST['updateId'];
							$errored = $this->setFirstRecurrance($recurId);
							$pageUrlOrig = $pageUrl;
						}
						if($eventType == 2 || $eventType == 3 || $eventType == 4) {
							$dateStrStart = strftime("%Y-%m-%d %H:%M:00", 
													strtotime("$dateStrStart +" 
														. $skipNum 
														. " $skipStr"));
							$dateStrEnd = strftime("%Y-%m-%d %H:%M:00", 
													strtotime("$dateStrEnd +"
														. $skipNum 
														. " $skipStr"));
							$pageUrl = $pageUrlOrig . "-" . ($i+1);
							$this->m_db->begin();
						} else if($eventType == 5) {	
							$numDays = $calFactory->getNumDaysInMonth(($selMonth+$i+1)%12, $selYear);

							$month1stDay = mktime(0,0,0,((int)$selMonth)+$i+1,1,$selYear);
							$firstWeekDay= date("w", $month1stDay);
							
							$dayDiff = $dayOffset - $firstWeekDay; 
							if(($dayDiff)%7+(7*$whichWeek) > $numDays)
								$doInsert = false;
		
							$nextDay = $month1stDay + (($dayDiff)%7+(7*$whichWeek))*(60*60*24);
							$dateStrStart = strftime("%Y-%m-%d", $nextDay) . " "
											. $eventTimeStart;
							$dateStrEnd = strftime("%Y-%m-%d", 
													   ($nextDay+$eventLength))
										. " " . $eventTimeEnd;
							$pageUrl = $pageUrlOrig . "-" . ($i+1);
							$this->m_db->begin();
						}
					}
					if($errored) {	
						
						$html = '<p>A database error occurred in processing your '.
								"submission.\nIf this error persists, please ".
								'contact us.</p>';
						$html .= "<p>" . $this->m_db->getErrorMsg() . "</p>";

						$this->m_db->rollback();
					} else {
						$this->m_db->commit();
					}
				}
			}
		}

		$html .= $this->admin();
		return $html;
	}

	function setFirstRecurrance($recurId) {
		GLOBAL $config;
		$eventSql = "UPDATE ". $config['tableprefix'] . "events SET
			 recurId = $recurId
			 where id = " . $_POST['updateId'];
		if (!$this->m_db->runQuery($eventSql)) {
			$errored = true;
		} else {
			$errored = false;
		}

		return $errored;
	}

	function recurEditInfo() {
		$html = "";
		$html .= "<h3>Select action for recurring item</h3>\n";
		$html .= "<p>This is a reoccurring item.  Would you like to...</p>";
		$isSelected = "";
		if(isset($_POST['actOnAll']) && $_POST['actOnAll'] == "false")
			$isSelected = "checked";
		$html .= "<input type=\"radio\" name=\"actOnAll\" value=\"false\" $isSelected />Act on selected item only.<br />";
		$isSelected = "";
		if(isset($_POST['actOnAll']) && $_POST['actOnAll'] == "true")
			$isSelected = "checked";
		$html .= "<input type=\"radio\" name=\"actOnAll\" value=\"true\" $isSelected />Act on all items in the series.<br />";
		
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
				$_POST['pageContent'] = $editPage->data;
				$_POST['eventId'] = $editPage->eventId;

				$dateTime = explode(' ', $editPage->startDateTime);	
				$date = explode('-', $dateTime[0]);
				$_POST['eventDateStart'] = $date[1]. "/" .  $date[2] . "/" . $date[0]; 
				$_POST['eventTimeStart'] = substr($dateTime[1], 0, strlen($dateTime[1])-3);

				$dateTime = explode(' ', $editPage->endDateTime);	
				$date = explode('-', $dateTime[0]);
				$_POST['eventDateEnd'] = $date[1]. "/" .  $date[2] . "/" . $date[0]; 
				$_POST['eventTimeEnd'] = substr($dateTime[1], 0, strlen($dateTime[1])-3);

				$_POST['eventType'] = $editPage->eventType;
				$_POST['recurId'] = $editPage->recurId;
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
				unset($_POST['pageContent']);
				unset($_POST['hideLeftBlocks']);
				unset($_POST['hideRightBlocks']);
				unset($_POST['minUserLevel']);
				unset($_POST['eventDateStart']);
				unset($_POST['eventTimeStart']);
				unset($_POST['eventDateEnd']);
				unset($_POST['eventTimeEnd']);
				unset($_POST['eventType']);
				$activeFolder = -1;
			}

		return $activeFolder;
	}
	
	function recurringEvents($dateStrStart, $dateStrEnd, $eventType,
							 &$startDates, &$endDates)
	{
		if($eventType == 2) {
			

		} else if($eventType == 3) {
		
			array_push($startDates, $dateStrStart);
			array_push($endDates, $dateStrEnd);
	
			$startDate = strtotime($dateStrStart);
			$endDate = strtotime($dateStrEnd);
			for($mOffset=1; $mOffset<=12; ++$mOffset) {
				$newTS = strtotime("$dateStrStart +$mOffset months");
				$dbTime = strftime("%Y-%m-%d %H:%M:00", $newTS);
			}
	
		} else {
			array_push($startDates, $dateStrStart);
			array_push($endDates, $dateStrEnd);
		}
	}
	
	function getItemsFormText($category, $postArray) {
		GLOBAL $config;
		$sql = "select title from " . $config['tableprefix'] . $this->m_pageType . " where category ='" . $category . "' and minUserLevel <= " . $_SESSION['userLevel'] . " and editable=1 order by startDateTime asc";
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
