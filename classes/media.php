<?php

include_once("classes/treeItems.php");

class media
{
	var $m_db;
	var $m_filePath;
	var $m_maxSize;
	var $m_file;
	var $m_errorText;
	var $m_galleryId;
	var $m_catIcon;

	function media($db, $filePath, $maxSize, $file, $galleryId, $catIcon) {
		$this->m_filePath = $filePath;
		$this->m_maxSize = $maxSize;
		$this->m_file = $file;
		$this->m_db = $db;
		$this->m_galleryId = $galleryId;
		$this->m_catIcon = $catIcon;
	}

	function processUpload() {
		global $config;
	    if(!isset($this->m_file)) {
			$html = "<p> No file uploaded.</p>";
			return $html;
		}
   		$newMediaName = str_replace(' ','_',$this->m_file['name']);	
   		$newMediaType = $this->m_file['type'];	
   		$newMediaSize = $this->m_file['size'];	
		if($newMediaSize > $this->m_maxSize) {
			$html = "<p>File too large: ". $newMediaName . "</p>";
			return $html;
   	    }

		$duplicate = false;
		if($this->m_galleryId != -1) {	
			$duplicate = $this->m_db->isDuplicateName("name", $newMediaName, 
										  			$config['tableprefix']."media", 
													"galleryId", $this->m_galleryId);
			$fileName = $this->m_filePath . "orig_" . $newMediaName;
		}
		else { 
			$duplicate = $this->m_db->isDuplicateName("name", $newMediaName, 
										  			$config['tableprefix']."siteMedia", 
													"category", $_POST['parentCatActive']);
			$fileName = $this->m_filePath . $newMediaName;	
		}
		if (!$duplicate && move_uploaded_file($this->m_file['tmp_name'], $fileName)) {
			if($this->postMoveProcessing($newMediaName)) {
			    $html = "File is valid, and was successfully uploaded. ";
			} else {
				//delete uploaded file
				unlink($fileName);	
				$html = "<p>Post processing failed. Upload not successful.\n" 
						. $this->m_errorText . "</p>";
			}
			return $html;
		} else {
			if($duplicate)
				$html = "<p>Duplicate item found, please rename and try again</p>";
			else	
    			$html = "Error during file upload move. Please provide the following info to the admin:\n";
				$html .= "<p>$fileName</p>" ;
			unlink($this->m_file['tmp_name']);
  			 // print_r($_FILES);
		}

	 	return $html;
	}
	
	function postMoveProcessing($mediaName) {
		$rc = $this->validateMedia($mediaName);

		return $rc;
	}

	function validateMedia($mediaName) {
		if($this->m_galleryId != -1)	
			$fileName = $this->m_filePath . "orig_" . $mediaName;
		else 
			$fileName = $this->m_filePath . $mediaName;	
		
		$imageInfo = getimagesize($fileName);
		$valid = false;
		if ($imageInfo !== false) {
			$mime = $imageInfo['mime'];
			switch($mime) {
				case "image/jpeg":
					$valid = $this->resizeImage($mediaName, "jpg");
					if($this->m_catIcon !== true)
						$valid = $this->dbInsert($mediaName, 0);
				break;
				case "image/gif":
					$valid = $this->resizeImage($mediaName, "gif");
					if($this->m_catIcon !== true)
						$valid = $this->dbInsert($mediaName, 0);
				break;
				case "image/png":
					$valid = $this->resizeImage($mediaName, "png");
					if($this->m_catIcon !== true)
						$valid = $this->dbInsert($mediaName, 0);
				break;
				case "application/x-shockwave-flash":
					$valid = true;
					if($this->m_catIcon !== true)
						$valid = $this->dbInsert($mediaName, 10);
				break;
				default:
				$valid = false;
			}
		} else {
			//is it a zip file of media?
			if(strpos($this->m_file['type'], "zip") !== false) {
				$unzipCmd = "unzip -o -d " . $this->m_filePath . " " . $fileName;
				exec($unzipCmd, $files);
				$rc = false;	
				for($i=1; $i<sizeof($files); ++$i) {
					$pos = strrpos($files[$i], '/');
					$file = substr($files[$i], $pos+1);	

   					$file2 = str_replace(' ', '_', $file);
					$fullFile = "orig_" . $file2;
					rename($this->m_filePath . $file,
						   $this->m_filePath . $fullFile);
					$rc = $this->validateMedia($file2);
				}
				unlink($fileName);	
				return $rc;
			
			} else {		
				//maybe it's a movie or pdf????
//				echo "non image mime " . $this->m_file['type'] . "<br>";
				if(strpos($this->m_file['type'], "pdf") !== false)	
					$valid = $this->dbInsert($mediaName, 1);
				else if(strpos($this->m_file['type'], "video") !== false)	
					$valid = $this->dbInsert($mediaName, 2);
				else if(strpos($this->m_file['type'], "audio") !== false)	
					$valid = $this->dbInsert($mediaName, 4);
				else if(substr($mediaName, strlen($mediaName)-3, 3) == "doc")	
					$valid = $this->dbInsert($mediaName, 3);
				else 
					$valid = false;
			}
		} 
		return $valid;	
	}

	function dbInsert($mediaName, $mediaType) {
		global $config;
		if($this->m_galleryId != -1) {
			$fileName = $this->m_filePath . $mediaName;
			$jheadCmd = "/home/firstnaz/jhead " . "\"$fileName\"";
			exec($jheadCmd, $exif, $rc);
			if($rc != 0) {
				$this->m_errorText = "Error retreiving photo properties.";
				return false;
			}

			for($i=0; $i<sizeof($exif); ++$i) {
				$pos = strpos($exif[$i], ':');
				$exif[$i] = substr($exif[$i], $pos+2);
			}
			$camera = $exif[3] . " " . $exif[4];
				$sql = "INSERT INTO ". $config['tableprefix'] . "media SET
				galleryId = '$this->m_galleryId',
						  name = '$mediaName',
						  displayName = '$mediaName',
						  data = '',
						  size = '$exif[1]',
						  camera = '$camera',
						  date = '$exif[5]',
						  resolution = '$exif[6]',
						  flash = '$exif[7]',
						  focal_length = '$exif[8]',
						  exposure_time = '$exif[9]',
						  aperture = '$exif[10]',
						  iso = '$exif[11]',
						  metering = '$exif[12]',
						  exposure = '$exif[13]'";
		} else {  //content media
			$catActive = $_POST['parentCatActive'];
			if($_POST['download'] == "on")
				$download = 1;
			else	
				$download = 0;

			$data = $_POST['data'];
			
			$sql = "INSERT INTO " . $config['tableprefix'] . "siteMedia SET
				name = '$mediaName',
					 fileType = '$mediaType',
					 category = '$catActive',
					 data = '$data',
					 download = '$download'";
		}
		if (!$this->m_db->runQuery($sql)) {
			$this->m_errorText  = "<p>A database error occurred in processing your ".
				"submission.\nIf this error persists, please ".
				"contact us.</p>";
			return false;
		}

		return true;

	}

	function delMedia() {
		GLOBAL $config;
		if($this->m_galleryId == -1) { //not gallery but content media
			$sql = "select name from cc_siteMedia where id =" . $_POST['imageDel'];
		} else {
			$sql = "select name from cc_media where id =" . $_POST['imageDel'];
		}
		$this->m_db->runQuery($sql);
		$numMedia = $this->m_db->getNumRows();
		if($numMedia == 1) {
			$media = $this->m_db->getRowObject();
			$fileName = $this->m_filePath . "/" . $media->name;
			$thumbName = $this->m_filePath . "/t_" . $media->name;
		}
		
		if($this->m_galleryId == -1)
			$sql = "delete from " . $config['tableprefix'] . "siteMedia where id = '" . $_POST['imageDel'] . "'";
		else 
			$sql = "delete from " . $config['tableprefix'] . "media where id = '" . $_POST['imageDel'] . "'";
		
		if (!$this->m_db->runQuery($sql)) {
			$html = '<p>A database error occurred in processing your '.
				"submission.\nIf this error persists, please ".
				'contact us.</p>';
		} else {
			//final unline
			if(!unlink($fileName) || !unlink($thumbName)) {
				$this->m_errorText  = "<p>Error deleting file from filesystem.  Contact Admin.</p>";
			}
		}

		return;
	}

	function updateMedia() {
		global $config;
		$newName = $_POST['displayName'];
		$newDesc = $_POST['data'];
		if($this->m_galleryId != -1) {
			$sql = "UPDATE ". $config['tableprefix'] . "media SET
				displayName = '$newName',
				data = '$newDesc'
				where id = '" . $_POST['imageUpdate'] . "'";
		} else {
			$sql = "UPDATE ". $config['tableprefix'] . "siteMedia SET
				data = '$newDesc'
				where id = '" . $_POST['imageUpdate'] . "'";


		}

		if (!$this->m_db->runQuery($sql)) 
			$html = '<p>A database error occurred in processing your update';

	}

	function resizeImage($mediaName, $type) {
		if($this->m_galleryId != -1)
			$fullFileName = $this->m_filePath . "orig_" . $mediaName;
		else 
			$fullFileName = $this->m_filePath . $mediaName;

		$fileName = $this->m_filePath . $mediaName;	
		if($type == "jpg")
			$src = imagecreatefromjpeg($fullFileName);
		else if($type == "gif")
			$src = imagecreatefromgif($fullFileName);
		else if($type == "png")
			$src = imagecreatefrompng($fullFileName);

		if($src === false) {
			$this->m_errorText = "Error opening uploaded image for resize.";
			return false;
		}

		$width = imagesx($src);
		$height = imagesy($src);
		if($this->m_galleryId != -1) {
			//actual images make longest side 640 and go from there...	
			if($width > $height) {
				$newWidth = 640;
				$newHeight = ($newWidth/$width) * $height;
			} else {
				$newHeight = 640;
				$newWidth = ($newHeight/$height) * $width;
			}
	
			$dst = imagecreatetruecolor($newWidth,$newHeight);
			imagecopyresampled($dst,$src,0,0,0,0,$newWidth,$newHeight,$width,$height);
			//Full Sized Picture
			$sizeOkay = false;
			$quality = 85;

			while(!$sizeOkay) { 
				imagejpeg($dst, $fileName, $quality);
				if(filesize($fileName) <= 65000)
					$sizeOkay = true;
				else
					$quality = $quality - 5;
				clearstatcache(); 	

			}

			$cdCmd = "cd " . $this->m_filePath;		
			$jheadCmd = $cdCmd . "; /home/firstnaz/jhead -dt -te " . $fullFileName . " " . $fileName;
			exec($jheadCmd, $out, $rc);
			if($rc != 0) {
				$this->m_errorText = "Error transfering photo properties from original image.";
				return false;
			}
			Imagedestroy($dst);
		}

		//same with thumbs...
		if($width > $height) {
			$tWidth = 100;
			$tHeight = ($tWidth/$width) * $height;
		} else {
			$tHeight = 100;
			$tWidth = ($tHeight/$height) * $width;
		}
		
		$dstThumb = imagecreatetruecolor($tWidth,$tHeight);
		imagecopyresampled($dstThumb,$src,0,0,0,0,$tWidth,$tHeight,$width,$height);
		//thumbnail picture
		$tFileName = $this->m_filePath . "t_" . $mediaName;	
		imagejpeg($dstThumb, $tFileName, 60);
		
		Imagedestroy($src);
		Imagedestroy($dstThumb);
	
		return true;
	}

	//display functions

	function dispMediaAdmin($activeImg) {
		if($this->m_galleryId == -1) { //not gallery but content media
			$sql = "select id, name from cc_siteMedia where category  =" . $_POST['catActive'] . ";";
		} else {
			$sql = "select id, name from cc_media where galleryId  =" . $this->m_galleryId . ";";
		}
		$this->m_db->runQuery($sql);
		$numMedia = $this->m_db->getNumRows();
		if($numMedia < 1)
			$html = "<p>Empty gallery, add some media.</p>";
		else  {
			$html;
			$cats = new treeItems($this->m_db, "cat", "media");		
			if(isset($_POST['imageEdit'])) {
				$html .= "<p><input type=\"submit\" name=\"back\" value=\"Back to Thumbnails\" /></p>\n";
				if($this->m_galleryId == -1) { //not gallery but content media
					$sql = "select id, name, fileType, data from cc_siteMedia where id  =" . $activeImg . ";";
				} else {
					$sql = "select id, name, displayName, data from cc_media where id  =" . $activeImg . ";";
				}
				//echo $sql;
				$this->m_db->runQuery($sql);
				$numMedia = $this->m_db->getNumRows();
				if($numMedia > 1)
					$html .= "<p>Error only, add some media.</p>";
				else  {
					$media = $this->m_db->getRowObject();
					$type=0;
					if(isset($media->fileType))
						$type = $media->fileType;		
					$name = $this->createPath($media->name, $type, false);			
	
					$html .= "<div class=\"formRow\">\n";
					$html .= "<img class=\"galleryImage\" src=\"" . $name
						. "\" alt=\"Image\" /><br />\n";
					$html .= $media->name;
					$html .= "</div>\n";
					$html .= $this->imageEditOptions($media);
				}
			} else {
				$uri = $_SERVER["REQUEST_URI"];
				$pos1 = strrpos($uri, '/');
				$pos2 = strrpos($uri, '_');
				$path = substr($uri, 1, $pos1);
				$file = "gallery_admin_";

				if(isset($_POST['thumbPage']))
                    $curPage = $_POST['thumbPage'];
                else
                    $curPage = 1;
				
				$cols = $rows = 4;
                $maxThumbs = $cols*$rows;
                $numPages = (int)$numMedia/$maxThumbs;
                if($numPages > 1) {
                    if($curPage == $numPages)
                        $next = -1;
                    else
                        $next = $curPage+1;
                    if($curPage == 1)
                        $prev = -1;
                    else
                        $prev = $curPage-1;

					$html .= "<h3><p>Jump to page:</p></h3>";
					for($p=1; $p<=$numPages; ++$p) {
//						$html .= "<div class=\"navButton left\">\n";
						$html .= "<input type=\"submit\" name=\"thumbPage\" value=\"" . $p . "\" />\n";
//						$html .= "</div>";
					}
					//$html .= "<div class=\"clearer\">&nbsp;</div>\n";
                }

				$startThumb = 0;
                if($curPage >1)
                    $startThumb += $maxThumbs*($curPage-1);
                
				if($this->m_galleryId == -1) { //not gallery but content media
					$sql = "select id, name, fileType, download from cc_siteMedia where category  =" . $_POST['catActive'];
				} else {
					$sql = "select id, name from cc_media where galleryId  =" . $this->m_galleryId;
				}
				$sql = $sql . " order by 'displayName' asc limit " . $startThumb . ", " . $maxThumbs ;
               
				$this->m_db->runQuery($sql);
                $numMedia = $this->m_db->getNumRows();
                if($numMedia < 1)
                    $html = "<p>Empty thumbnail page.</p>";
				else {
					$percentage = 100/$cols;
					$curRow = 1;
					$curCol = 1;

					for($i=0; $i<$numMedia; ++$i) {
						$media = $this->m_db->getRowObject();
						$type=0;
						if(isset($media->fileType))
							$type = $media->fileType;		
						$thumbName = $this->createPath($media->name, $type, true);			

						$html .= "<div class=\"thumb\" style=\"width: $percentage%\">"
							. "<input type=\"image\" class=\"thumb\""
							. " name=\"imageEdit" . $media->id . "\" onmouseover=\"this.style.cursor='pointer';\""
							. " src=\"" . $thumbName 
							. "\" alt=\"Image thumbnail\" />"

							. "<p class=\"thumb\">" . $media->name . "</p>"
							. "<input type=\"submit\" class=\"thumb\" name=\"imageDel". $media->id 
							. "\" onmouseover=\"this.style.cursor='pointer';\" value=\"Delete\" />\n"
							. "</div>\n";
						if($curCol >= 4) {
							$html .= "<div class=\"clearer\">&nbsp;</div>\n";
							$curCol =1;
						}
						else
							$curCol++;
							
					}
					$html .= "<div class=\"clearer\">&nbsp;</div>\n";
				}
			}	
		}

		return $html;

	}

	function createPath($name, $type, $thumb) {
		switch($type){
			case 0:
				$path = $this->m_filePath;
				if($thumb)
					$path .= "t_";
				$path .= $name;
				break;
			case 1:
				$path = "images/pdf.gif";
				break;
			case 2:
				$path = "images/video.gif";
				break;
			case 3:
				$path = "images/doc.gif";
				break;
			case 4:
				$path = "images/pdf.gif";
				break;
			case 10:
				$path = "images/video.gif";
				break;
			default:
				$path = $this->m_filePath;
				if($thumb)
					$path .= "t_";
				$path .= $name;
				break;
		}
		return $path;
	}


	function imageEditOptions($media) {
		$html = "";
		if($this->m_galleryId != -1) { //gallery, not content media
			$html .= "<div class=\"formRow\">\n";
			$html .= "\t<div class=\"formHeading\">\n";
			$html .= "\t\t<div>Display Name:</div>\n";
			$html .= "\t</div>\n";
			$html .= "\t<div class=\"formEntry\">\n";
			$html .= "\t\t<input name=\"displayName\" type=\"text\" value=\"" . $media->displayName ."\" maxlength=\"100\" size=\"60\" />\n";
			$html .= "</div>\n";
			$html .= "</div>\n";
		}
		$html .= "<div class=\"formRow\">\n";
		$html .= "\t<div class=\"formHeading\">\n";
    	$html .= "\t\t<div>Description:</div>\n";
		$html .= "\t</div>\n";
		$html .= "\t<div class=\"formEntry\">\n";
    	$html .= "\t\t<div><textarea wrap=\"soft\" name=\"data\" rows=\"10\" cols=\"60\">" . $media->data . "</textarea></div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "<input type=\"submit\" name=\"imageUpdate" . $media->id . "\" value=\"Update File Info.\" />\n";

		return $html;

	}

	function getMediaIds(&$first, &$last, &$prev, &$next) {
		$lastId = -1;
		$next = -1;
		$sql = "select id,name,displayName from cc_media where galleryId  =" . $this->m_galleryId . " order by 'displayName' asc;";
		$this->m_db->runQuery($sql);
		$numMedia = $this->m_db->getNumRows();
		if($numMedia < 1)
			$html = "<p>Empty gallery, can't display.</p>";
		else {
			for($i=0; $i<$numMedia; ++$i) {
				$media = $this->m_db->getRowObject();
				if($i == 0) {
					$first = $media->id;
				} else if($i == $numMedia-1) {
					$last = $media->id;
				}

				if($media->id == $_GET['item']) {
					$prev = $lastId;
					$lastId = $media->id;
				} else if($lastId == $_GET['item']) {
					$next = $media->id;
					$lastId = $media->id;
				} else {
					$lastId = $media->id;
				}
			}
		}
		
	}

	function dispMediaHeader($path, $first, $last, $prev, $next) {
		$html ="";
		if($prev != -1) {
			$html .= "<div class=\"navButton left\">\n";
			$html .= "<a href=\"" . $path . $first
				. ".html\"><< First</a>";
			$html .= "</div>";

			$html .= "<div class=\"navButton left\">\n";
			$html .= "<a href=\"" . $path . $prev . ".html\">< Prev</a>";
			$html .= "</div>";
		}
		if($next != -1) {
			$html .= "<div class=\"navButton right\">\n";
			$html .= "<a href=\"" . $path . $last
				. ".html\">Last >></a>";
			$html .= "</div>";

			$html .= "<div class=\"navButton right\">\n";
			$html .= "<a href=\"" . $path . $next . ".html\">Next ></a>";
			$html .= "</div>";
		}
		$html .= "<div class=\"clearer\">&nbsp;</div>\n";

		return $html;					

	}

	function dispMedia($page, $rows, $cols) {
		GLOBAL $config;
		GLOBAL $extraHead;
		GLOBAL $bodyOnLoad;
		
		$html = "";
		$cat = new treeItems($this->m_db, "cat", "");
		if($_GET['action'] == "disp") { //displaying pic 
			$extraHead .= "<script language=\"javascript\" type=\"text/javascript\" src=\"themes/" . $config['themeName'] . "/scroller.js\"></script>\n";
			$bodyOnLoad = "onload=\"galleryScroll()\"";
			$this->getMediaIds($first, $last, $prev, $next);
			//navigation
			$uri = $_SERVER["REQUEST_URI"];
			$pos = strrpos($uri, '_');
			$path = substr($uri, 1, $pos);
			
			$pos2 = strrpos($uri, '/');
	  		$home = substr($path, 0, $pos2);

			$pos3 = strpos($path, '_');
	  		$pageU = substr($path, $pos3+1, $pos-$pos3-2);
			$home = $home . "thumbs_" . $pageU . "_1";
	
			$html .= "<div class=\"formRow right\">\n";
			$html .= "<h6>Album: <a href=\"". $home . ".html\">" . $page->title 
					. "</a></h6>\n";
			$html .= "</div>";
			$html .= "<div class=\"clearer\">&nbsp;</div>\n";

			$html .= $this->dispMediaHeader($path, $first, $last, $prev, $next);
			$sql = "select name, displayName, data from cc_media where id  =" . $_GET['item'] . ";";
			$this->m_db->runQuery($sql);
			$numMedia = $this->m_db->getNumRows();
			if($numMedia > 1)
				$html .= "<p>Error only, add some media.</p>";
			else  {
				$media = $this->m_db->getRowObject();
				$html .= "<img class=\"galleryImage\" src=\""
					   . $this->m_filePath . "/" . $media->name . "\" />\n";

				$html .= "<p>" . $media->displayName . "</p>";
				$html .= "<p>" . $media->data . "</p>";
			}
		} else { //show thumbnails for gallery
			$html .= $page->data;
			if($page->pageUrl == "index") {
				$emptyGallery = false;
				$sql = "select id, title, pageUrl from cc_gallery where category ='" . $page->category . "' and recordType=2 order by 'id' asc";
				$this->m_db->runQuery($sql);
				$numAlbums = $this->m_db->getNumRows();
				$albumIds=array();
				$albumNames=array();
				$albumUrls=array();
				if($numAlbums < 1) {
					$emptyGallery = true;
				} else {
					for($i=0; $i<$numAlbums; ++$i) {
						$album = $this->m_db->getRowObject();
						array_push($albumIds, $album->id);
						array_push($albumNames, $album->title);
						array_push($albumUrls, $album->pageUrl);
					}
					$albumIdsString = implode(" or galleryId = ", $albumIds);
					$sql = "select id, name from cc_media where (galleryId =" . $albumIdsString . ") group by galleryId order by 'id' asc";
					$indexCat = $cat->getParentItemName($page->category);
					$showParent = true;
					if($indexCat !== false) {
						if($indexCat == "Top Level") {
							$indexCatDesc = $indexCat . " Gallery";
							$indexCat = "/";
						} else {
							$indexCatDesc = $indexCat;
							$indexCat = "/";
						}
					}
					$catName = $cat->getActiveItemName($page->category); 	
					$galDesc = "Parent Gallery: ";


				}	
			} else {
				$sql = "select id, name from cc_media where galleryId  =" . $this->m_galleryId . ";";
				$indexCat = $cat->getActiveItemName($page->category);
				if($page->category == 1)
					$indexCatDesc = "Main Gallery";
				else
					$indexCatDesc = $indexCat;
				$catName = $indexCat;
				$indexCat .= "/";
				$galDesc = "Gallery: ";
			}
		
			$uri = $_SERVER["REQUEST_URI"];
			$pos = strrpos($uri, "/");
			$path = substr($uri, 1, $pos);
			$pos = strrpos($path, "/");
			$path = substr($path, 0, $pos+1);
			$html .= "<div class=\"formRow right\">\n";
			if($showParent === true) {
				$pos = strrpos($path, "/");
				$linkPath = substr($path, 0, $pos);
			} else 
				$linkPath = $path;
			$html .= "<h6>$galDesc<a href=\"". $linkPath . "index.html\">" . $indexCatDesc 
					. "</a></h6>\n";
			$html .= "</div>";
			$html .= "<div class=\"clearer\">&nbsp;</div>\n";

			$this->m_db->runQuery($sql);
			$numMedia = $this->m_db->getNumRows();
			if($numMedia < 1) {
				$html .= "<p>Empty gallery, add some media.</p>";
			}
			else {
				$file = "thumbs_" . $page->pageUrl . "_";

				if(isset($_GET['item']))
					$curPage = $_GET['item'];
				else
					$curPage = 1;				
				$maxThumbs = $cols*$rows;	
				$numPages = (int)$numMedia/$maxThumbs;
				if($numPages > 1) {
					if($curPage == $numPages)
						$next = -1;
					else 
						$next = $curPage+1;
					if($curPage == 1)
						$prev = -1;
					else 
						$prev = $curPage-1;
				
				if($file == "") {	
					$file = "thumb_" . $_GET['name'] . "_";
				}

				$html .= $this->dispMediaHeader($path.$file, 1, $numPages, $prev, $next);
				}
				$startThumb = 0;
				if($curPage >1)
					$startThumb += $maxThumbs*($curPage-1);
				if($page->pageUrl == "index") {	
					$sql = "select id, name, galleryId from cc_media where (galleryId =" . $albumIdsString . ") group by galleryId order by 'id' asc limit " . $startThumb . ", " . $maxThumbs;
				} else {
					$sql = "select id, name, displayName from cc_media where galleryId  =" . $this->m_galleryId . " order by 'displayName' asc limit " . $startThumb . ", " . $maxThumbs ;
				}
				$this->m_db->runQuery($sql);
				$numMedia = $this->m_db->getNumRows();
				if($numMedia < 1)
					$html = "<p>Empty thumbnail page.</p>";
				else {
					$percentage = 100/$rows;	
					$curRow = 1;
					$curCol = 1;
			
					$pos1 = strrpos($uri, "/");
					$path = substr($uri, 1, $pos1);

					for($i=0; $i<$numMedia; ++$i) {
						$media = $this->m_db->getRowObject();
						$html .= "<div class=\"thumb\" style=\"width: " 
							. $percentage . "%;\"><a";
						if($page->pageUrl == "index") {
							$this->m_filePath = "gallery/" . $page->category . "/" . $media->galleryId . "/";
							$html .= " href=\"" . $path . $albumUrls[$i+$curPage-1]  
								. ".html" . "\">"
								. "<img class=\"galleryImage\" src=\"" 
								. $this->m_filePath . "t_" 
								. $media->name . "\" alt=\"Thumbnail\" /></a>\n" 
								. "<p class=\"thumb\">" . $albumNames[$i+$curPage-1] . "</p>"
								. "</div>\n";
						} else {
							$html .= " href=\"" . $path . "disp_" . $_GET['name']
								. "_" . $media->id . ".html" . "\">"
								. "<img class=\"galleryImage\" src=\"" 
								. $this->m_filePath . "t_" 
								. $media->name . "\" alt=\"Thumbnail\" /></a>\n" 
								. "<p class=\"thumb\">" . $media->displayName . "</p>"
								. "</div>\n";
						}
						
						if($curCol == $cols) {
							$html .= "<div class=\"clearer\">&nbsp;</div>\n";
							$curCol = 1;
							$curRow++;
						} else {
							$curCol++;
						}

						if($curRow > $rows)
							break;

					}
					$html .= "<div class=\"clearer\">&nbsp;</div>\n";
				}
			}

			$catCache = new catCache($this->m_db);
			$catIds = $catCache->lineage($page->category, false, true);
			$catNames = $catCache->lineage($page->category, true, true);
	
			$id=0;
		
			$subHtml = "";
			foreach($catIds as $catId) {
				$subCatIds = $catCache->lineage($catId, false, false);
				array_push($subCatIds, $catId);
				$subCatIdsString = implode(" or category = ", $subCatIds);
				$sql = "select id from cc_gallery where (category=$subCatIdsString)";
				$this->m_db->runQuery($sql);
				$numMedia = $this->m_db->getNumRows();
				if($numMedia >= 1) {
					$uri = $_SERVER["REQUEST_URI"];
					$pos1 = strrpos($uri, '/');
					$path = substr($uri, 1, $pos1);
					$subHtml .= "<a href=\"$path" . $catNames[$id]. "/index.html\">$catNames[$id]</a> ";
				}
				$id++;
			}
			if($subHtml != "") {
				if($emptyGallery) 
					$html .= "<p>However the following subcategories contain galleries:</p>". $subHtml;
				else
					$html .= "<p>The following subcategories also contain galleries:</p>" . $subHtml;
			}
									
		}
	
		return $html;
	}

}

?>
