<?php


class display 
{
	var $m_db;
	var $m_theme;
	var $m_page;

	function display($db, $theme, $page) {
		$this->m_theme = $theme;
		$this->m_page = $page;
		$this->m_db = $db;
	}

	function getDisplayText() {
		$theme = $this->m_theme;
		$page = $this->m_page;
		$db = $this->m_db;
		$html = "";
		if($_GET['action'] != "popup")			
			$html .= $theme->themeHeader($db, $page);
		$html .= $theme->themeBody($db, $page);
		if($_GET['action'] != "popup")			
			$html .= $theme->themeFooter($db, $page);

		return $html;
	}

}

?>
