<?php

class blocks
{
	var $m_db;
	var $m_cat;
	
	function blocks($db, $cat) {
		$this->m_db = $db;
		$this->m_cat = $cat;
	}

	function getBlocks($section) {
		global $config;
		$db = $this->m_db;
		$cat = $this->m_cat;
		
		$sql = "select class, position, itemId from " . $config['tableprefix'] . "blocks where position = '$section' and (category=$cat or category=1)";
		$result = $db->runQuery($sql);
		$numRows = $db->getNumRows();
		for($i=0; $i<$numRows; ++$i) {
			$row = $db->getRowObject();
			include_once("blocks/$row->class.php");
			$execStr = '$blockClass' . " = new $row->class($row->itemId, '$row->position'," . '$db' . ',$cat'. ");";

			eval($execStr);
			$blocks[$i] = $blockClass;
		}
		return $blocks;
	}	
	
}

?>
