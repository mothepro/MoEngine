<?php
namespace MoEngine;

/**
 * Objects that can be saved to be loaded to database by cron
 * @author Maurice Prosper <maurice@chimpmint.com>
 */
interface Loggable {
	/**
	 * SQL representation of an object
	 * Multiple SQL fields can be used returning an array
	 * 
	 * @return boolean|int|string|array
	 */
	public function escape();
}