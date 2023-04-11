<?php

class catCache
{
	var $m_db;

	function catCache($db) {
		$this->m_db = $db;
		$this->clearCache();
	}

	//****Called once in index.php, so later on in the call the current***** 
	//****cat should definately be available*****
	function init() {
		GLOBAL $config;
		// if at least cat1 is set make assumptions and look for other cats
		$catId = 1;
		if(isset($_GET['cat1'])) { 
			if(isset($_GET['cat6'])) 
				$numUrlCats = 6;
			else if(isset($_GET['cat5'])) 
				$numUrlCats = 5;
			else if(isset($_GET['cat4'])) 
				$numUrlCats = 4;
			else if(isset($_GET['cat3']))  
				$numUrlCats = 3;
			else if(isset($_GET['cat2']))  
				$numUrlCats = 2;
			else 
				$numUrlCats = 1;
		
			if(!$this->currentCatGood($numUrlCats)) {	
				$parentCat = 1;

				$cumulativeName = "";
				$iconExt = "";
				$catTheme = "";	
				$menuId = 1;
				for($i=0; $i<$numUrlCats; ++$i) {
					$curCat = "cat" . ($i+1);
					$sql = "select id, name, parent, themeName, iconExt, ownMenu, menuId from " . $config['tableprefix']
						.  "categories where name = '" . $_GET[$curCat]
						.  "' and parent = '" . $parentCat . "';";
					$this->m_db->runQuery($sql);
					if($this->m_db->getNumRows() != 1)
						echo "Error category " . $_GET[$curCat] . " not found.";
					else { 
						$cat = $this->m_db->getRowObject();
						$parentCat = $cat->id;
						$catId = $cat->id;
						$cumulativeName .= $cat->name;
						$_SESSION[$cumulativeName] = $catId;

						$iconExt = $cat->iconExt;
						$_SESSION[$cumulativeName . "Icon"] = $catTheme;
						
						if($cat->themeName != "") {
							$catTheme = $cat->themeName;
							$_SESSION[$cumulativeName . "Theme"] = $catTheme;
						}
						if($cat->ownMenu == 1) {
							$menuId = $cat->menuId;
							$_SESSION[$cumulativeName . "Menu"] = $menuId;
						}
						if($cat->ownBlocks == 1) {
							$_SESSION[$cumulativeName . "Blocks"] = true;
						}
					}	
				}
				if($iconExt != "") 
					$config['iconExt'] = $iconExt;
				if($catTheme != "") 
					$config['themeName'] = $catTheme;
				$config['mainMenuId'] = $menuId;
				$config['catId'] = $catId;
			} else {
				//its in the cache
				$catName = $this->getCumulativeCatName($numUrlCats);
				$config['catId'] = $_SESSION[$catName];
				$config['iconExt'] = $_SESSION[$catName."Icon"];
				if(isset($_SESSION[$catName."Theme"]))
					$config['themeName'] = $_SESSION[$catName."Theme"];
				if(isset($_SESSION[$catName."Menu"]))
					$config['mainMenuId'] = $_SESSION[$catName."Menu"];
				else
					$config['mainMenuId'] = 1;
				if(isset($_SESSION[$catName."Blocks"]))
					$config['catBlocks'] = true;
				else
					$config['catBlocks'] = false;
				
			}
		} else {
			//it's root and we don't care about the other stuff here
			$config['mainMenuId'] = 1;
			$config['catId'] = 1;
		}

	}

	function getCumulativeCatName($numUrlCats) {
		$catName = "";
		for($i=1; $i<=$numUrlCats; ++$i) {
			$curCat = "cat" . $i;
			$catName .= $_GET[$curCat];	
		}

		return $catName;
	}

	function currentCatGood($numUrlCats) {
		$catName = $this->getCumulativeCatName($numUrlCats);
		
		if(isset($_SESSION[$catName]))
			return true;
		else
			return false;
	}

	function lineage($itemId, $getName, $oneLevel) {
		if($getName)
			$lineageType = "lineageNames";
		else
			$lineageType = "lineageIds";

		if($oneLevel)
			$lineageType .= "OneLevel";

		$sessionName = $itemId.$lineageType;
		if(!isset($_SESSION[$sessionName])) {
			GLOBAL $config;
			$catIds = array();
			$sql = "select id, name, parent from " 
				. $config['tableprefix'] 
				. "categories where parent = '" 
				. $itemId . "';";
			if($this->m_db->runQuery($sql) && $this->m_db->getNumRows() > 0) {
				$numRows = $this->m_db->getNumRows();
				$rows = array();
				for($i=0; $i<$numRows; ++$i) {
					$item = $this->m_db->getRowObject();
					array_push($rows, $item);
				}
				foreach($rows as $row) {
					if(!$oneLevel)
						$catIds = array_merge($catIds, $this->lineage($row->id, $getName, $oneLevel));
					if($getName)
						array_push($catIds, $row->name);
					else
						array_push($catIds, $row->id);
				}
			} else {
				$catIds = array(); 
			}	
			$_SESSION[$sessionName] = $catIds;
		} else {
			$catIds = $_SESSION[$sessionName];
		}
		return $catIds;
	}

	function ancestory($itemId, $getName) {
		GLOBAL $config;
		$catIds = array();
		$sql = "select id, name, parent, hideParentItems from " 
			. $config['tableprefix'] 
			. "categories where id = '" 
			. $itemId . "'";
		if($this->m_db->runQuery($sql) && $this->m_db->getNumRows() == 1) {
			$item = $this->m_db->getRowObject();
			if($item->parent != -1 && $item->hideParentItems != 1) {
				$catIds = array_merge($catIds, $this->ancestory($item->parent, $getName));
			} 
			if($getName)
				array_push($catIds, $item->name);
			else
				array_push($catIds, $item->id);
		} else {
			$catIds = array();
		}	
		return $catIds;

	}

	function clearCache() {
		$fname = $_SESSION['firstName'];
		$lname = $_SESSION['lastName'];
		$email = $_SESSION['email'];
		$bio = $_SESSION['bio'];
		$userLevel = $_SESSION['userLevel'];
		$uid = $_SESSION['uid'];
		
		session_unset();

		$_SESSION['firstName'] = $fname;
		$_SESSION['lastName'] = $lname;
		$_SESSION['email'] = $email;
		$_SESSION['bio'] = $bio;
		$_SESSION['userLevel'] = $userLevel;
		$_SESSION['uid'] = $uid;
	}


	function getCategoryId($url) {
		GLOBAL $config;
		$pos1 = strpos($url, "events/");
		$pos1 += 7;
		$pos2 = strrpos($url, "/");

		$cats = array();
		if($pos1 < $pos2) {
			$catString = substr($url, $pos1, $pos2-$pos1);
			$cats = explode("/", $catString);
		}	
		// if at least cat1 is set make assumptions and look for other cats
		$catId = 1;
		if(sizeof($cats) > 0) {
			$numUrlCats = sizeof($cats);
		
			if(!$this->currentCatGood($numUrlCats)) {	
				$parentCat = 1;

				$cumulativeName = "";
				$iconExt = "";
				$catTheme = "";	
				$menuId = 1;
				for($i=0; $i<$numUrlCats; ++$i) {
					$curCat = "cat" . ($i+1);
					$sql = "select id, name, parent, themeName, iconExt, ownMenu, menuId from " . $config['tableprefix']
						.  "categories where name = '" . $cats[$i]
						.  "' and parent = '" . $parentCat . "';";
					$this->m_db->runQuery($sql);
					if($this->m_db->getNumRows() != 1)
						echo "Error category " . $cats[$i] . " not found.";
					else { 
						$cat = $this->m_db->getRowObject();
						$parentCat = $cat->id;
						$catId = $cat->id;
						$cumulativeName .= $cat->name;
					}	
				}
				return $catId;
			} else {
				//its in the cache
				$catName = $this->getCumulativeCatName($numUrlCats);
				$catId = $_SESSION[$catName];
				return $catId;
			}
		} else {
			//it's root and we don't care about the other stuff here
			return 1;
		}

	}

}

?>
