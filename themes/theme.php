<?php

include_once("blocks/blocks.php");
include_once("classes/mainMenu.php");

class theme
{
	var $m_showLeftBlocks;
	var $m_showRightBlocks;
	var $m_parms;
	
	function theme() {
	}

	function themeHeader($db, $page) {
		$headerHtml = "<div class=\"headerName\">::" . $this->m_siteName . "::</div>\n";
		$headerHtml .= "<div class=\"headerSlogan\">" . $this->m_siteSlogan . "</div>\n";
		$headerHtml .= "<div class=\"headerMain\">\n";
		$headerHtml .= "<image src=\"". $this->m_parms['themeName'] . "/images/header.jpg\" />\n";
		//add any blocks to header
		$headerHtml .= $this->themeHeaderBlocks($db, $page->getPageCategory());
		$headerHtml .= "</div>\n";

		return $headerHtml;

	}
	
	function themeHeaderBlocks($db, $cat) {
		$html = "<div id=\"hBlocks\">\n";

		//insert mainMenu block where the theme layout wants it
		$mainMenu = new mainMenu($db, 'h', "menu");
		$menuBlock = $mainMenu->getMenuBlock();
		$html .= $menuBlock->getDisplayText(); 

		$blocks = new blocks($db);
		//array of block objects
		$headerBlocks = $blocks->getBlocks('h', $cat);

		foreach($headerBlocks as $headerBlock) {	
			$html .= $headerBlock->getDisplayText();
		}
		$html .= "</div>\n";
	
		return $html;
	}

	function themeLeftBlocks($db) {
		//echo "themeLeftBlocks";
	}

	/* this could get a little tricky.  If you're theme will be using a background image to simulate a full height divider bar then you will need some type of "mainWithImage" css class like below in your derived theme class.  If it's possible to have both left and right dividers that needs to be taken into consideration with seperate right, left, and both css objects.  Regardless if you are using a background image or not you need also need to take care of your "content" class as this will specify the width of the main content area */
	function blockLayoutText($page, &$main, &$content) {
		if($this->m_showRightBlocks == 0 && $this->m_showLeftBlocks == 0) {
			if($page->hideRightBlocks() == 0 
				&& $page->hideLeftBlocks() == 0) {
				$main = "mainBothBlocks";
				$content = "contentBothBlocks";
			} else if($page->hideRightBlocks() == 0 
						&& $page->hideLeftBlocks() == 1) {
				$main = "mainRightBlocks";
				$content = "contentRightBlocks";
			} else if($page->hideRightBlocks() == 1 
						&& $page->hideLeftBlocks() == 0) {
				$main = "mainLeftBlocks";
				$content = "contentLeftBlocks";
			} else {
				$main = "main";
				$content = "content";
			}
		} else if($this->m_showRightBlocks == 0 && $this->m_showLeftBlocks == 1) {
			if($page->hideRightBlocks() == 0) {
				$main = "mainBothBlocks";
				$content = "contentBothBlocks";
			} else if($page->hideRightBlocks() == 1) {
				$main = "mainLeftBlocks";
				$content = "contentLeftBlocks";
			} else {
				$main = "main";
				$content = "content";
			}
		} else  if($this->m_showRightBlocks == 1 && $this->m_showLeftBlocks == 0) {
			if($page->hideLeftBlocks() == 0) {
				$main = "mainBothBlocks";
				$content = "contentBothBlocks";
			} else if($page->hideLeftBlocks() == 1) {
				$main = "mainRighttBlocks";
				$content = "contentRightBlocks";
			} else {
				$main = "main";
				$content = "content";
			}
		} else  if($this->m_showRightBlocks == 1 && $this->m_showLeftBlocks == 1) {
			$main = "mainBothBlocks";
			$content = "contentBothBlocks";
		} else {
			$main = "main";
			$content = "content";
		}

	}
			
	function themeBody($db, $page) {
		$main = "";
		$content = "";
		$this->blockLayoutText($page, $main, $content);

		$html = "<div id=\"" . $main . "\">\n";
		if($this->m_showLeftBlocks == 1 && $page->hideLeftBlocks() == 0) {

			$html .= $this->themeLeftBlocks($db, $page->getPageCategory());
		}	
		$html .= "<div id=\"" . $content . "\">\n";
		
		$html .= $page->getDisplayText();
	
		$html .= "</div>\n";
		
		if($this->m_showRightBlocks == 1 && $page->hideRightBlocks() == 0) {
			$html .= $this->themeRightBlocks($db, $page->getPageCategory());
		}
		
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";

		return $html;
	}
	
	function themeRightBlocks($db, $cat) {
		$html = "<div id=\"rBlocks\">\n";
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
		
		$html .= "</div>\n";
		return $html;
	}

	function themeFooter($db, $page) {
		$html = "<div id=\"footer\">\n";
		//add any blocks to footer
		//floated divs...so they're out of order
		$html .= $this->themeFooterBlocks($db, $page->getPageCategory());
		$html .= "<div id=\"footerMsg\">" .$this->m_parms['footerMsg']. "</div>";
		$html .= "&nbsp;";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";

		return $html;

	}

	function themeFooterBlocks($db, $cat) {
		$html = "<div id=\"fBlocks\">\n";
		$blocks = new blocks($db, $cat);

		//array of block objects
		$footerBlocks = $blocks->getBlocks('f');

		if(sizeof($footerBlocks) > 0) {
			foreach($footerBlocks as $footerBlock) {	
				$html .= $footerBlock->getDisplayText();
			}
		}
		$html .= "</div>\n";
	
		return $html;
	}
	
}

?>
