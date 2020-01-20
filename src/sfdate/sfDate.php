<?php

/*
 * This file is part of the sfDateTimePlugin package.
 * (c) 2007 Stephen Riesenberg <sjohnr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * sfDate class.
 *
 * A class for representing a date/time value as an object.
 *
 * This class allows for chainable calculations using the sfTime utility class.
 *
 * @package	sfDateTimePlugin
 * @author	Stephen Riesenberg <sjohnr@gmail.com>
 * @version	SVN: $Id$
 */
class sfDate
{
	/**
	 * The timestamp for this sfDate instance.
	 */
	private $ts = null;
	
	/**
	 * The original timestamp for this sfDate instance.
	 */
	private $init = null;
	
	/**
	 * Retrieves a new instance of this class.
	 *
	 * NOTE: This is not the singleton pattern. Instead, it is for chainability ease-of-use.
	 *
	 * <b>Example:</b>
	 * <code>
	 *   echo sfDate::getInstance()->getFirstDayOfWeek()->addDay()->format('Y-m-d');
	 * </code>
	 *
	 * @param	mixed	timestamp, string, or sfDate object
	 * @return	sfDate
	 */
	public static function getInstance($value = null)
	{
		return new sfDate($value);
	}
	
	/**
	 * Construct an sfDate object.
	 *
	 * @param	mixed	timestamp, string, or sfDate object
	 */
	public function __construct($value = null)
	{
		$this->set($value);
	}
	
	/**
	 * Format the date according to the <code>date</code> function.
	 *
	 * @return	string
	 */
	public function format($format)
	{
		return date($format, $this->ts);
	}
	
	/**
	 * Formats the date according to the <code>format_date</code> helper of the Date helper group.
	 *
	 * @return	string
	 */
	public function date($format = 'd')
	{		
		return date('Y-m-d', $this->ts);
	}
	
	/**
	 * Formats the date according to the <code>format_datetime</code> helper of the Date helper group.
	 *
	 * @return	string
	 */
	public function datetime($format = 'F')
	{		
		return date('Y-m-d H:i:s', $this->ts);
	}
	
	/**
	 * Format the date as a datetime value.
	 *
	 * @return	string
	 */
	public function dump()
	{
		return date('Y-m-d H:i:s', $this->ts);
	}
	
	/**
	 * Retrieves the given unit of time from the timestamp.
	 *
	 * @param	int	unit of time (accepts sfTime constants).
	 * @return	int	the unit of time
	 *
	 * @throws	sfDateTimeException
	 */
	public function retrieve($unit = sfTime::DAY)
	{
		switch ($unit)
		{
			case sfTime::SECOND:
				return date('s', $this->ts);
			case sfTime::MINUTE:
				return date('i', $this->ts);
			case sfTime::HOUR:
				return date('H', $this->ts);
			case sfTime::DAY:
				return date('d', $this->ts);
			case sfTime::WEEK:
				return date('W', $this->ts);
			case sfTime::MONTH:
				return date('m', $this->ts);
			case sfTime::QUARTER:
				return ceil(date('m', $this->ts) / 3);
			case sfTime::YEAR:
				return date('Y', $this->ts);
			case sfTime::DECADE:
				return ceil((date('Y', $this->ts) % 100) / 10);
			case sfTime::CENTURY:
				return ceil(date('Y', $this->ts) / 100);
			case sfTime::MILLENIUM:
				return ceil(date('Y', $this->ts) / 1000);
			default:
				throw new sfDateTimeException(sprintf('The unit of time provided is not valid: %s', $unit));
		}
	}
	
	/**
	 * Retrieve the timestamp value of this sfDate instance.
	 *
	 * @return	timestamp
	 */
	public function get()
	{
		return $this->ts;
	}
	
	/**
	 * Sets the timestamp value of this sfDate instance.
	 *
	 * This function accepts several froms of a date value:
	 * - timestamp
	 * - string, parsed with <code>strtotime</code>
	 * - sfDate object
	 *
	 * @return	sfDate	the modified object, for chainability
	 */
	public function set($value = null)
	{
		$ts = sfDateTimeToolkit::getTS($value);
		
		$this->ts = $ts;
		if ($this->init === null)
		{
			$this->init = $ts;
		}
		
		return $this;
	}
	
	/**
	 * Resets the timestamp value of this sfDate instance to its original value.
	 *
	 * @return	sfDate	the reset object, for chainability
	 */
	public function reset()
	{
		$this->ts = $this->init;
		
		return $this;
	}
	
	/**
	 * Compares two date values.
	 *
	 * @param	mixed	timestamp, string, or sfDate object
	 * @return	int		-1, 0, or 1
	 */
	public function cmp($value)
	{
		$ts = sfDateTimeToolkit::getTS($value);
		
		if ($this->ts < $ts)
		{
			// less than
			return -1;
		}
		else if ($this->ts > $ts)
		{
			// greater than
			return 1;
		}
		
		// equal to
		return 0;
	}
	
	/**
	 * Gets the difference of two date values in seconds.
	 *
	 * @param	mixed	timestamp, string, or sfDate object
	 * @param	int		the difference in seconds
	 */
	public function diff($value)
	{
		$ts = sfDateTimeToolkit::getTS($value);
		
		return $this->ts - $ts;
	}
	
	/**
	 * Call any function available in the sfTime library, but without the ts parameter.
	 *
	 * <b>Example:</b>
	 * <code>
	 *   $ts = sfTime::firstDayOfMonth(sfTime::addMonth(time(), 5));
	 *   // equivalent
	 *   $dt = new sfDate();
	 *   $ts = $dt->addMonth(5)->firstDayOfMonth()->get();
	 * </code>
	 *
	 * @return	sfDate	the modified object, for chainability
	 */
	public function __call($method, $arguments)
	{
		$callable = array('sfTime', $method);
		
		if (!is_callable($callable))
		{
			throw new sfDateTimeException(sprintf('Call to undefined function: %s::%s', 'sfDate', $method));
		}
		
		array_unshift($arguments, $this->ts);
		
		$this->ts = call_user_func_array($callable, $arguments);
		
		return $this;
	}
}