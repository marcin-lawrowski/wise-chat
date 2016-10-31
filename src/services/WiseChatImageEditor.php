<?php

/**
 * Wise Chat images editor class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatImageEditor {
	/**
	 * GD Resource.
	 *
	 * @var resource
	 */
	private $image;
	
	/**
	* @var string
	*/
	private $imagePath;
	
	/**
	* @var array
	*/
	private $imageData;
	
	/**
	* @var integer
	*/
	private $jpgQuality = 90;
	
	public function __destruct() {
		if ($this->image !== null) {
			imagedestroy($this->image);
		}
	}
	
	/**
	* Loads the image from given file.
	*
	* @param string $imagePath
	*
	* @return null
	*/
	public function load($imagePath) {
		if (!file_exists($imagePath)) {
			throw new Exception('WiseChatImageEditor: File does not exist');
		}
		
		$imageData = getimagesize($imagePath);
		if ($imageData === false) {
			throw new Exception('WiseChatImageEditor: The file is not an image');
		}
		
		switch ($imageData[2]) {
			case IMAGETYPE_GIF:
				$this->image = imagecreatefromgif($imagePath);
				break;
			case IMAGETYPE_JPEG:
				$this->image = imagecreatefromjpeg($imagePath);
				break;
			case IMAGETYPE_PNG:
				$this->image = imagecreatefrompng($imagePath);
				break;
			case IMAGETYPE_WBMP:
				$this->image = imagecreatefromwbmp($imagePath);
				break;
			default:
				throw new Exception('WiseChatImageEditor: Image format is not supported');
		}
		
		$this->imageData = $imageData;
		$this->imagePath = $imagePath;
	}
	
	/**
	* Resizes the image.
	*
	* @param integer $maxWidth
	* @param integer $maxHeight
	* @param boolean $constraint
	*
	* @return null
	*/
	public function resize($maxWidth, $maxHeight, $constraint = true) {
		if ($this->image === null) {
			throw new Exception('WiseChatImageEditor: Image was not loaded');
		}
		
		$origSize = $this->imageData;
		$image = $this->image;
		$resizeByH = $resizeByW = false;
		
		if ($origSize[0] > $maxWidth && $maxWidth) {
			$resizeByW = true;
		}
		
		if ($origSize[1] > $maxHeight && $maxHeight) {
			$resizeByH = true;
		}
		
		if ($resizeByH && $resizeByW) {
			$resizeByH = ($origSize[0] / $maxWidth < $origSize[1] / $maxHeight);
			$resizeByW = !$resizeByH;
		}
		
		if ($resizeByW) {
			if ($constraint) {
				$newW = $maxWidth;
				$newH = ($origSize[1] * $maxWidth) / $origSize[0];
			} else {
				$newW = $maxWidth;
				$newH = $origSize[1];
			}
		} elseif ($resizeByH) {
			if ($constraint) {
				$newW = ($origSize[0] * $maxHeight) / $origSize[1];
				$newH = $maxHeight;
			} else{
				$newW = $origSize[0];
				$newH = $maxHeight;
			}
		} else{
			$newW = $origSize[0];
			$newH = $origSize[1];
		}
		
		if ($newW != $origSize[0] || $newH != $origSize[1]) {
			$imageResized = imagecreatetruecolor($newW, $newH);
			
			// take care of transparency when resizing:
			$imageType = $this->imageData[2];
			if ($imageType == IMAGETYPE_GIF) {
				$background = imagecolorallocate($imageResized, 0, 0, 0);
				imagecolortransparent($imageResized, $background);
			}
			if ($imageType == IMAGETYPE_PNG) {
				$background = imagecolorallocate($imageResized, 0, 0, 0);
				imagecolortransparent($imageResized, $background);
				imagealphablending($imageResized, false);
				imagesavealpha($imageResized, true);
			};
			
			imagecopyresampled($imageResized, $image, 0, 0, 0, 0, $newW, $newH, $origSize[0], $origSize[1]);
			imagedestroy($image);
			$this->image = $imageResized;
		}
		
		$this->imageData[0] = $newW;
		$this->imageData[1] = $newH;
	}
	
	/**
	* Fixes image orientation.
	*
	* @return null
	*/
	public function fixOrientation() {
		if ($this->image === null) {
			throw new Exception('WiseChatImageEditor: Image was not loaded');
		}

		if (!function_exists('exif_read_data') || !function_exists('exif_imagetype')) {
			return;
		}

		$type = exif_imagetype($this->imagePath);
		if (!in_array($type, array(IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM))) {
			return;
		}
		$exif = exif_read_data($this->imagePath);

		if (is_array($exif) && !empty($exif['Orientation'])) {
			switch ($exif['Orientation']) {
				case 3:
					$this->rotate(180);
					break;

				case 6:
					$this->rotate(90);
					break;

				case 8:
					$this->rotate(-90);
					break;
			}
		}
	}
	
	/**
	* Rotates the image.
	*
	* @param integer $angle
	*
	* @return null
	*/
	public function rotate($angle) {
		if ($this->image === null) {
			throw new Exception('WiseChatImageEditor: Image was not loaded');
		}
		
		$imageRotated = imagerotate($this->image, -$angle, 0);
		
		imagedestroy($this->image);
		$this->image = $imageRotated;
		
		$this->imageData[0] = imagesx($this->image);
		$this->imageData[1] = imagesy($this->image);
	}
	
	/**
	* Saves the image in the given file or the file that the image was loaded from.
	*
	* @param string $outputImagePath
	*
	* @return boolean
	*/
	public function build($outputImagePath = null) {
		if ($this->image === null) {
			throw new Exception('WiseChatImageEditor: Image was not loaded');
		}
		
		if ($outputImagePath === null) {
			$outputImagePath = $this->imagePath;
		}
		
		$imageType = $this->imageData[2];
		
		if ($imageType == IMAGETYPE_GIF) {
			$background = imagecolorallocate($this->image, 0, 0, 0);
			imagecolortransparent($this->image, $background);
				
			return imagegif($this->image, $outputImagePath);
		}
		if ($imageType == IMAGETYPE_JPEG) {
			return imagejpeg($this->image, $outputImagePath, $this->jpgQuality);
		}
		if ($imageType == IMAGETYPE_PNG) {
			$background = imagecolorallocate($this->image, 0, 0, 0);
			imagecolortransparent($this->image, $background);
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
				
			return imagepng($this->image, $outputImagePath);
		}
		if ($imageType == IMAGETYPE_WBMP) {
			return imagewbmp($this->image, $outputImagePath);
		}
		
		return false;
	}
}