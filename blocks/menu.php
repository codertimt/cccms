<?php

include_once("classes/menuItem.php");


class menu
{
	var $m_itemId;
	var $m_db;
	var $m_pos;
	var $m_adminTitles;
	var $m_cat;

	function menu($itemId, $pos, $db, $cat) {
		$this->m_itemId = $itemId;
		$this->m_pos = $pos;
		$this->m_db = $db;	
		$this->m_cat = $cat;
		$this->m_adminTitles = array();
	}

	function getDisplayText() {
		GLOBAL $config;
		$db = $this->m_db;
		$menuPos = $this->m_pos . "menu";
	
		$sql = "select id, menuId, parent, editable from " . $config['tableprefix'] . "menuItems where id = " . $this->m_itemId;
		$db->runQuery($sql);
		$numRows = $db->getNumRows();
		
		if($numRows != 1) {
			$html = "<p>Error retrieving menu.</p>";
			return $html;
		} else {
			$dbItem = $db->getRowObject();
			$parentId = $dbItem->parent;
		}
		$sql = "select id, menuId, parent, name, link, minUserLevel, maxUserLevel, orderNum, active, editable from " . $config['tableprefix']. "menuItems where menuId = " . $dbItem->menuId . " order by 'orderNum' asc;";
		
		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();
		for($i=0; $i<$numItems; ++$i) {
			$dbItems[$i] = $this->m_db->getRowObject();
		}

		$childItems = $this->organizeItems($dbItems, $parentId);
		$treeItem = $childItems[0];

		$item .= "<div class=\"$menuPos\" id=\"$menuPos\">\n";
		$item .= $this->getMenuText($treeItem, 0, $parentId);
		$item .= "</div>\n";
		return $item;

	}

	function getMenuText($treeItem, $depth, $parentId) {
			$tab = "";
			for($i=0; $i<$depth; $i++) {
				$tab .= "\t";
			}

			if($treeItem->getParent() != $parentId)
				$html.= "$tab<li><a href=\"".$treeItem->getLink(). "\">". $treeItem->name() ."</a>";
			$children = $treeItem->children();
			++$depth;
			if($treeItem->hasChildren()) {
				$html .= "$tab\n";
				$html .= "$tab<ul>\n";
			}
			foreach($children as $child) {
				$html .= $this->getMenuText($child, $depth, -1);
			}
			if($treeItem->hasChildren()) {
				$html .= "$tab</ul>\n";	
			}
			if($treeItem->getParent() != $parentId)
				$html .= "$tab</li>\n";

		return $html;
	}
	
	function organizeItems($dbItems, $parentId) {
		$childItems = array();

		foreach($dbItems as $dbItem) {
			if($dbItem->parent == $parentId &&
					$_SESSION['userLevel'] >= $dbItem->minUserLevel
                    && $_SESSION['userLevel'] <= $dbItem->maxUserLevel
					&& ($dbItem->editable == 0 || $dbItem->active == 1)) {
				$treeItem = new menuItem($dbItem);
				$childChildItems = $this->organizeItems($dbItems, $dbItem->id);
				$treeItem->setChildren($childChildItems);
				array_push($childItems, $treeItem);
			}
		}
		return $childItems;	
	}

	function getAdminFormText($page) {
		$html = "<p>No additional block settings, menus can be placed in this Menu Block from the 'Menu Items' Administration page.</p>";

		return $html;
	}

	function processAdminInsert($page, $itemName) {
		GLOBAL $config;
		$menuId = $page->getNextMenuId();
		$rc = false;
		if($menuId != -1) {
			$sql = "INSERT INTO ". $config['tableprefix'] . "menuItems SET
				name = '$itemName',
					 active = '0',
					 menuId = '$menuId',
					 editable = '0',
					 parent = '2',
					 adminActive = 1";
			if ($this->m_db->runQuery($sql)) {
				$rc = true;
			}
		}

		return $rc;
	}

	function processAdminUpdate($itemName, $id) {
		GLOBAL $config;
		$rc = false;
		$sql = "UPDATE ". $config['tableprefix'] . "menuItems SET
			name = '$itemName'
				 where id = $id";
		if ($this->m_db->runQuery($sql)) {
			$rc = true;
		}
		
		return $rc;
	}

	function processAdminDelete($id) {
		GLOBAL $config;
		$rc = false;
		$sql = "DELETE FROM ". $config['tableprefix'] . "menuItems
				 where id = '" . $id. "'";
		if ($this->m_db->runQuery($sql)) {
			$rc = true;
		}
		return $rc;
	}
	
	function processTreeItemsFormPostArray($id) {
	}

}

?>	
