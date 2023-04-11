<?php

class pageFactory {

	var $m_db;
	function pageFactory($db) {
		$this->m_db = $db;
	}

	function getPageClass() {
		$db = $this->m_db;

		$page = $_GET['page'];

		include_once("pages/$page.php");
		$execStr = '$pageClass' . " = new $page(" . '$db' . ");";		
		eval($execStr);
		return $pageClass;
	}
}

?>
