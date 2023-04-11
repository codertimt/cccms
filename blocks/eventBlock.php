<?php

include_once("classes/treeItems.php");
include_once("classes/calFactory.php");

class eventBlock
{
	var $m_db;
	var $m_item;
	var $m_id;
	var $m_pos;
	var $m_cat;

	function eventBlock($id, $pos, $db, $cat) {
		$this->m_db = $db;
		$this->m_id = $id;
		$this->m_pos = $pos;
		$this->m_cat = $cat;
	}
	
	function getDisplayText() {
		GLOBAL $config;
		$sql = "select id, title, showCalendar, showEventList, numDays from " . $config['tableprefix']. "eventBlocks where id = " . $this->m_id;
		$this->m_db->runQuery($sql);
		
		$numRows = $this->m_db->getNumRows();
		if($numRows > 1) {
			$html = "Error getting Event Block info.";
		} else  {
			$this->m_item = $this->m_db->getRowObject();

			$html = "<div class=\"" . $this->m_pos . "Block\">\n";
			$html .= "<div class=\"" . $this->m_pos . "BlockTitle\">\n";
			$html .= $this->m_item->title . "\n";
			$html .= "</div>\n";
			$html .= "<div class=\"" . $this->m_pos . "BlockText\">\n";
			if($this->m_item->showCalendar)
				$html .= $this->getCalendar();
			if($this->m_item->showEventList)
				$html .= $this->getEventList($this->m_item->numDays);
			$html .= "</div>\n";
			$html .= "</div>\n";

			return $html;
		}
	}
	
	function getAdminFormText($page) {
		GLOBAL $config;
		if(isset($_POST['catPageEdit'])) {
			$sql = "select * from " . $config['tableprefix'] . "eventBlocks where id=" . $_POST['itemId'];
			$this->m_db->runQuery($sql);
			$numRows = $this->m_db->getNumRows();
			if($numRows != 1) {
				$html = "<p>Error getting eventBlock details.</p>";
				return $html;
			} else {
				unset($_POST['showCalendar']);
				unset($_POST['showEventList']);
				unset($_POST['numDays']);
				$block = $this->m_db->getRowObject();
				if($block->showCalendar == 1)
					$_POST['showCalendar'] = 'on';
				if($block->showEventList == 1)
					$_POST['showEventList'] = 'on';
				$_POST['numDays'] = $block->numDays;
			}
		}

		$html = "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
		$html .= "\t\t<div>Display Options:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
		$checked = "";	
		if($_POST['showCalendar'] == 'on')
			$checked = " checked=\"on\" ";
		$html .= "\t\t<input name=\"showCalendar\" type=\"checkbox\"";
		$html .= "$checked>Show Calendar</input>";
		$checked = "";	
		if($_POST['showEventList'] == 'on')
			$checked = " checked=\"on\" ";
		$html .= "\t\t<input name=\"showEventList\" type=\"checkbox\"";
		$html .= "$checked>Show Event List</input>";
		$html .= "\t\t<input name=\"numDays\" type=\"text\" value=\"" . $_POST['numDays'] ."\" maxlength=\"10\" size=\"4\" />\n";
		$html .= "Number of days to show in Event List.\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";

		return $html;
	}

	function getCalendar() {
		GLOBAL $config;
		$uri = $_SERVER["REQUEST_URI"];
		$uri = substr($uri, 1);
		$pos = strpos($uri, '/');
		$pos2 = strrpos($uri, '/');
		$path = substr($uri, $pos+1, $pos2-$pos);
			
		$timestamp = time();
		$date = getdate ($timestamp);
		$today = $date['mday'];

		$curDay = getdate();
		$timestamp = mktime(0,0,0,$curDay['mon'],1,$curDay['year']);
		$date = getdate ($timestamp);
		$dow = $date['wday'];
		

	/*	if(isset($_GET['item'])) {
			$date  = $_GET['item'];
			$year = substr($date, 0, 4);
			$month = substr($date, 4, 2);
		} else */{
			$year = $curDay['year'];
			$month = str_pad($curDay['mon'], 2, "0", STR_PAD_LEFT);
		}
		

		$calFactory = new calFactory($this->m_db, $this);
		
		$calFactory->init($today, $month, $year);

		//get events
		$calFactory->initEvents($this->m_cat, "events");
		
		$fDate = date("F Y", mktime(0,0,0, $month, 1, $year));
		
		$html = "<div class=\"calBlockHeader\"><h3><a href=\"events/" . $path . "index.html\">$fDate</a></h3>";
		$html .= "</div>";

		$html .= $calFactory->getCalendarHtml(false);

		return $html;

	}

	function getEventList($numDays) {

	}
	
	function processTreeItemsFormPostArray($id) {

	}

}

?>
