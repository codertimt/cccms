<?php

class treeItems
{
	var $m_db;
	var $m_type;
	var $m_treeClass;

	var $m_postArray;
	var $m_adminType;

	function treeItems($db, $type, $adminType) {
		$this->m_db = $db;
		
		$this->setType($type);
		$this->m_adminType = $adminType;
	}

	function getPostArray() {
		return $this->m_postArray;
	}
	
	function setType($type) {
		$this->m_type = $type;
		if($type == "cat" || $type == "parentCat")
			$this->m_treeClass = "categories";
		else if($type == "menu" || $type == "parentMenu")
			$this->m_treeClass = "menuItems";

		$this->m_postArray['folder'] = $this->m_type . "FolderIcon";
		$this->m_postArray['folderTitle'] = $this->m_type . "FolderTitle";
		$this->m_postArray['active'] = $this->m_type . "Active";
		$this->m_postArray['page'] = $this->m_type . "PageEdit";
	}

	function organizeItems(&$treeItems, $dbItems, $parentId, $activeId, $parent) {
		$tempItems = array();
		$rootNode = false;
		//$activeDepth = false;
		$foundActive = false;
		$isActive = false;

		foreach($dbItems as $dbItem) {
			if($dbItem->parent == $parentId) {
				$childItems = array();
		
					if($this->m_treeClass == "categories")	
						$treeItem = new category($dbItem);
					else {
						include_once("classes/menuItem.php");
						$treeItem = new menuItem($dbItem);
					}

					$rootNode = $treeItem->isRootNode();
					if($dbItem->id == $activeId) {
						$treeItem->setActive();
						$foundActive = true;
					}
				
					$foundActive |= $this->organizeItems($childItems,
								  				   $dbItems, 
												   $dbItem->id, 
												   $activeId,
												   $treeItem);
					$treeItem->setChildren($childItems);
					array_push($tempItems, $treeItem);
			}
		}
		if($foundActive || $rootNode || $parent->active()) {
			if(sizeof($tempItems) > 0) {
				$treeItems = $tempItems;
			}
		}
			
		return $foundActive || $isActive;
	}

	function getTreeItems($activeName) {
		include_once("classes/category.php");
		GLOBAL $config;
		if($this->m_adminType == "menu" && $this->m_treeClass == "menuItems") {
			$sql = "select * from " . $config['tableprefix'] 
				. $this->m_treeClass . " where adminActive=1  order by 'orderNum' asc;";
			
		} else {
			$sql = "select * from " . $config['tableprefix'] 
				. $this->m_treeClass . " order by 'name' asc;";
		}
		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();
		for($i=0; $i<$numItems; ++$i) {
			$dbItems[$i] = $this->m_db->getRowObject();
		}
		
		$treeItems = array();
		$this->organizeItems($treeItems, $dbItems, -1, $activeName, false);
		return $treeItems;
	}

	function getTreeItemAndItemText($treeItems, $depth, 
								&$tItemHtml, &$itemHtml, 
								$activeFolder, $itemClass) {
		foreach($treeItems as $treeItem) {
			for($i=0; $i<$depth; ++$i) {
				$tItemHtml .= "<img src=\"images/spacer.gif\" alt=\"spacer image\" />\n";
			}
			if($activeFolder == $treeItem->id() && $itemClass == "none") {
				$tItemHtml .= "<input type=\"hidden\" name=\"submitItem\" value=\"";
				$tItemHtml .= $treeItem->id() . "\" />\n";
			}
			$tItemHtml .= "<input type=\"image\" class=\"button\" name=\""
						. $this->m_postArray['folder']; 
			$tItemHtml .= $treeItem->id() . "\" onmouseover=\"this.style.cursor='pointer';\" ";
			$tItemHtml .= "src=\"images/folder.gif\" alt=\"folder\" />";
		//	$tItemHtml .= "</button>";
			//and the cat name which display items in the cat	
			$tItemHtml .= "<input type=\"submit\" name=\""
						. $this->m_postArray['folderTitle'];
			$tItemHtml .= $treeItem->id() . "\" value=\""; 
			$tItemHtml .= $treeItem->name() . "\"";
			if($activeFolder == $treeItem->id()) {
				$tItemHtml .= " class=\"activeButton\"";
			} else {
				$tItemHtml .= " class=\"button\"";
			}
			$tItemHtml .= "onmouseover=\"this.style.cursor='pointer';\" /><br/>";
			if($activeFolder == $treeItem->id()) {
				$_POST[$this->m_postArray['active']] = $activeFolder;
				$tItemHtml .= "<input type=\"hidden\" name=\""
							. $this->m_postArray['active'] . "\" value=\"";
				$tItemHtml .= $activeFolder . "\" />\n";
			}

			if($treeItem->hasChildren()) {
				if($treeItem->id() == $activeFolder 
					&& isset($_POST[$this->m_postArray['folderTitle']])
					&& $activeFolder != $_POST[$this->m_postArray['active']]) {
						//do nothing;
				} else {
				$this->getTreeItemAndItemText($treeItem->children(), 
										 $depth+1, 
										 $tItemHtml, 
										 $itemHtml,
										 $activeFolder,
										 $itemClass);
				}
			}		
			if($treeItem->active()) {
				if($itemClass != "none")	
					$itemHtml .=  $itemClass->getItemsFormText($treeItem->id(), $this->m_postArray);
			}
		}
	}
	
	function parseImageButtonText($buttonName) {
		$keys = array_keys($_POST);
		$length = strlen($buttonName);
		foreach($keys as $key) {
			$pos = strpos($key, $buttonName);
			if($pos !== false) {
				$offset = $pos + $length; // lenght of the word $buttonName
				$value = substr($key, $offset);
				$pos = strpos($value, '_');
				if($pos !== false) {
					$value = substr($value, 0, $pos);
					$_POST[$buttonName] = $value;
				} else { //no underscore just take number...
					$value = substr($value, 0);
					$_POST[$buttonName] = $value;
				}
			} 
		}

		if(isset($_POST[$buttonName])) {
			$curItem = $_POST[$buttonName];
		} else {
			$curItem = -1;
		}
		return $curItem;

	}
	
	function getAllItemsForm(&$tItemHtml, &$itemHtml, $itemClass) {
		GLOBAL $config;
		$activeFolder = -1;
		if($activeFolder == -1) 
			$activeFolder = $this->parseImageButtonText($this->m_postArray['folder']);
		if($activeFolder == -1) 
			$activeFolder = $this->parseImageButtonText($this->m_postArray['folderTitle']);
			
		if($activeFolder == -1 && isset($_POST[$this->m_postArray['active']])) {
			$activeFolder = $_POST[$this->m_postArray['active']];
		}
			
		if($activeFolder >= 1) 
			$this->processTreeItemsFormPostArray($activeFolder, $this->m_postArray);

		$items = $this->getTreeItems($activeFolder);
		$this->getTreeItemAndItemText($items, 
									   0, 
									   $tItemHtml, 
									   $itemHtml, 
									   $activeFolder,
									   $itemClass);
	}

	function getParentCatsForm($activeFolder, $itemClass) {
		GLOBAL $config;
		if($activeFolder == -1) {
			$activeFolder = $this->parseImageButtonText($this->m_postArray['folder']);
			if($activeFolder == -1) {
				$activeFolder = $this->parseImageButtonText($this->m_postArray['folderTitle']);
			}
			if($activeFolder == -1) 
				$activeFolder = $_POST[$this->m_postArray['active']];
		}		

		$items = $this->getTreeItems($activeFolder);
		$tItemHtml = "<div class=\"formTreeFull\">\n";
		$this->getTreeItemAndItemText($items, 
									   0, 
									   $tItemHtml, 
									   $itemHtml, 
									   $activeFolder,
									   $itemClass);
		$tItemHtml .= "</div>\n";
		return $tItemHtml;

	}

	function getTreeItemsAndItemsForm($itemClass) {
		GLOBAL $extraHead;
		GLOBAL $config;

		$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"js/content.js\"></script>";
		$html .= "<div class=\"group\">\n";
		$html .= "<div class=\"groupTitle\">Current Items</div>\n";
		
		$html .= "<img src=\"images/html.gif\" alt=\"folder\" /> ";
		$html .= "<input class=\"button\" name=\"newItem\" value=\""; 
		$html .= "New Item\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" /><br />\n";
		//logic here is if activeFolder gets set with one of the parseImageButton calls we've selected a treeItem, FIXME add more desc	
		$activeFolder = -1;
			$activeFolder = $this->parseImageButtonText($this->m_postArray['folder']);
			if($activeFolder == -1) {
				$activeFolder = $this->parseImageButtonText($this->m_postArray['folderTitle']);
			}
			if($itemClass != "none") 
				$itemClass->processTreeItemsFormPostArray($activeFolder, $this->m_postArray);
			if($activeFolder == -1) 
				$activeFolder = $_POST[$this->m_postArray['active']];

		$items = $this->getTreeItems($activeFolder);
		$this->getTreeItemAndItemText($items, 
									   0, 
									   $tItemHtml, 
									   $itemHtml, 
									   $activeFolder,
									   $itemClass);

		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\tItem Tree\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<center>Select Item to Edit</center>\n";
		$html .= "\t</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"formRowWhite\">\n";
		$html .= "\t<div class=\"formTree\">\n";
    	$html .= $tItemHtml;
		$html .= "\t</div>\n";
		$html .= "&nbsp;"; //make ie realize there is something there. so 
		$html .= "\t<div class=\"formList\">\n";
    	$html .= $itemHtml;
		$html .= "\t</div>\n";
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";
		
		$html .= "</div>\n";
		$html .= "</div>\n";
//		$html .= "</form>\n";

		return $html;
	}

	function getItemPath($catId) {
		GLOBAL $config;
		$sql = "select name, parent,iconExt from " . $config['tableprefix'] . $this->m_treeClass . " where id = $catId;";

		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();
		if($numItems != 1 )
			$catPath = "";
		else {
			$cat = $this->m_db->getRowObject();
			//got the iconExt for free while I was here, lets place it somewhere nice to access
			$config['iconExt'] = $cat->iconExt;
			if($cat->parent == -1) {
				$catPath = "";	
			} else if($cat->parent != 1) { //mysql key
				$catPath .= $this->getItemPath($cat->parent);
				$catPath .= $cat->name . "/";
			} else {
				$catPath .= $cat->name . "/";
			}
		}
		return $catPath;
	}

	function getParentItemName($itemId) {
		GLOBAL $config;
		if($itemId == 1)
			return false;

		$sql = "select parent from " 
				. $config['tableprefix'] 
				. $this->m_treeClass
				. " where id = '" 
				. $itemId . "';";
		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();
		if($numItems != 1)
			return "Error getting parent Item";
		else {
			$item = $this->m_db->getRowObject();
			if($item->parent == 1)
				return "Top Level";

			$sql = "select id, name from " 
				. $config['tableprefix'] 
				. $this->m_treeClass
				. " where id = '" 
				. $item->parent . "';";
			$this->m_db->runQuery($sql);
			$numItems = $this->m_db->getNumRows();
			if($numItems != 1)
				return "Error getting Parent Item Name";
			else {
				$item = $this->m_db->getRowObject();
				return $item->name;	
			}
		}
	}

	function getActiveItemName($itemId) {
		GLOBAL $config;
		if($itemId == 1)
			return "";
		$sql = "select id, name from " 
				. $config['tableprefix'] 
				. $this->m_treeClass
				. " where id = '" 
				. $itemId . "';";
		$this->m_db->runQuery($sql);
		$numItems = $this->m_db->getNumRows();
		if($numItems > 1 || $numItems == 0)
			return "Error getting Item Name";
		else {
			$item = $this->m_db->getRowObject();
			return $item->name;	
		}
	}

	function getItemLineage($itemId) {
		GLOBAL $config;
		$lineage = array();
		$sql = "select name, parent from " 
			. $config['tableprefix'] 
			. $this->m_treeClass
			. " where id = '" 
			. $itemId . "';";
		if($this->m_db->runQuery($sql) && $this->m_db->getNumRows() == 1) {
			$item = $this->m_db->getRowObject();
			if($item->parent != -1) {
				$lineage = $this->getItemLineage($item->parent); 
			} 
			array_push($lineage, $item->name);
		} else {
			$lineage = array();
		}	

		return $lineage;
	}
		
	
	/*********Item functions**********
		These are functions related to 
		a single item and thus
		a bit different thant the
		rest of this class
	*************************************/

	function processTreeItemsFormPostArray($activeFolder, $postArray) {
		GLOBAL $config;
		if($this->m_adminType == "category") {
			if(isset($_POST['catFolderTitle'])) {
				//FIXME
				$sql = "select id, name, parent, iconExt, themeName, ownMenu, hideParentItems from " 
						. $config['tableprefix'] 
						. $this->m_treeClass . " where id = $activeFolder";
			
				$this->m_db->runQuery($sql);
				$editItem = $this->m_db->getRowObject();
				$_POST['updateId'] = $editItem->id;
				$_POST['tItemName'] = $editItem->name;
				$_POST['tItemParent'] = $editItem->parent;
				$_POST['catIconExt'] = $editItem->iconExt;
				$_POST['ownMenu'] = $editItem->ownMenu;
				$_POST['ownTheme'] = $editItem->themeName;
				$_POST['subSite'] = $editItem->hideParentItems;
			} else if(isset($_POST['newItem'])) {
				unset($_POST['updateId']);
				unset($_POST['tItemName']);
				unset($_POST['tItemParent']);
				unset($_POST['catIconExt']);
			}
		} else if($this->m_adminType == "media") {
			if(isset($_POST['catFolderTitle'])) {
				$_POST['siteMedia'] = true;
//				$_POST['updateId'] = $editItem->id;
//				$_POST['tItemName'] = $editItem->name;
//				$_POST['tItemParent'] = $editItem->id;
			} else if(isset($_POST['newItem'])) {
				unset($_POST['updateId']);
				unset($_POST['tItemName']);
				unset($_POST['tItemParent']);
				unset($_POST['catIcon']);
				unset($_POST['siteMedia']);
			}
		} else if($this->m_adminType == "blocks") {
			if(isset($_POST[$postArray['page']])) {
				$activeFolder = $_POST[$postArray['active']];
				$name = $_POST[$postArray['page']];
				$pos = strpos($name, "(");
				$name = substr($name, 0, $pos);
				$sql = "select * from " 
						. $config['tableprefix'] 
						. "blocks where name = '$name' "
						. "and category = '" 
						. $_POST['catActive']
						. "'";
				$this->m_db->runQuery($sql);
				$block = $this->m_db->getRowObject();
				$_POST['updateId'] = $block->id;
				$_POST['name'] = $block->name;
				$_POST['location'] = $block->position;
				$_POST['blockType'] = $block->class;
				$_POST['itemId'] = $block->itemId;
				$_POST['parentCatActive'] = $block->category;
				
				$blockType = $_POST['blockType'];
				include_once("blocks/$blockType.php");
				$evalStr = '$blockClass = new ' . $blockType . 
					'($_POST["updateId"], $_POST["location"], $this->m_db, $_POST["parentCatActive"]);';
				eval($evalStr);
				
				$blockClass->processTreeItemsFormPostArray($block->itemId);

			} else if(isset($_POST['newItem'])) {
				unset($_POST['updateId']);
				unset($_POST['name']);
				unset($_POST['location']);
				unset($_POST['blockType']);
				unset($_POST['category']);
				unset($_POST['parentCatActive']);
			}
		} else { //menu
			if(isset($_POST['menuFolderTitle'])) {
				//FIXME
				$sql = "select * from " 
						. $config['tableprefix'] 
						. $this->m_treeClass . " where id = $activeFolder";
			
				$this->m_db->runQuery($sql);
				$editItem = $this->m_db->getRowObject();
				$_POST['updateId'] = $editItem->id;
				$_POST['tItemName'] = $editItem->name;
				$_POST['menuParent'] = $editItem->parent;
				$_POST['menuCat'] = $editItem->category;
				$_POST['menuLink'] = $editItem->link;
				if($editItem->active == 1)
					$_POST['menuIsActive'] = "on";
				else
					unset($_POST['menuIsActive']);
				$_POST['minUserLevel'] = $editItem->minUserLevel;
				$_POST['maxUserLevel'] = $editItem->maxUserLevel;
				//opening item to edit default link tree	
				unset($_POST['catActive']);
			} else if($this->m_type == "cat" && isset($_POST['catPageEdit'])) {
				if($activeFolder > 1)  //mysql key
					$catName = $this->getItemPath($activeFolder);
				else
					$catName = "";

				if($_POST['catPageEdit'] == "News for this Category")
					$_POST['menuLink'] = "news/$catName" . "index.html";
				else if($_POST['catPageEdit'] == "Gallery for this Category")
					$_POST['menuLink'] = "gallery/$catName" . "index.html";
				else if($_POST['catPageEdit'] == "Contact Form for this Category")
					$_POST['menuLink'] = "contact/$catName" . "index.html";
				else {
					$sql = "select pageUrl from " . $config['tableprefix']. "content  where title = '" . $_POST['catPageEdit'] . "' and category = '". $activeFolder. "';";
					$this->m_db->runQuery($sql);
					$editPage = $this->m_db->getRowObject();
						$_POST['menuLink'] = "content/$catName" . $editPage->pageUrl . ".html";
					
				}
			} else if(isset($_POST['newItem'])) {
				unset($_POST['updateId']);
				unset($_POST['tItemName']);
				unset($_POST['menuParent']);
				unset($_POST['menuCat']);
				if(isset($_POST['menuLink']))
					unset($_POST['menuLink']);
				unset($_POST['catActive']);
				$_POST['menuIsActive'] = "on";
				
			}
		}
	}

	function processPostArray(&$activeFolder, $treeItems, $postArray) {
		if($this->m_adminType == "category" || $this->m_adminType == "media") {
			if($this->m_type == "cat") {
				if(isset($_POST['catFolderTitle']))
					$activeFolder = $_POST['tItemParent'];
				else 
					$activeFolder = -1;
			}
		} else { //menu admin page
			if($this->m_type == "menu") {
				if(isset($_POST['menuFolderTitle']))
					$activeFolder = $_POST['menuParent'];
				else 
					$activeFolder = -1;
			}
			if($this->m_type == "parentCat") {  //menu category
				if(isset($_POST['menuFolderTitle']))
					$activeFolder = $_POST['menuCat'];
				else 
					$activeFolder = -1;
			}
		}
	}

	function getItemsFormText($itemId, $postArray) {
		GLOBAL $config;
		$pageHtml = "";
		if($this->m_type == "cat" && $this->m_adminType == "menu") {
			$pageHtml .= "<img src=\"images/html.gif\" alt=\"News Page\" /> ";
			$pageHtml .= "<input class=\"button\" name=\""
							. $postArray['page'] . "\" value=\""; 
			$pageHtml .= "News for this Category\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" /><br />\n";
	
			$pageHtml .= "<img src=\"images/html.gif\" alt=\"folder\" /> ";
			$pageHtml .= "<input class=\"button\" name=\""
							. $postArray['page'] . "\" value=\""; 
			$pageHtml .= "Gallery for this Category\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" /><br />\n";

			$pageHtml .= "<img src=\"images/html.gif\" alt=\"folder\" /> ";
			$pageHtml .= "<input class=\"button\" name=\""
							. $postArray['page'] . "\" value=\""; 
			$pageHtml .= "Contact Form for this Category\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" /><br />\n";


			$sql = "select title from " . $config['tableprefix'] . "content where category ='" . $itemId . "' and editable=1";
			$this->m_db->runQuery($sql);
			$numPages = $this->m_db->getNumRows();
			for($i=0; $i<$numPages; ++$i) {
				$page = $this->m_db->getRowObject();
	
				$pageHtml .= "<img src=\"images/html.gif\" alt=\"folder\" /> ";
				$pageHtml .= "<input class=\"button\" name=\""
							. $postArray['page'] . "\" value=\""; 
				$pageHtml .= $page->title . "\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" /><br />\n";
			}
		} else if($this->m_type == "cat" && $this->m_adminType == "blocks") {
			$sql = "select name, class,position from " . $config['tableprefix'] . "blocks where category ='" . $itemId . "' order by position desc";
			$this->m_db->runQuery($sql);
			$numPages = $this->m_db->getNumRows();
			$oldPos = "";
			for($i=0; $i<$numPages; ++$i) {
				$page = $this->m_db->getRowObject();
				
				if($page->position != $oldPos) {
					if($page->position == "l")
						$pageHtml .= "<p style=\"margin:0;padding:0;font-weight:bold\">Left Blocks</p>";
					if($page->position == "r")
						$pageHtml .= "<p style=\"margin:0;padding:0;font-weight:bold\">Right Blocks</p>";
					if($page->position == "h")
						$pageHtml .= "<p style=\"margin:0;padding:0;font-weight:bold\">Header Blocks</p>";
					if($page->position == "f")
						$pageHtml .= "<p style=\"margin:0;padding:0;font-weight:bold\">Footer Blocks</p>";
					$oldPos = $page->position;
				}
	
				$pageHtml .= "<img src=\"images/html.gif\" alt=\"folder\" /> ";
				$pageHtml .= "<input class=\"button\" name=\""
							. $postArray['page'] . "\" value=\""; 
				$pageHtml .=  $page->name . "(" . $page->class  . ")\" type=\"submit\" onmouseover=\"this.style.cursor='pointer';\" /><br />\n";
			}
		}
		return $pageHtml;	
	}
	
	function getItemsAsList($parent, $active) {
		GLOBAL $config;
		$items = array();
		$sql = "select name from " . $config['tableprefix'] . "menuItems where parent ='" . $parent . "'and id != '" . $active . "' order by orderNum;";
		$this->m_db->runQuery($sql);
		$numPages = $this->m_db->getNumRows();
		for($i=0; $i<$numPages; ++$i) {
			$item = $this->m_db->getRowObject();
			array_push($items, $item->name);
		}

		return $items;	
	}
		
}
?>
