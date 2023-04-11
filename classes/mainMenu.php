<?php
class mainMenu
{
	var $m_menu;

	//menuPos: l=left, r=right, h=header, f=footer
	//menuType: menu=regular menu, suckerfish=suckerfish dropdown menu
	function mainMenu($db, $menuPos, $menuType, $cat) {
		GLOBAL $config;
		include_once("blocks/$menuType.php");
		if($menuType == "menu")	 {
			$this->m_menu = new menu($config['mainMenuId'], $menuPos, $db, $cat);
		}

	}

	function getMenuBlock() {
		return $this->m_menu;
	}

}
?>
