<?php

class category 
{
	var $m_id;
	var $m_name;
	var $m_parent;
	var $m_children;
	var $m_icon;

	var $m_active;

	
	function category($catRow) {
		$this->m_id = $catRow->id;
		$this->m_name = $catRow->name;
		$this->m_parent = $catRow->parent;
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
	
	function isRootNode() {
		if($this->m_parent == -1)
			return true;
		else
			return false;
	}
	
}

?>
