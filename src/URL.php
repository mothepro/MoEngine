<?php
namespace MoEngine;
/**
 * A URL
 *
 * @author Maurice Prosper <maurice@ParkShade.com>
 * @package ParkShade
 */
class URL implements Loggable {
	/**
	 * actual URL
	 * @var string
	 */
	protected $url;

	/**
	 * Cleans and Makes a URL
	 * @param string $url url to make
	 */
	public function __construct($url = null) {
		// if there isnt a url given they will be setting it localy
		if(isset($url))
			$this->url = filter_var($url, FILTER_SANITIZE_URL);
	}

	/**
	 * Sets the URL to a local path in project
	 * Default is the homepage
	 *
	 * @param string $name
	 * @param string|array $val
	 * @param string $arg
	 */
	public function setLocal($name = '', $val = null, $arg = null) {
		$this->url = self::makePath($name, $val, $arg);

		return $this;
	}

	/**
	 * Valid URL format
	 * @return boolean
	 */
	public function isValid() {
		return filter_var($this->url, FILTER_VALIDATE_URL);
	}

	/**
	 * The URL is https
	 * @return boolean
	 */
	public function isSercure() {
		return (parse_url($this->url, PHP_URL_SCHEME) === 'https');
	}

	/**
	 * Is the url in our domain
	 * Load locals will be false
	 *
	 * @return boolean
	 */
	public function belongsToUs() {
		$ret = false;

		$host = parse_url($this->url, PHP_URL_HOST);

		foreach(array(\URL::CSS, \URL::JS, \URL::IMG) as $our_url) {
			$our_host = parse_url($our_url, PHP_URL_HOST);

			if($host === $our_host) {
				$ret = true;
				break;
			}
		}

		return $ret;
	}

	/**
	 * Removes the HTTPS and converts it to HTTP
	 * @return URL
	 */
	public function makeUnSecure() {
		if($this->isSercure())
			$this->url = 'http://' . substr($this->url, 8);

		return $this;
	}

	/**
	 * Checks if a URL is active and doesn't 404
	 * @return boolean
	 */
	public function isLive(){
		if(!$this->isValid())
			return false;

		if(!$fp = curl_init($this->url))
			return false;
		return true;
	}

	/**
	 * Casts the URL to Link (New object is made)
	 * @return Link
	 */
	public function __toLink() {
		return new Link($this);
	}

	/**
	 * @return string URL
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Adds a query parameter
	 * make sure to setLocal first!
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function addParam($name, $value) {
		if(empty($this->url))
			$this->url = '/';

		$this->url .=
			((parse_url($this->url, PHP_URL_QUERY) === null) ? '?' : '&') . // seperator
			\urlencode($name) .'='. \urlencode($value); // parameter name and value

		return $this;
	}

	/**
	 * Gets a parameter from url
	 * @param string $name name of parameter
	 */
	public function getParam($name) {
		parse_str(parse_url($this->url, PHP_URL_QUERY), $x);

		if(isset($x[$name]))
			return $x[$name];
		return null;
	}

	/**
	 * Saves data from URL locally
	 * @param string $file path to save file
	 * @return int|boolean number of bytes that were written to the file, or FALSE on failure
	 */
	public function save($file) {
		$this->makeUnSecure();

		if($this->isValid() && !Error::hasError()) {
			$data = @file_get_contents($this->url);
			if($data)
				return file_put_contents($file, $data);
			else
				Error::removeErrors();
		}

		return false;
	}

	/**
	 * Formats a local path for a URL
	 *
	 * @param string $name
	 * @param int $val
	 * @param int $offset
	 * @param string $arg
	 * @return string
	 */
	public static function makePath($name = null, $val = null, $offset = null, $arg = null) {
		$url = '/';

		if(!empty($name)) {
			$url .= Helper::clean($name);

			if(isset($val))	$url .= '/' . intval($val);
			if(isset($offset))	$url .= '/' . intval($offset);
			if(isset($arg))	$url .= '/' . Helper::clean($arg);
		}

		return $url;
	}

	/**
	 * What to save when logging
	 * @return string
	 */
	public function escape() {
		return $this->url;
	}
}