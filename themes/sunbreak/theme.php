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
		GLOBAL $extraHead;
		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"/js/sfmenuh.js\"></script>\n";
		//overide default theme to display extra pictures
		$headerHtml = "<div id=\"preheader\">\n";
		
		//insert mainMenu block where the theme layout wants it
		$mainMenu = new mainMenu($db, 'h', "menu", $cat);
		$menuBlock = $mainMenu->getMenuBlock();
		$headerHtml .= $menuBlock->getDisplayText(); 
		$headerHtml .= "</div>\n";
       
		$headerHtml .= "<div id=\"header\">\n";
		 //$headerHtml .= "<div id=\"headerSlogan\"><h3>" . $this->m_parms['siteSlogan'] . "</h3></div>\n";
		if($page->m_name == "front-page") {
		$headerHtml .= "<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\""
					. " codebase=\"http://active.macromedia.com/flash4/cabs/swflash.cab#version=4,0,0,0\""
					. " id=\"headerPicRand\" width=\"695\" height=\"160\">"
					. " <param name=\"movie\" value=\"themes/sunbreak/images/Movie1.swf\">"
					. " <param name=\"quality\" value=\"high\">"
					. " <param name=\"wmode\" value=\"transparent\">"

  					. "<embed name=\"Movie1\" src=\"themes/sunbreak/images/Movie1.swf\" quality=\"high\" "
					. " width=\"695\" height=\"160\""
					. " wmode=\"transparent\""
					. " type=\"application/x-shockwave-flash\""
    				. " pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\">"
					. " </embed>"	
					. " </object>";	
		} else {
		$headerNum = rand(1,5);		
		$headerHtml .= "<img id=\"headerPicRand\" src=\"themes/". $this->m_parms['themeName'] . "/images/header$headerNum.jpg\" alt=\"header\" />";

		}
		$headerHtml .= "<img class=\"logo\" src=\"themes/". $this->m_parms['themeName'] . "/images/welcome.gif\" alt=\"logo\" />";

		/*$headerHtml .= "<div id=\"headerName\"><h2>" . $this->m_parms['siteName'] . "</h2></div>\n";
	*/	

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
		
		
		$html .= "<br />\n";
		$blocks = new blocks($db, $cat);
		//array of block objects
		$rightBlocks = $blocks->getBlocks('l');
	
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

		$html .= "</td>\n";
		return $html;
	}
	
	function themeBody($db, $page) {
		$main = "";
		$content = "";
		//$this->blockLayoutText($page, $main, $content);

		$html = "<table id=\"main\" margin=\"0\" border=\"0\" cellspacing=\"0px\" cellpadding=\"0px\"><tr>\n";
		if(($_GET['action'] != "popup") && $this->m_showLeftBlocks == 1 || $page->hideLeftBlocks() == 0) {
//			$html .= $this->themeLeftBlocks($db, $page->getPageCategory());
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
