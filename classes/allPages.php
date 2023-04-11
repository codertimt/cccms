<?php

class allPages
{
	var $m_db;

	function allPages($db) {
		$this->m_db = $db;
	}

	function processTreeItemsFormPostArray($activeFolder) {	
	}
	function getItemsFormText($category) {
		GLOBAL $config;
		$sql = "select title from " . $config['tableprefix'] . "content where category ='" . $category . "';";
		$this->m_db->runQuery($sql);
		$numPages = $this->m_db->getNumRows();
		for($i=0; $i<$numPages; ++$i) {
			$page = $this->m_db->getRowObject();
	
			$pageHtml .= "<img src=\"images/html.gif\" alt=\"folder\" /> ";
			$pageHtml .= "<input class=\"button\" name=\"editPage\" value=\""; 
			$pageHtml .= $page->title . "\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" /><br />\n";
		}

		$pageHtml .= "<input type=\"hidden\" name=\"pageFolder\" value=\""; 
		$pageHtml .= $category . "\">\n";
		return $pageHtml;
	}


}

?>
