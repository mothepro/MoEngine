<?php
namespace MoEngine;

/**
 * A List of flags
 *
 * @author Maurice Prosper <maurice@ParkShade.com>
 * @package ParkShade
 */
abstract class BitField implements Loggable {
	/**
	 * The Status bits
	 * @var int|bits
	 */
	private $value;

	/**
	 * Sets list of bits
	 * @param bits $value
	 */
	public function __construct($value = 0) {
		$this->value = intval($value);
	}

	/**
	 * The bits concatenated
	 * @return bits
	 */
	public function escape() {
		return $this->value;
	}

	/**
	 * Sets a flag
	 * @param bits $n
	 * @return BitField
	 */
	public function set($n) {
		$this->value |= $n;
		return $this;
	}

	/**
	 * Clears a flag
	 * @param bits $n
	 * @return BitField
	 */
	public function reset($n) {
		$this->value &= ~$n;
		return $this;
	}

	/**
	 * Toggles a flag
	 * @param bits $n
	 * @return BitField
	 */
	public function toggle($n) {
		$this->value ^= $n;
		return $this;
	}

	/**
	 * Turns all flags off
	 * @return BitField
	 */
	public function clear() {
		$this->value = 0;
		return $this;
	}

	/**
	 * Tells if all bits given are set
	 * @param bits $n
	 * @return boolean
	 */
	public function is($n) {
		return ($this->value & $n) === $n;
	}

	/**
	 * Tells if any of the bits are set
	 * @param bits $n
	 * @return boolean
	 */
	public function isAny($n) {
		return !!($this->value & $n);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return strval($this->value);
	}
}