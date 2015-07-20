<?php
namespace MoEngine;
/**
 * Absolute date and time
 *
 * @author Maurice Prosper <maurice@ParkShade.com>
 * @package ParkShade
 */
class Time extends \DateTime implements Loggable, \JsonSerializable {
	/**
	 * Makes a new time
	 * Default is current time
	 * @param string $time any time format
	 * @param DateTimeZone $object a timezone
	 */
	public function __construct($time = null, $object = null) {
		// UNIX time
		if(!isset($time) || ctype_digit($time)) {
			parent::__construct();

			// current time
			if(!isset($time))
				$this->setTimestamp(\NOW);
			else
				$this->setTimestamp($time);

			if($object instanceof \DateTimeZone)
				$this->setTimezone($object);
		} else
			parent::__construct($time, $object);
	}

	/**
	 * Puts time in a relative format
	 * @param boolean $upper capitalize first letter
	 * @return string
	 */
	public function ago() {
		$date = $this->getTimestamp();

		if(empty($date))
			return 'A long, long time ago&#8230;';

		if(\NOW === $date)
			return 'just now';

		$periods    = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
		$lengths    = array(60,60,24,7,365/(12*7),12,10,0);

		if(\NOW > $date)
			$tense	= 'ago';
		if(\NOW < $date)
			$tense	= 'from now';

		$diff = abs(\NOW - $date);
		if($diff < 10)
			return 'just a moment '. $tense;

		$tot = count($lengths)-1;
		for($j = 0; $diff >= $lengths[$j] && $j < $tot; ++$j)
			$diff /= $lengths[$j];

		// remove precision
		$diff = number_format(round($diff));

		// relative wording
		if($periods[$j]=="day" && $diff == 1)
			if(\NOW > $date)
				return 'yesterday';
			else
				return 'tomorrow';

		if($diff == 1) {
			if($periods[$j] === 'hour')
				$diff = 'an';
			else
				$diff = 'a';
		} else
			$periods[$j] .= 's';

		return $diff .' '. $periods[$j] .' '. $tense;
	}

	/**
	 * Unixtime
	 * @todo bigint
	 * @return int
	 */
	public function escape() {
		$i = intval($this->getTimestamp());
		if($i < 0)
			$i += 4294967296;
		return $i;
	}

	/**
	 * Adds time to the time
	 * timezone safe
	 * @param int $s
	 * @return Time
	 */
	public function addSeconds($s) {
		return $this->setTimestamp($this->getTimestamp() + intval($s));
		//return $this->add(new \DateInterval('P'. $s .'S'));
	}

	public function jsonSerialize() {
		return $this->format(\DateTime::ISO8601);
	}

}