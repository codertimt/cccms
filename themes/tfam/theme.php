<?php

include_once("themes/theme.php");

class myTheme extends theme
{
	
	function myTheme($parms) {
		$this->m_showLeftBlocks = 0;
		$this->m_showRightBlocks = 1;
		$this->m_parms = $parms;
	}

	function themeHeader($db) {
		//overide default theme to display extra pictures

		$headerNum = rand(1,3);		
		$headerHtml = "<div id=\"header\" style=\"background-image: url('images/header$headerNum.jpg')\">\n";
		$headerHtml .= "<div id=\"headerName\"><h2>" . $this->m_parms['siteName'] . "</h2><h5>" . $this->m_parms['siteSlogan'] . "</h5></div>\n";
		//$headerHtml .= "<div id=\"headerSlogan\"><h3>" . $this->m_parms['siteSlogan'] . "</h3></div>\n";
		$headerHtml .= "<div class=\"clearer\"></div>";
		$headerHtml .= "<div id=\"headerPics\">";
		$headerHtml .= "<img src=\"themes/". $this->m_parms['themeName'] . "/images/tfamhead.jpg\" alt=\"small header images\" />";
		$headerHtml .= "</div>\n";	
		//add any blocks to header
		$headerHtml .= $this->themeHeaderBlocks($db);
		$headerHtml .= "</div>\n";

		return $headerHtml;
	}
	
	function themeHeaderBlocks($db) {
		//if we go to parent use default header blocks...
		return parent::themeHeaderBlocks($db);
	}

	function themeLeftBlocks($db) {
		parent::themeLeftBlocks($db);
	}
	
	function themeBody($db, $page) {
		return parent::themeBody($db, $page);
	}
	
	function themeRightBlocks($db) {
		return parent::themeRightBlocks($db);
	}

	function themeFooter($db) {
		return parent::themeFooter($db);
	}

	function themeFooterBlocks($db) {
		return parent::themeFooterBlocks($db);
	}
	
}

?>
