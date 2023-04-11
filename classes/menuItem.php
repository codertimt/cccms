<?php

class menuItem 
{
	var $m_id;
	var $m_menuId;
	var $m_name;
	var $m_parent;
	var $m_children;
	var $m_link;
	var $m_minLevel;
	var $m_maxLevel;

	var $m_active;

	
	function menuItem($itemRow) {
		$this->m_id = $itemRow->id;
		$this->m_menuId = $itemRow->menuId;
		$this->m_name = $itemRow->name;
		$this->m_parent = $itemRow->parent;
		$this->m_link = $itemRow->link;
		$this->m_minLevel = $itemRow->minUserLevel;
		$this->m_maxLevel = $itemRow->maxUserLevel;	
	}

	function minUserLevel() {
		return $this->m_minLevel;
	}
	
	function maxUserLevel() {
		return $this->m_maxLevel;
	}

	function setChildren($children) {
		$this->m_children = $children;
	}

	function children() {
		return $this->m_children;
	}
	
	function hasChildren() {
		if(sizeof($this->m_children) > 0)
			return true;
		else
			return false;
	}

	function setActive() {
		$this->m_active = true;
	}
	
	function active() {
		return $this->m_active;
	}
	
	function id() {
		return $this->m_id;
	}

	function name() {
		return $this->m_name;
	}

	function getParent() {
		return $this->m_parent;
	}
	
	function getLink() {
		return $this->m_link;
	}
	
	function isRootNode() {
		if($this->m_parent == -1)
			return true;
		else
			return false;
	}
}

?>
