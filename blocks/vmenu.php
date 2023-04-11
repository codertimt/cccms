<?php

class vmenu
{
	var $m_menuId;
	var $m_db;

	function vmenu($menuId, $db) {
		$this->m_menuId = $menuId;
		$this->m_db = $db;	
	}

	function getDisplayText() {
		GLOBAL $config;
		$db = $this->m_db;

		$sql = "select menuId, parent, name, link, minUserLevel, maxUserLevel from " . $config['tableprefix']. "menuItems where menuId = " . $this->m_menuId . ";";
		$db->runQuery($sql);
		
		$numRows = $db->getNumRows();
		$item .= "<div class=\"vmenu\" id=\"vmenu\">\n<ul>\n";
		for($i=0; $i<$numRows; ++$i) {
			$row = $db->getRowObject();
			if($_SESSION['userLevel'] >= $row->minUserLevel 
				&& $_SESSION['userLevel'] < $row->maxUserLevel) {
				$item .= "<li><a href=\"$row->link\">$row->name</a></li>\n";
			}
		}

			$item .= "</ul>\n</div>\n";
		return $item;

	}

}

?>	
