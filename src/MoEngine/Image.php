<?php
namespace MoEngine;

/**
 * A modifiable Image
 * Used Imagick to processeing
 *
 * @todo better name managment to allow multiple sizes of an image
 * @package	MoEngine
 * @author Maurice Prosper <maurice.prosper@ttu.edu>
 */
abstract class Image implements Loggable {
	/**
	 * Filename
	 * @var string
	 */
	protected $name;

	/**
	 * Path to image
	 * excluding hostname and filename
	 * @var string
	 */
	protected $path;

	/**
	 * image type
	 * index to extension list
	 * @var int
	 */
	protected $type;

	/**
	 * width of image
	 * @var int
	 */
	protected $width = 0;

	/**
	 * height of image
	 * @var int
	 */
	protected $height = 0;

	/**
	 * Size of image in bytes
	 * @var int
	 */
	protected $filesize = 0;

	/**
	 * extra image data
	 * @var array
	 */
	protected $exif = array();

	/**
	 * Temporary location of image
	 * if set, modifications were made
	 * @var string
	 */
	protected $tmpname;

	/**
	 * The image is loaded and ready
	 * @var boolean
	 */
	protected $exist = false;

	/**
	 * Extension types
	 * @var array
	 */
	private static $exts = array(
		IMAGETYPE_GIF	=> 'gif',
		IMAGETYPE_JPEG	=> 'jpg',
		IMAGETYPE_PNG	=> 'png',
	);

	/**
	 * URL to Smush.it API
	 */
	const SMUSH_URL = 'http://www.smushit.com/ysmush.it/ws.php?';

	/**
	 * Makes an image
	 * if only source is used, upload it to servers
	 * if both, make object from our image
	 * if none, make an empty image
	 *
	 * @param URL|string $path URL to Image or local path
	 * @param string $name path to image to add
	 * @param int|string $ext extension of image
	 * @return Image
	 */
	public function __construct($path = null, $name = null, $ext = null) {
		// trying to load externally
		if($path instanceof URL)
			$this->loadURL($path);

		// trying to load locally
		elseif(isset($path) && isset($name) && isset($ext))
			$this->loadLocal($path, $name, $ext);
	}

	/**
	 * Loads an image from the harddrive
	 *
	 * @param string $path
	 * @param string $name
	 * @param string $ext
	 * return Image
	 */
	public function loadLocal($path, $name, $ext) {
		$this->path = $path;
		$this->name = strtolower($name);
		$this->setExtension($ext);

		if(is_file($this->getFile()))
			$this->exist = true;

		return $this;
	}

	/**
	 * Loads an image from the URL given
	 * @param URL|string $url
	 * @return Image|boolean
	 */
	public function loadURL($url) {
		// no url
		if(empty($url))
			return false;

		if(is_string($url))
			$url = new URL($url);

		// Image is already on our server
		//if($url->belongsToUs())
		//	return $this;

		$this->makeTemporary();

		// cant move to temp?
		// save data from URL
		//if( !move_uploaded_file($url, $this->tmpname) )
			$url->save($this->tmpname);

		// must have local data now
		if(!is_file($this->tmpname) || !filesize($this->tmpname)) {
			// @unlink($this->tmpname);
			return false;
		}

		$img = @getimagesize($this->tmpname);

		// not an image
		if( empty($img) ) {
			unlink($this->tmpname);
			return false;
		}

		// image type must be valid
		$this->type	= intval($img[2]);

		// is it an image?
		if(!isset($this->type))
			return false;

		// set attributes
		$this->width	= intval($img[0]);
		$this->height	= intval($img[1]);
		$this->name		= substr(trim(exec('openssl md5 ' . $this->tmpname)), -32);
		$this->filesize	= filesize($this->tmpname);
		$this->exist	= true;
		// $this->getEXIF();

		// size is unsigned
		if($this->filesize < 0)
			$this->filesize += 4294967296;

		return $this;
	}

	/**
	 * Saves EXIF data found in Image
	 * @return boolean Wether data was found
	 */
	private function getEXIF() {
		// vaild type
		if(!in_array($this->type, array(IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM)))
			return false;

		//get all EXIF data
		if(!is_readable($this->tmpname))
			return false;

		$a = @exif_read_data($this->tmpname, null, true);

		if($a) {
			$div = array_fill(0, 5, null);
			foreach(array('ApertureValue', 'ShutterSpeedValue', 'ExposureTime', 'ExposureBiasValue', 'FocalLength') as $k => $v)
				if(isset($a['EXIF'][$v])) {
					$div[$k] = $a['EXIF'][$v];
					if(strpos($div[$k], '/')) {
						$divx = explode('/', $a['EXIF'][$v]);
						$div[$k] = $divx[0] / $divx[1];
					}
				}

			$this->exif = array(	//http://stackoverflow.com/questions/2526304/php-extract-gps-exif-data
				(isset($a['EXIF']['DateTimeOriginal']) && !empty($a['EXIF']['DateTimeOriginal']) ? strtotime( $a['EXIF']['DateTimeOriginal'] ) : null),
				(isset($a['EXIF']['MeteringMode'])		? $a['EXIF']['MeteringMode']	: null),
				(isset($a['EXIF']['ISOSpeedRatings'])	? $a['EXIF']['ISOSpeedRatings']	: null),
				$div[0],
				$div[1],
				$div[2],
				$div[3],
				$div[4],
				(isset($a['IFD0']['Make'])	? $a['IFD0']['Make']	: null),
				(isset($a['IFD0']['Model'])	? $a['IFD0']['Model']	: null),
			);

			unset($div,$divx,$a);
		} else
			return false;

		return true;
	}

	/**
	 * Compresses the Image using the Smush.it API
	 * @return Image
	 */
	public function compress() {
		$this->makeTemporary();

		// send request to smush.it
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::SMUSH_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('files' => '@' . $this->tmpname));
        $json = curl_exec($ch);
        curl_close($ch);

		if(isset($json)) {
			$json = json_decode($json);

			if(empty($json->error) && isset($json->dest) && $this->filesize > $json->dest_size) {
				$newImage = new URL($json->dest);
				$newImage->save($this->tmpname);

				// update filesize
				$this->filesize = intval($json->dest_size); //filesize($this->tmpname);
			}
		}

		return $this;
	}

	/**
	 * Resizes the current image
	 * If a parameter is missing the image is scaled only using given parameter
	 *
	 * @param int $width
	 * @param int $height
	 * @return \MoEngine\Image
	 */
	public function resize($width = 0, $height = 0) {
		// no change
		if(!$this->exist || (!$width && !$height))
			return $this;

		$this->makeTemporary();

		if(!isset($width))	$width = 0;
		if(!isset($height))	$height = 0;

		// only cares about height
		if($width === 0)
			$width = intval($this->width / ($this->height / $height));

		// only cares about width
		if($height === 0)
			$height = intval($this->height / ($this->width / $width));


		try {
			$image = new \Imagick($this->tmpname);
			$image->thumbnailImage($width, $height, true);
			$image->writeImage($this->tmpname);
			$image->destroy();

			// update attributes
			$this->width	= $width;
			$this->height	= $height;
			$this->filesize	= filesize($this->tmpname);
		} catch(\ImagickException $e) {} // ignore imagemagick errors

		return $this;
	}

	/**
	 * Crops the current image
	 * If a parameter is missing the image is scaled only using given parameter
	 *
	 * @param int $width width of new image [ignore to crop only vertical]
	 * @param int $height height of new image [ignore to crop only horizontal]
	 * @param int $left number of pixels to push left [ignore to crop for horizontal center]
	 * @param int $top number of pixels to push from top [ignore to crop for vertical center]
	 * @return \MoEngine\Image
	 */
	public function crop($width = null, $height = null, $left = null, $top = null) {
		// no change
		if(!$this->exist || (!isset($left) && !isset($top) &&!isset($width) && !isset($height)))
			return $this;

		$this->makeTemporary();

		// no width crop
		if(!isset($width) || $width > $this->width)
			$width = $this->width;

		// no height crop
		if(!isset($height) || $height > $this->height)
			$height = $this->height;

		// no cropping
		if($width === $this->width && $height === $this->height)
			return $this;

		// crop vertically centerd
		if($height < $this->height && !isset($top))
			$top = intval(($this->height - $height) / 2);

		// crop horizontally centerd
		if($width < $this->width && !isset($left))
			$left = intval(($this->width - $width) / 2);

		try {
			$image = new \Imagick($this->tmpname);
			$image->cropimage($width, $height, $left, $top);
			$image->writeimage($this->tmpname);
			$image->destroy();

			// update attributes
			$this->width	= $width;
			$this->height	= $height;
			$this->filesize	= filesize($this->tmpname);
		} catch(\ImagickException $e) {} // ignore imagemagick errors

		return $this;
	}

	/**
	 * Saves changes made to image
	 * @return \MoEngine\Image
	 */
	public function save() {
		// move tmp file to real path
		if($this->exist && isset($this->tmpname)) {
			rename($this->tmpname, $this->getFile());
			unset($this->tmpname);
		}

		return $this;
	}

	/**
	 * Removes unused temp image, if any
	 */
	public function __destruct() {
		if(isset($this->tmpname) && is_file($this->tmpname))
			unlink($this->tmpname);
	}

	/**
	 * Live URL for image
	 * @return URL
	 */
	public function getURL() {
		return new URL(\URL::IMG .
				trim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
				$this->name .'.'. $this->getExtension());
	}

	/**
	 * Location of image on server
	 * @return string
	 */
	public function getFile() {
		return \DIR::IMG .
				trim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
				$this->name .'.'. $this->getExtension();
	}

	/**
	 * Creates a temporary copy to mess around with
	 * @return \MoEngine\Image
	 */
	private function makeTemporary() {
		if(!isset($this->tmpname)) {
			// make new tempfile
			$this->tmpname = \DIR::TMP . 'image_' . \NOW .'-'. md5( mt_rand() );

			// if current image is found, copy
			if(is_file($this->getFile()))
				copy($this->getFile(), $this->tmpname);
		}

		$this->exist = true;

		return $this;
	}

	/**
	 * Show an image in HTML
	 * @return string
	 */
	public function show() {
		return '<img'.
					' src="' . $this->getURL() . '"' .
					($this->width ? ' width="'. $this->width . '"' : null).
					($this->height ? ' height="'. $this->height . '"' : null).
					' alt=""'.
				'/>';
	}

	/**
	 * Echo as a string
	 * Show image in HTML
	 * @return string
	 */
	public function __toString() {
		return $this->show();
	}

	/**
	 * Gives the string ending of the image
	 * @return string
	 */
	public function getExtension() {
		if(!isset($this->type) || !isset(self::$exts[ $this->type ]))
			return false;

		return self::$exts[ $this->type ];
	}


	/**
	 * Set an extension for image
	 * @param int|string $ext
	 * @return \MoEngine\Image
	 */
	protected function setExtension($ext) {
		// integer, a key
		if(is_int($ext) && in_array($ext, array_keys(self::$exts)))
			$this->type = $ext;

		// string, extension name
		$this->type = array_search($ext, self::$exts);

		return $this;
	}

	/**
	 * When logging save the [name & extension]
	 * @return array
	 */
	public function escape() {
		return array(
			$this->name,
			$this->getExtension()
		);
	}

	public function getWidth() {
		return $this->width;
	}

	public function getHeight() {
		return $this->height;
	}

	public function getFilesize() {
		return $this->filesize;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function setPath($path) {
		$this->path = $path;
		return $this;
	}
}