<?php
namespace MoEngine;

/**
 * Simple MySQL Database conntector resource
 *
 * @package MoEngine
 * @author Maurice Prosper <maurice.prosper@ttu.edu>
 */
abstract class Sql {
	/**
	 * list of replacers
	 * @var array
	 */
	protected static $replace = array();

	/**
	 * hostname to mysql
	 * @var string
	 */
	protected static $server;

	/**
	 * user to mysql
	 * @var string
	 */
	protected static $user;

	/**
	 * password to mysql
	 * @var string
	 */
	protected static $pass;

	/**
	 * prefix to all databases
	 * @var string
	 */
	protected static $db_prefix;

	/**
	 * current database
	 * @var string
	 */
	protected static $db;

	/**
	 * Database resource
	 * @var mysqli
	 */
	protected static $sql;

	/**
	 * Use a different database
	 * @param string $name
	 * @return boolean
	 */
	public static function changeDB($name) {
		if(static::$db !== $name) {
			static::$db = $name;

			// dont try to make a connection, just edit if it's alive
			if(isset(static::$sql)) {
				// add db prefix if any
				if(empty(static::$db_prefix))
					$dbc = static::$db;
				else
					$dbc = static::$db_prefix . '_' . static::$db;

				return static::$sql->select_db($dbc);
			}
		}

		return true;
	}

	/**
	 * Give the database name, without prefix
	 * @return string $name name of database
	 */
	public static function getDB() {
		return static::$db;
	}

	/**
	 * Changes queries dynamically
	 * @param string $from
	 * @param string $to
	 */
	public static function addReplacement($from, $to) {
		static::$replace[ $from ] = $to;
	}

	/**
	 * Make a connection to a MySQL server
	 */
	protected static function connect() {
		if(!isset(static::$sql)) {
			// add db prefix if any
			$dbc = '';
			if(static::$db) {
				if(static::$db_prefix)
					$dbc = static::$db_prefix . '_';
				$dbc .= static::$db;
			}

			// connect
			static::$sql = new \mysqli(static::$server, static::$user, static::$pass, $dbc);

			if(static::$sql->connect_errno == 0) {
				static::$sql->set_charset('utf8');

				// allow loading locally
				if(static::$user === 'root')
					static::$sql->options(MYSQLI_OPT_LOCAL_INFILE, true);

				// close MySQL connection on end
				register_shutdown_function(function(){
					static::$sql->close();
				});
			} else {
				throw new SQLException(static::$sql->connect_error);
				unset(static::$sql);
				return false;
			}
		}

		return true;
	}

	/**
	 * Makes query ready against database
	 * @param string $q
	 */
	protected static function cleanQuery($q) {
		// get data if a SQL file
		if(substr($q, -4) === '.sql')
			$q = file_get_contents(\DIR::SQL . $q, true);

		//remove delimiters
		$q = rtrim($q, ';');

		//db prefix
		$q = str_replace('db_', static::$db_prefix . '_', $q);

		// add seperators
		$q = strtr($q, array('SEP_MYSQL' => '"'. \SEP::MYSQL .'"'));

		// do replacements
		$q = strtr($q, static::$replace);

		return trim($q);
	}

	/**
	 * Runs a query without any parameters
	 * @param string $query
	 * @array array|int|boolean Data selected, Inserted ID or whether it worked
	 */
	protected static function basicQuery($query) {
		if(!static::connect())
			return null;

		$ret = false;
		$res = static::$sql->query($query);

		//INSERT, UPDATE OR DELETE, give insert id else true
		if ($res === true) {
			$ret = static::$sql->insert_id;
			if ($ret <= 0)
				$ret = true;
		} elseif (!static::$sql->errno) {
			$ret = array();

			while($data = $res->fetch_assoc())
				$ret[] = $data;
		} else
			throw new SQLException(static::$sql->error);

		// MySQLi result
		if (is_object($res))
			$res->close();

		return $ret;
	}

	/**
	 * Runs a query
	 * @param string $query Query to run against MySQL DB
	 * @param mixed $list All vars to replace in query
	 * @return array data from query, or null
	 */
	public static function q() {
		if(!static::connect())
			return null;

		//return default
		$array = null;

		// query is first arg
		$q = static::cleanQuery( func_get_arg(0) );

		if(empty($q))
			throw new SQLException('Bad query given');

		if(func_num_args() > 1) {
			$stmt = static::$sql->prepare($q);

			// prepares query for syntax/db errors
			if($stmt) {
				$bind[0] = '';

				//arguments of query
				$args = func_get_args();
				unset($args[0]);

				// attempt to combine data of arrays
				foreach($args as $k => $v) {
					if(is_array($v)) {
						//Find $k'th '?'
						$pos = 0;
						for($i = 0; $i <= $k; ++$i)
							$pos = strpos($q, '?', $pos);

						//Change 1 '?' into $c '?'s
						$q = substr_replace($q, '?' . str_repeat(',?', count($v) - 1), $pos, 1);

						//Add list of args to array
						unset($args[$k]);
						$args = array_merge($args, $v);
					}
				}

				//set type & add to bind
				foreach($args as $k => $v) {
					// user instance identifier
					if($v instanceof Loggable)
						$v = $v->escape();

					if(is_integer($v))		$bind[0] .= 'i';
					elseif(is_float($v))	$bind[0] .= 'd';
					else 					$bind[0] .= 's';

					$bind[] = &$args[$k];
				}

				//Run this query
				if(	call_user_func_array(array($stmt, 'bind_param'), $bind)
				&&	$stmt->execute()
				&&	$stmt->errno === 0) {
					$meta = $stmt->result_metadata();
					// SELECT
					if(($ret = $stmt->affected_rows) <= 0 && $meta) {
						//get meta data
						$vars = array();
						$data = $array = array();

						foreach($meta->fetch_fields() as $v)
							$vars[] = &$data[ $v->name ];

						call_user_func_array(array($stmt, 'bind_result'), $vars);

						$i=0;
						while($stmt->fetch()) {
							$array[$i] = array();
							foreach($data as $k => $v)
								$array[$i][ $k ] = $v;
							++$i;
						}
						$meta->free_result();

					// INSERT, UPDATE OR DELETE
					} else {
						//give insert id
						$array = static::$sql->insert_id;

						// or affected rows at the least
						if($array <= 0)
							$array = $ret;
					}
				} else
					throw new SQLException($stmt->error);

				$stmt->close();
			} else
				throw new SQLException(static::$sql->error);
		} else
			$array = static::basicQuery($q);

		//break open with our seperator
		if(is_array($array))
			foreach($array as &$tmp)
				foreach($tmp as &$v) {
					if(strpos($v, \SEP::MYSQL) !== false)
						$v = explode(\SEP::MYSQL, $v);
				}

		//move up if only data
		if(is_array($array) && isset($array[0]) && strtolower(substr($q, -7)) === 'limit 1')
			$array = $array[0];

		return $array;
	}
}

class SQLException extends \Exception {}