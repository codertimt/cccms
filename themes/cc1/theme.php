<?php

include_once("themes/theme.php");

class myTheme extends theme
{
	
	function myTheme($parms) {
		$this->m_showLeftBlocks = 0;
		$this->m_showRightBlocks = 0;
		$this->m_parms = $parms;
	}

	function themeHeader($db, $page) {
		//overide default theme to display extra pictures
		$headerHtml = "<div id=\"header\">\n";
		 $headerHtml .= "<div id=\"headerName\"><h2>" . $this->m_parms['siteName'] . "</h2><h5>" . $this->m_parms['siteSlogan'] . "</h5></div>\n";
        //$headerHtml .= "<div id=\"headerSlogan\"><h3>" . $this->m_parms['siteSlogan'] . "</h3></div>\n";
        $headerHtml .= "<div class=\"clearer\"></div>";

/*
		$headerHtml .= "<div id=\"headerPics\">";
		$headerHtml .= "<img src=\"themes/". $this->m_parms['themeName'] . "/images/smhead1.jpg\" alt=\"small header images\" />";
		$headerHtml .= "<img src=\"themes/". $this->m_parms['themeName'] . "/images/smhead2.jpg\" alt=\"small header images\" />";
		$headerHtml .= "<img src=\"themes/". $this->m_parms['themeName'] . "/images/smhead3.jpg\" alt=\"small header images\" />";
		$headerHtml .= "</div>\n";	
*/
		//add any blocks to header
		$headerHtml .= $this->themeHeaderBlocks($db, $page->getPageCategory());
		

		$headerHtml .= "</div>\n";

		return $headerHtml;
	}
	
	function themeHeaderBlocks($db, $cat) {
		$html = "<div id=\"hBlocks\">\n";

		//insert mainMenu block where the theme layout wants it
		$mainMenu = new mainMenu($db, 'h', "menu", 1);
		$menuBlock = $mainMenu->getMenuBlock();
		$html .= $menuBlock->getDisplayText(); 

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
		
		$blocks = new blocks($db, $cat);
		//array of block objects
		$rightBlocks = $blocks->getBlocks('l');
	
		$numBlocks = sizeof($rightBlocks);
		$i=1;
		foreach($rightBlocks as $rightBlock) {
			$html .= $rightBlock->getDisplayText();
			if($i < $numBlocks)
				$html .= "<br />\n";
			++$i;
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
	//		$html .= $this->themeLeftBlocks($db, $page->getPageCategory());
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
		if($numBlocks > 0) {
			foreach($rightBlocks as $rightBlock) {
				$html .= $rightBlock->getDisplayText();
				if($i < $numBlocks)
					$html .= "<br />\n";
				++$i;
			}
		}
		
		/*$html .= "<div class=\"rBlock\">\n";
		$html .= "<p>ddd dddd ll ddd ddddd ddddd ddddd ddddd ddddd dddddd dddd ddddd ddddd ddddd dddddd ddddd dddddddd</p>";
		$html .= "</div>\n";
	*/	
		$html .= "</td>\n";
		return $html;
	}

	function themeFooter($db, $page) {
		return parent::themeFooter($db, $page);
	}

	function themeFooterBlocks($db, $cat) {
		return parent::themeFooterBlocks($db, $cat);
	}
	
}

?>
