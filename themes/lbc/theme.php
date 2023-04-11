<?php

include_once("themes/theme.php");

class myTheme extends theme
{
	
	function myTheme($parms) {
		$this->m_showLeftBlocks = 1;
		$this->m_showRightBlocks = 0;
		$this->m_parms = $parms;
	}

	function themeHeader($db, $page) {
		GLOBAL $extraHead;
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"/js/sfmenu.js\"></script>\n";
		//overide default theme to display extra pictures
		$headerHtml = "<div id=\"header\">\n";
		$headerHtml .= "<div id=\"headerName\">";
		$headerHtml .= "<img src=\"themes/". $this->m_parms['themeName'] . "/images/logo.gif\" alt=\"church logo\" />";
		$headerHtml .= "<div id=\"headerSlogan\"><h5>" . $this->m_parms['siteSlogan'] . "</h5></div>\n";
		$headerHtml .= "</div>\n";
		$headerHtml .= "<div class=\"clearer\">&nbsp;</div>";

		$headerHtml .= "<div id=\"headerPics\">";
		$headerHtml .= "<img src=\"themes/". $this->m_parms['themeName'] . "/images/church.jpg\" alt=\"small header images\" />";
		$headerHtml .= "</div>\n";	
		//add any blocks to header
		$headerHtml .= $this->themeHeaderBlocks($db, $page->getPageCategory());

		$headerHtml .= "</div>\n";

		return $headerHtml;
	}
	
	function themeHeaderBlocks($db, $cat) {
		$html = "<div id=\"hBlocks\">\n";

		$blocks = new blocks($db, $cat);
		//array of block objects
		$headerBlocks = $blocks->getBlocks('h');

		if(sizeof($headerBlocks) > 0) {
			foreach($headerBlocks as $headerBlock) {	
				$html .= $headerBlock->getDisplayText();
			}
		}
		$html .= "</div>\n";
	
		return $html;
	}

	function themeLeftBlocks($db, $cat) {
		$html .= "<td id=\"lBlocks\">\n";
		
		//insert mainMenu block where the theme layout wants it
		$mainMenu = new mainMenu($db, 'v', "menu", "");
		$menuBlock = $mainMenu->getMenuBlock();
		$html .= $menuBlock->getDisplayText(); 

		$blocks = new blocks($db, $cat);
		//array of block objects
		$rightBlocks = $blocks->getBlocks('l');
	
		$numBlocks = sizeof($rightBlocks);
		$i=1;
		if(sizeof($rightBlocks) >0) {
			foreach($rightBlocks as $rightBlock) {
				$html .= $rightBlock->getDisplayText();
				if($i < $numBlocks)
					$html .= "<br />\n";
				++$i;
			}
		}

		$html .= "</td>\n";
		return $html;
	}
	
	function themeBody($db, $page) {
		$main = "";
		$content = "";
		//$this->blockLayoutText($page, $main, $content);

		$html = "<table id=\"main\"><tr>\n";
		if($this->m_showLeftBlocks == 1 || $page->hideLeftBlocks() == 0) {
			$html .= $this->themeLeftBlocks($db, $page->getPageCategory());
		}	
		$html .= "<td id=\"content\">\n";
		
		$html .= $page->getDisplayText();
	
		$html .= "</td>\n";
		
		if($this->m_showRightBlocks == 1 || $page->hideRightBlocks() == 0) {
			$html .= $this->themeRightBlocks($db, $page->getPageCategory());
		}
		
		$html .= "</tr></table>\n";

		return $html;
	}
	
	function themeRightBlocks($db, $cat) {
		$html = "<td id=\"rBlocks\">\n";
		$blocks = new blocks($db, $cat);
		//array of block objects
		$rightBlocks = $blocks->getBlocks('r');
	
		$numBlocks = sizeof($rightBlocks);
		$i=1;
		foreach($rightBlocks as $rightBlock) {
			$html .= $rightBlock->getDisplayText();
			if($i < $numBlocks)
				$html .= "<br />\n";
			++$i;
		}
		
		/*$html .= "<div class=\"rBlock\">\n";
		$html .= "<p>ddd dddd ll ddd ddddd ddddd ddddd ddddd ddddd dddddd dddd ddddd ddddd ddddd dddddd ddddd dddddddd</p>";
		$html .= "</div>\n";
	*/	
		$html .= "</td>\n";
		return $html;
		return parent::themeRightBlocks($db, $cat);
	}

	function themeFooter($db, $page) {
		return parent::themeFooter($db, $page);
	}

	function themeFooterBlocks($db, $cat) {
		return parent::themeFooterBlocks($db, $cat);
	}
	
}

?>
