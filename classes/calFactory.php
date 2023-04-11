<?php

class calFactory {

	var $m_db;
	var $m_mini;
	var $m_today;
	var $m_month;
	var $m_year;
	var $m_dow;
	var $m_numDays;	
	var $m_events;

	function calFactory($db, $mini)
	{
		$this->m_db = $db;
		$this->m_mini = $mini;
	}
	
	function init($today, $month, $year) {
		$this->m_today = $today;
		$this->m_month = $month;
		$this->m_year = $year;

		$timestamp = mktime(0,0,0,$month,1,$year);
		$date = getdate ($timestamp);
		$this->m_dow = $date['wday'];

		$this->m_numDays = $this->getNumDaysInMonth($month, $year);
	}
	
	function getNumDaysInMonth($month, $year) {
		$numDays = 0;
		if($month == 2) {
			if(($year%4 == 0 && $year%100 != 0) ||  $year%400 == 0) //leap year.
				$numDays = 29;
			else
				$numDays = 28;
		} else if($month%2 == 0) { //if even month
			if($month < 8) //before Aug even months have 30 days, Aug and after they have 31
				$numDays = 30;
			else
				$numDays = 31;
		} else { //odd months
			if($month < 8) //before Aug odd months have 31 days, Aug and after they have 30
				$numDays = 31;
			else
				$numDays = 30;
		}
		return $numDays;
	}
	function initEvents($curCat, $pageType) {
		GLOBAL $config;
		//init storage
		$events = array();
		for($day=1; $day<=31; ++$day) {
			$daysEvents = array();
			array_push($events, $daysEvents);
		}
		//hold 32 days so we can just reference by day
		$daysEvents = array();
		array_push($events, $daysEvents);
		
		$sDate = $this->m_year . "-" . $this->m_month . "-" . "00 00:00:00";
		$eDate = $this->m_year . "-" . $this->m_month . "-" . "31 23:59:59";

		$catCache = new catCache($this->m_db);
		$catIds = $catCache->lineage($curCat, false, false);
		array_push($catIds, $curCat);
		$catIdsString = implode(" or category = ", $catIds);

		$sql = "select id, title, category, pageUrl, data, startDateTime, endDateTime, hideLeftBlocks, hideRightBlocks, minUserLevel, eventType from " . $config['tableprefix']. $pageType . " where ((startDateTime >= '$sDate' and startDateTime <= '$eDate' or endDateTime >= '$sDate' and endDateTime <= '$eDate')) and (category = ". $catIdsString . ")";
		if ($this->m_db->runQuery($sql) && $this->m_db->getNumRows() > 0) {
			$numRows = $this->m_db->getNumRows();
			for($row=0; $row<$numRows; ++$row) {
				$event = $this->m_db->getRowObject();
				$startDate = getdate(strtotime($event->startDateTime));	
				$endDate = getdate(strtotime($event->endDateTime));	
				$startDay = $startDate["mday"];
				$startMonth = $startDate["mon"];
				$endDay = $endDate["mday"];
				$endMonth = $endDate["mon"];

				$numDays = $this->getNumDaysInMonth($this->m_month, $this->m_year);
				if($startMonth < $this->m_month) {
					$startDay = 1;
				}
				if($endMonth > $this->m_month) {
					$endDay = $numDays;
				}
				while($startDay <= $endDay) {
					array_push($events[(int)$startDay], $event);
					++$startDay;
				}
			}
		}
		
		$this->m_events = $events;

	}

	function getCalTitleHtml($day) {
		$html = "";
		switch ($day) {
			case 1:
				if($this->m_mini)
					$html .= "S";
				else
					$html .= "Sun.";
				break;
			case 2:
				if($this->m_mini)
					$html .= "M";
				else
					$html .= "Mon.";
				break;
			case 3:
				if($this->m_mini)
					$html .= "T";
				else
					$html .= "Tues.";
				break;
			case 4:
				if($this->m_mini)
					$html .= "W";
				else
					$html .= "Wed.";
				break;
			case 5:
				if($this->m_mini)
					$html .= "Th";
				else
					$html .= "Thurs.";
				break;
			case 6:
				if($this->m_mini)
					$html .= "F";
				else
					$html .= "Fri.";
				break;
			case 7:
				if($this->m_mini)
					$html .= "S";
				else
					$html .= "Sat.";
				break;
		}
		return $html;
	}

	function getCalendarHtml($admin) {
		if($this->m_mini) {
			$cssCal = "calBlock";
			$cssCalTitleRow = "calBlockTitleRow"; 
			$cssCalTitle = "calBlockTitle";
			$cssCalRow = "calBlockDayRow";
			$cssCalDay = "calBlockDay";
			$cssCalDayCur = "calBlockDayCur";
			$cssCalDayWithItems = "calBlockDayWithItems";
		} else {
			$cssCal = "cal";
			$cssCalTitleRow = "calTitleRow"; 
			$cssCalTitle = "calTitle";
			$cssCalRow = "calDayRow";
			$cssCalDay = "calDay";
			$cssCalDayCur = "calDayCur";
			$cssCalDayWithItems = "calDayWithItems";
		}
		
		$html = "<table cellspacing=\"0\"+ cellpadding=\"0\" class=\"cal\">\n";

		$html .= "\t<tr class=\"$cssCalTitleRow\"><h3>\n";
		for($day=1; $day<=7; ++$day) {
			$html .= "\t\t<td class=\"$cssCalTitle";
			if($day == 1) {
				$html .= " calBorderLeft";	
			}
			$html .= "\">\n";
			$html .= $this->getCalTitleHtml($day);
			$html .= "</td>\n";
		}
		$html .= "\t</h3></tr>\n";

		//skip days until first day of month	
		$html .= "\t<tr class=\"$cssCalRow\">\n";
		for($day=1; $day<=$this->m_dow; ++$day) {
			$html .= "\t\t<td class=\"$cssCalDay";
			if($day == 1) {
				$html .= " calBorderLeft";	
			}
			$html .= "\">&nbsp</td>\n";
			$daysEvents = $this->m_events[$day];
		}
		
		$sunday = 8-$this->m_dow;
		if($sunday == 8)
			$sunday = 1;
		for($day=1; $day<=$this->m_numDays; ++$day) {
			if($day == $sunday && $day != 1) {
				$html .= "\t</tr>\n";
				$html .= "\t<tr class=\"$cssCalRow\">";
			}
			$html .= "\t\t<td class=\"$cssCalDay";
			$curDay = getdate();
			if($day == $this->m_today && $this->m_month == $curDay["mon"] 
									  && $this->m_year == $curDay["year"] )
				$html .= " $cssCalDayCur";
			if($day == $sunday) {
				$html .= " calBorderLeft";	
				$sunday += 7;
			}
			$daysEvents = $this->m_events[$day];
			$tip = "No events scheduled for this day.";
			$body = "";
//		print_r($daysEvents);
			if(sizeof($daysEvents) != 0) {
				$cat = new treeItems($this->m_db, "cat", "");
				$tip = "<ul class=\"calTip\">\n";
				$body ="<ul class=\"calTip\">\n";
				
				for($i=0; $i<sizeof($daysEvents); ++$i) {
					$fStartTime = date("M d, h:i a", strtotime($daysEvents[$i]->startDateTime));
					$fEndTime = date("M d, h:i a", strtotime($daysEvents[$i]->endDateTime));
					$catPath = $cat->getItemPath($daysEvents[$i]->category);
					$tip .= "<li>" .$daysEvents[$i]->title . "<br />" 
						. "<h6>" . $fStartTime. " - <br />"
						. $fEndTime . "</h6></li>\n";	
					
					if($admin) {
						$strlen = 17;
					} else {
						$strlen = 22;
					}
					
					if(strlen($daysEvents[$i]->title) > $strlen)
						$elipses = "...";
					else
						$elipses = "";

					$linkUrl = "events/$path" . $catPath . $daysEvents[$i]->pageUrl;
					$linkText = substr($daysEvents[$i]->title, 0, $strlen) . "$elipses<span>"
							. $daysEvents[$i]->title 
							. "<h6>" . $fStartTime . " - "
							. $fEndTime . "</h6>"	
							. "</span>\n";
	
					$body .= "<li>\n"
						. $this->createTooltipLink($admin, $linkUrl, $linkText, false,  $daysEvents[$i]->pageUrl)
						. "</li>\n";	
				}
			//	$body .= sizeof($daysEvents) . " Events\n";
				$tip .= "</ul>\n";
				$body .= "</ul>\n";
				$tip .= "<h6>Click on the date for more info.</h6>\n";
				if($day != $this->m_today || $this->m_month != $curDay["mon"] 
									  || $this->m_year != $curDay["year"] )
					$html .= " $cssCalDayWithItems";
			}
			$html .= "\">";

			$linkUrl = "events/$path" . $catPath;
			$linkText = "$day<span>$tip</span>";
			
			$html .= $this->createTooltipLink($admin, $linkUrl, $linkText, $day, ""); 
			if(!$this->m_mini)
				$html .= $body;
			$html .= "\t\t</td>\n";
		}
		
		//skip days until first day of month	
		for($day=$this->m_dow+$this->m_numDays; $day<35; ++$day) {
			$html .= "\t\t<td class=\"$cssCalDay\">&nbsp</td>\n";
		}
		$html .= "\t</tr></table>";

		$html .= "</div>\n";

		return $html;

	}

	function createTooltipLink($admin, $linkUrl, $linkText, $day, $eventName) {
		if($day !== false) { //tooltip for day
			if($admin) {
					$myDate = $this->m_year
							. str_pad($this->m_month, 2, "0", STR_PAD_LEFT) 
							. str_pad($day, 2, "0", STR_PAD_LEFT); 
					$url = "<form>"
					. "<a class=\"calTip\" href=\""
					. $linkUrl
					. "disp_day_"
					. $myDate
					. ".html\">$linkText</a>"
					. "<input type=\"image\" class=\"eventAdminLink\" src=\"images/add.png\""
					. " onClick=\"window.open('index.php?page=events&name=adminpopup&action=popup&catActive=1&addDate=$myDate','mywindow','width=800,height=580,scrollbars=yes')\">"
					. "</form>";
			} else {
				$url = "<a class=\"calTip\" href=\""
					. $linkUrl
					. "disp_day_"
					. $this->m_year 
					. str_pad($this->m_month, 2, "0", STR_PAD_LEFT) 
					. str_pad($day, 2, "0", STR_PAD_LEFT) 
					. ".html\">$linkText</a>";
			}
		} else { //tooltip for event
			if($admin) {
					$catCache = new catCache($this->m_db);
					$catId = $catCache->getCategoryId($linkUrl);
					$url = "<form>"
					. "<input type=\"image\" class=\"eventAdminLink\" src=\"images/bullet_wrench.png\""
					. " onClick=\"window.open('index.php?page=events&name=adminpopup&action=popup&catActive=$catId&catPageEdit=$eventName&allOrOne=true','mywindow','width=800,height=580,scrollbars=yes')\">"
				 	. "<a class=\"calTip\" href=\""
					. $linkUrl
					. ".html\">$linkText</a>"
					. "</form>";
			} else {
				$url = "<a class=\"calTip\" href=\""
					. $linkUrl
					. ".html\">$linkText</a>";
			}
		}

		return $url;
	}


}

?>
