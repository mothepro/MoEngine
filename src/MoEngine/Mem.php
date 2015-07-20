<?php
namespace MoEngine;

/**
 * Memory Manager
 * Resource
 *
 * @package MoEngine
 * @author Maurice Prosper <maurice.prosper@ttu.edu>
 */
abstract class Mem {
	/**
	 * Connection to RAM
	 * @var \Memcached
	 */
	protected static $mem;

	/**
	 * list of servers in memcached cluster
	 * @var array
	 */
	protected static $servers;

	/**
	 * Create a memcached object with options and servers
	 */
	protected static function connect() {
		if(!isset(static::$mem)) {
			static::$mem = new \Memcached();

			static::$mem->setOptions(array(
				\Memcached::OPT_PREFIX_KEY			=> \MAIN::PROJECT,
				\Memcached::OPT_COMPRESSION			=> true,
				\Memcached::OPT_COMPRESSION_TYPE	=> \Memcached::COMPRESSION_FASTLZ
			));

			if(!empty(static::$servers))
				static::$mem->addServers(static::$servers);
		}
	}

	/**
	 * Gets data from Memcached to caller
	 * @param array|string $key
	 * @return mixed
	 */
	public static function get($key) {
		static::connect();

		if(is_array($key))
			return static::$mem->getMulti($key);
		elseif(is_string($key))
			return static::$mem->get($key);
	}

	/**
	 * Saves some data for memcached
	 * @param string $key
	 * @param mixed $value
	 * @param Time|int $expiration Absolute time to expire or seconds to wait till untils it's expired
	 * @return boolean
	 */
	public static function set($key, $value, $expiration = 0) {
		static::connect();

		if($expiration instanceof Time)
			$expiration = $expiration->getTimestamp();
		elseif(is_int($expiration) && 2592000 < $expiration && $expiration < \NOW)
			$expiration += \NOW;

		return static::$mem->set($key, $value, $expiration);
	}

	/**
	 * Increments data in memcached
	 * @param string $key
	 * @param int $offset
	 */
	public static function increment($key, $offset = 1) {
		static::connect();

		static::$mem->increment($key, $offset);
	}

	/**
	 * Removes all objects in memcached
	 * @param int $delay number of seconds to wait before preforming
	 */
	public static function flush($delay = 0) {
		static::connect();
		static::$mem->flush($delay);
	}

	/**
	 * Removes an object from memcached
	 * @param string $key
	 * @param int $time seconds to wait before deleting
	 */
	public static function delete($key, $time = 0) {
		static::connect();
		static::$mem->delete($key, $time);
	}


	/**
	 * Refreshes the expiration time of an object
	 * @param sting $key
	 * @param Time|int $expiration
	 */
	public static function touch($key, $expiration) {
		static::connect();

		if($expiration instanceof Time)
			$expiration = $expiration->getTimestamp();
		elseif(is_int($expiration) && 2582000 < $expiration && $expiration < \NOW)
			$expiration += \NOW;

		static::$mem->touch($key, $expiration);
	}
}