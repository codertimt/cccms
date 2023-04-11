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

			$html = "<div class=\"" . $this->m_pos . "Block\">\n";
			$html .= "<div class=\"" . $this->m_pos . "BlockTitle\">\n";
//			$html .= $this->m_item->title . "\n";
			$html .= "</div>\n";
			$html .= "<div class=\"" . $this->m_pos . "BlockText\">\n";
			$html .= $this->m_item->data;
			$html .= "</div>\n";
			$html .= "</div>\n";

			return $html;
		}
	}
	
	function getAdminFormText($page) {
		GLOBAL $config;
		GLOBAL $extraHead;
		GLOBAL $bodyOnLoad;

		$tinymce = "<!-- tinyMCE -->\n";
		$tinymce .= "<script language=\"javascript\" type=\"text/javascript\" src=\"/js/tiny_mce/tiny_mce.js\"></script>\n";
		$tinymce .= "<script language=\"javascript\" type=\"text/javascript\">\ntinyMCE.init({mode : \"textareas\", theme : \"advanced\", content_css : \"/themes/cc1/styletm.css\",\n";
		$tinymce .= "plugins : \"advimage,emotions,contextmenu\",\n";
		$tinymce .= "theme_advanced_buttons3_add : \"emotions\",\n";
		//$tinymce .= "add_form_submit_trigger : \"false\",\n";
		$tinymce .= "external_image_list_url : \"../js/myfiles/imgdd.js\",\n";
		$tinymce .= "external_link_list_url : \"../js/myfiles/linkdd.js\"\n";
		$tinymce .= "});\n</script>\n<!-- /tinyMCE -->\n";

		$extraHead .= $tinymce;
		
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Content:<font class=\"star\">*</font></div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
    	$html .= "\t\t<div><textarea class=\"mce\" wrap=\"soft\" name=\"pageContent\" rows=\"20\">" . $_POST['pageContent'] . "</textarea></div>\n";
		$html .= "\t</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Toggle Image/Link Category Filter:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">";
		$html .= "<input type=\"submit\" name=\"toggleImgFilter\" value=\"Toggle\" />\n";
		$curCatOnly = $page->toggleImgFilterText($html);
		
		$file = new file();
		$outPath = $_SERVER["DOCUMENT_ROOT"] . "/js/myfiles";
		$file->createTinyMCEImageList("images", $outPath, $_POST['parentCatActive'], $curCatOnly);
		$file->createTinyMCEContentList($this->m_db, $outPath, $_POST['parentCatActive'], $curCatOnly);

		$html .= "\t</div>\n";
		$html .= "</div>\n";
		return $html;
	}

	function processAdminInsert($page, $itemName) {
		GLOBAL $config;
		$rc = false;
		if($menuId != -1) {
			$sql = "INSERT INTO ". $config['tableprefix'] . "htmlBlocks SET
				title = '$itemName'";
			if ($this->m_db->runQuery($sql)) {
				$rc = true;
			}
		}

		return $rc;
	}

	function processAdminUpdate($itemName, $id) {
		GLOBAL $config;

		$data = $_POST['pageContent'];
		$rc = false;
		$sql = "UPDATE ". $config['tableprefix'] . "htmlBlocks SET
				title = '$itemName',
				data = '$data'
				 where id = $id";
		if ($this->m_db->runQuery($sql)) {
			$rc = true;
		}
		
		return $rc;
	}

	function processAdminDelete($id) {
		GLOBAL $config;
		$rc = false;
		$sql = "DELETE FROM ". $config['tableprefix'] . "htmlBlocks
				 where id = '" . $id. "'";
		if ($this->m_db->runQuery($sql)) {
			$rc = true;
		}
		return $rc;
	}

	function processTreeItemsFormPostArray($id) {
		GLOBAL $config;
		$sql = "select * from " 
			. $config['tableprefix'] 
			. "htmlBlocks where id = '$id' ";
		if ($this->m_db->runQuery($sql)) {
			$block = $this->m_db->getRowObject();
			$_POST['pageContent'] = $block->data;	
		} 
	}

}

?>
