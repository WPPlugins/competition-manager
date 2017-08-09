<?php

	/**
	 * A class to help with image and file uploading.
	 * 	 	
	 */
	class FileUpload{
	    
			var $filesizelimit_max = '35840000';//35840000
			var $filenames = array();
			var $uploadFolder = "../wp-content/plugins/competition-manager/images/";
			var $filesName;
			
			function getFilenames(){
				return $this->filenames;
			}
	
	function deleteFile($file){
		if(file_exists($this->uploadFolder.$file)){
			unlink($this->uploadFolder.$file);
		}
	}
	
	function uploadFile($file='upfile'){
	
        global $options;
 				$okfiles = array();
 				
				//PREPARE FILENAME
				$name = substr($file['name'],0,strrpos($file['name'],'.'));
				$ext = substr($file['name'],strrpos($file['name'],'.'));
				$timenow = date('Gis', time());
				
				if(isset($file['caption'])){
				 $caption = $file['caption'];
				};
				//MOVE FILE into the image folder
				$tmp_name = $file['tmp_name'];

				if(move_uploaded_file($tmp_name,$this->uploadFolder.$name.$ext)){
					//RESIZE IMAGES
					chmod($this->uploadFolder.$name.$ext, 0755);
					if($file['type'] == 'image/jpeg' || $file['type'] == 'image/jpg' || $file['type'] == 'image/pjpeg'){
					  $this->resizeJPGImage($this->uploadFolder.$name.$ext, $this->uploadFolder.$name.$ext, 90,get_option('competition_picWidth'));
					}
				}
				else
				{
          return "Failed uploading file";
        }
				$this->filenames = $name.$ext;
	}
	
	
	//not in use yet
	function checkImage($key){
		$error = '';
		if($_FILES[$this->filesName]['error'][$key] == UPLOAD_ERR_OK){
  			//GET FILE DETAILS
    		$name = $_FILES[$this->filesName]['name'][$key];
    		$tmp_name = $_FILES[$this->filesName]['tmp_name'][$key];
    		$filetype = $_FILES[$this->filesName]['type'][$key];
    		$filesize = $_FILES[$this->filesName]['size'][$key];
  
    		//GET FILE EXTENSION
   			$extensions = split("[/\\.]", $name);
    		$n = count($extensions)-1;
    		$file_ext = $extensions[$n];
    		if($filesize > 2 && is_uploaded_file($_FILES[$this->filesName]['tmp_name'][$key])){

    				if ($_FILES[$this->filesName]['size'][$key] < $this->filesizelimit_max){

    					$fileinfo['tmp_name'] = $tmp_name;
    					//echo $tmp_name;
        				$fileinfo['name'] = $name;
        				$fileinfo['type'] = $filetype;
        				$fileinfo['size'] = $filesize;
        				if(isset($_POST['caption'])){
									$fileinfo['caption'] = (string)$_POST['caption'][$key];
								};
        				$okfiles[] = $fileinfo;
					
					      return $fileinfo;
					
      				}else{
								$error .= "File <strong>".$_FILES[$this->filesName]['name'][$key]."</strong> is too big";
							}

    		}else{
					$error .= '';
				}
  		}
  		return $error;
	}
	
		function resizeJPGImage($input, $output, $quality, $new_size, $resizeby = false){ 
  		if(file_exists($input)){
  			//echo 'test';
  		
    		//work out new image sizes 
    		
   			$size = getimagesize($input); 
    		if($resizeby == 'height'){ 
    			$new_height = $new_size; 
     			$new_width = ($size[0] / $size[1]) * $new_height; 
    		}else{ 
     			$new_width = $new_size; 
     			$new_height = ($size[1] / $size[0]) * $new_width; 
    		}
    		
   			//if image is bigger than what we want, resize it 
    		if($size[0] > $new_width || $size[1] > $new_height){  
     			//create new image 
     			$src_img = imagecreatefromjpeg($input); 
     			$dst_img = imagecreatetruecolor($new_width,$new_height); 
     			imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width,$new_height, $size[0], $size[1]); 
     			imagejpeg($dst_img, $output, $quality); 
     			// free memory used 
     			imagedestroy($src_img); 
     			imagedestroy($dst_img);
     			}
    		}
  		} 
	}
?>
