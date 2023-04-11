<?php

	function sortByName($a, $b) { 
   		return strcasecmp($a, $b);
	}

class file
{
	var $m_path;

	function file() {

	}
	
	function createTinyMCEContentList($db, $outPath, $catId, $onlyCurCat) {
		GLOBAL $config;
		$sql = "select title, pageUrl, category from " . $config['tableprefix'] . "content";
		if($onlyCurCat) {
			$sql .= " where category ='" . $catId . "';";
			$forText = " for this category";
			$fileCatId = "/" . $catId;
			$catId = $catId . "/";
		}	
		else { 
			$catId = "";
			$fotText = " for the entire site";
		}

		$db->runQuery($sql);
		$numPages = $db->getNumRows();
	
		$js = "var tinyMCELinkList = new Array(";
		$js .= "[\"News$forText\", \"news/".$catId."index.html\"]";
		$js .= ",[\"Gallery$forText\", \"gallery/".$catId."index.html\"]";
		$js .= ",[\"Contact Form$forText\", \"contact/".$catId."index.html\"]";
		
		for($i=0; $i<$numPages; ++$i) {
			$page = $db->getRowObject();
			if($page->category != 1) {
				$categories = new treeItems($db, "cat", "");		
				$catName = $categories->getItemPath($page->category);
			}
			else
				$catName = "";
			$js .= ',';
			$js .= "[\"$page->title\", \"content/".$catName.$page->pageUrl.".html\"]";
		}

		$ext = array("doc", "pdf", "avi", "mpg", "mpeg", "mov", "mp3", "wav", "ogg");
	
		$images = $this->getFilesInPath($ext, $fileCatId);
		foreach($images as $image) {
			$js .= ',';
			$js .= "[\"$image\", \"images$image\"]";
		}
		$js .= ");\n";
		$outPath = $outPath . "/linkdd.js";

		$this->writeFile($outPath, $js);
	}

	function createTinyMCEImageList($path, $outPath, $catId, $onlyCurCat) {
		$this->m_path = $_SERVER["DOCUMENT_ROOT"] . "/" . $path;
		
		if(!$onlyCurCat)
			$catId = "";
		else
			$catId = "/" . $catId;

		$ext = array("jpg", "png", "jpeg", "gif");
	
		$images = $this->getFilesInPath($ext, $catId);

		$js = "var tinyMCEImageList = new Array(";
		$firstPass = true;
		$jsItems = array();
		foreach($images as $image) {
		#	if(!$firstPass)
		#		$jsItems .= ',';
			$jsItem = "[\"$image\", \"images$image\"]";
			array_push($jsItems, $jsItem);
		#	$firstPass = false;
		}
		usort($jsItems, 'sortByName');
		$js .= implode(',', $jsItems);
		$js .= ");\n";

		$outPath = $outPath . "/imgdd.js";

		$this->writeFile($outPath, $js);

		return $images;
	}

	function getFilesInPath($ext, $subPath) {
		$files = array();
		if($subPath != "/icons" && $subPath != "/catIcons") {
			if(strlen($subPath) == 0)
				$skipRoot = true;
			else 
				$skipRoot = false;

			$path = $this->m_path . $subPath;
		//echo $path;	
			if($handle = opendir($path)) {
				while(false !== ($file = readdir($handle))) {
					if(is_file($path. "/" . $file)) {
						for($i=0;$i<sizeof($ext);$i++) {
							if(strpos($file, ".".$ext[$i]) !== false &&
								substr($file, 0, 2) != "t_" &&
								!$skipRoot) 
								$files[] = ($subPath . "/" . $file);
						}
					} else if(is_dir($path . "/" . $file) && $file != "." && $file !="..") {
						$files = array_merge($files, $this->getFilesInPath($ext, $subPath . "/$file"));
					}
				}
				closedir($handle);
			}
		}
  	 	return $files;
	}
		
	function writeFile($file, $outStr) {
		$handle = fopen($file, 'w');

		fwrite($handle, $outStr);
		fclose($handle);
	}

}

?>
