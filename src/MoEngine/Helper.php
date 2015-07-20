<?php
namespace MoEngine;

/**
 * These are some nice little static functions to help with useablity
 *
 * Utility
 *
 * @package MoEngine
 * @author Maurice Prosper <maurice.prosper@ttu.edu>
 */
abstract class Helper {
	/**
	 * Cleans a string as a nicer urlencode
	 *
	 * @param  string $a string to clean up
	 * @return string    cleaned string
	 */
	public static function clean($a) {
		$a = str_replace(array("ä", "Ä"), "a", $a); // Additional Swedish filter
		$a = str_replace(array("å", "Å"), "a", $a); // Additional Swedish filter
		$a = str_replace(array("ö", "Ö"), "o", $a); // Additional Swedish filter

		$a = preg_replace("/[^a-z0-9\s\-]/i", "", $a); // Remove special characters
		$a = preg_replace("/\s\s+/", " ", $a); // Replace multiple spaces with one space
		$a = trim($a); // Remove trailing spaces
		$a = preg_replace("/\s/", "-", $a); // Replace all spaces with hyphens
		$a = preg_replace("/\-\-+/", "-", $a); // Replace multiple hyphens with one hyphen
		$a = preg_replace("/^\-|\-$/", "", $a); // Remove leading and trailing hyphens
		$a = strtolower($a);
		$a = urlencode($a);

		return $a;
	}

	/**
	 * A better way to show a number of something
	 *
	 * @param int $a number to check
	 * @param string $str String to show
	 * @param string $plur plural ending ['s']
	 * @param boolean $sing singular ending ['']
	 * @return string
	 */
	public static function number($a, $str, $plur = 's', $sing = '') {
		if($a == 0)
			return 'no ' . $str . $plur;
		return number_format($a) . ' ' . $str . ($a === 1 ? $sing : $plur);
	}

	/**
	 * Checks if email is valid
	 *
	 * @param string $email
	 * @return boolean
	 */
	public static function checkEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL) && checkdnsrr(substr($email, strpos($email, '@')+1), 'MX');
	}

	/**
	 * Converts Full name to array of names
	 *
	 * @param string $a the fullname
	 * @return array list of different names
	 */
	public static function fullname($a) {
		$r = array(
			'first'		=> null,
			'last'		=> null,
			'mid'		=> null,
			'suffix'	=> null,
		);

		// make alphanumeric including dashes and apostrphes
		$a = preg_replace('/[^A-Za-z0-9-\' ]/', '', trim($a));

		//remove multiple spaces
		$a = preg_replace('/\s+/', ' ', $a);

		//sep by space
		$tmp = explode(' ', $a);

		// find suffix, if any
		foreach($tmp as $k => $x) {
			foreach(array('Jr','Sr','III','IV','V','VI','VII','VIII','IX','X') as $y) { // find suffix, if any
				if(strtolower($x) === strtolower($y)) {
					$r['suffix'] = $y;
					unset($tmp[$k]);
					break;
				}
			}
		}

		$r['first']	= array_shift($tmp);
		$r['last']	= array_pop($tmp);
		$r['mid']	= implode(' ', $tmp);

		foreach($r as $k => $v)
			if(empty($v))
				$r[$k] = null;
			else
				$r[$k] = ucfirst($v);

		return $r; //array_filter($r);
	}

	/**
	 * Fixes string or array for saving to file or MySQL
	 *
	 * @param  mixed $v input var
	 * @return mixed escaped var
	 */
	private static function escape($v) {
		// null in MySQL
		if(!isset($v))
			return '\N';

		// mysql only has int support
		elseif(is_bool($v))
			return ($v ? '1' : '0');

		// has its own escape function
		elseif($v instanceof Loggable)
			return self::escape( $v->escape() );

		// a list of data
		elseif(is_array($v)) {
			$ret = array();
			foreach($v as $p)
				$ret[] = self::escape ($p);
			return $ret;
		}

		// regular data
		else {
			$x = str_replace(
				array(	"\t",	"\r\n",	"\r",	"\n"),
				array( 	'\t',	"\n",	"\n",	'\n'),
				trim(
					addcslashes($v, '\\')
				)
			);

			do
				$x = str_replace('\n\n\n', '\n\n', $x, $c);
			while($c);

			return $x;
		}
	}

	/**
	 * Saves data in load files for cron to push to MySQL
	 *
	 * @param string $db DB for data
	 * @param string $table Table for data
	 * @return boolean
	 */
	public static function save() {
		for($i=2, $t = func_num_args(), $str = array(); $i<$t; ++$i) {
			$x = self::escape( func_get_arg($i) );

			if(is_array($x))
				$x = implode("\t", $x);

			$str[] = $x;
		}

		$f = @fopen(\DIR::LOAD . func_get_arg(0) . DIRECTORY_SEPARATOR . func_get_arg(1), 'a');

		if(!$f)
			return false;

		fwrite($f, implode("\t", $str) . "\n");
		fclose($f);

		return true;
	}

	/**
	 * Hashes a password
	 *
	 * @param string $pass password entered
	 * @param string $email email used with password
	 * @param int $made time account was made [NOW]
	 * @return string 86 Chars
	 */
	public static function passwordHash($pass, $email, $made = \NOW)	{
		return substr(
				crypt(
					urlencode($pass),
					'$6$rounds='. ($made % 990000 + 10000) .'$'. str_pad(
						substr(
							preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($email)
						), 0, 16),
					16, 'abcdefg') .
				'$'
				),
			34);
	}

	/**
	 * 16 unique bytes
	 * @param string $seed Added randomness
	 * @return byte
	 */
	public static function randomHash($seed = null) {
		return md5( 'pootis' . mt_rand() . microtime(true) . strval($seed) . php_uname('n') . \NOW );
	}

	/**
	 * Turns a regular name into one that can be used as a function
	 * @param string $name
	 * @return string
	 */
	public static function funcname($name) {
		$ret = 'home';
		if(!empty($name))
			$ret = $name;

		$ret = str_replace('-', '_', $ret);

		return $ret;
	}
}