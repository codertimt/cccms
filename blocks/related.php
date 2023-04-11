<?php

class htmlBlock 
{
	var $m_db;
	var $m_item;
	var $m_id;
	var $m_pos;

	function htmlBlock($id, $pos, $db) {
		$this->m_db = $db;
		$this->m_id = $id;
		$this->m_pos = $pos;
	}
	
	function getDisplayText() {
		GLOBAL $config;
		$sql = "select id, title, data from " . $config['tableprefix']. "htmlBlocks where id = " . $this->m_id . ";";

		$this->m_db->runQuery($sql);
		
		$numRows = $this->m_db->getNumRows();
		if($numRows > 1) {
			$html = "Error getting htmlBlock";
		} else  {
			$this->m_item = $this->m_db->getRowObject();

			$html = "<div class=\"htmlBlock\">\n";
			$html .= "<div class=\"" . $this->m_pos . "BlockTitle\">\n";
			$html .= $this->m_item->title . "\n";
			$html .= "</div><br />\n";
			$html .= "<div class=\"" . $this->m_pos . "BlockText\">\n";
			$html .= $this->m_item->data;
			$html .= "</div>\n";
			$html .= "</div>\n";

			return $html;
		}
	}

}

?>
