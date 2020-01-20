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
 * sfDateTimeToolkit class.
 *
 * A toolkit for the sfDateTimePlugin.
 *
 * @package	sfDateTimePlugin
 * @author	Stephen Riesenberg <sjohnr@gmail.com>
 * @version	SVN: $Id$
 */
class sfDateTimeToolkit
{
	/**
	 * Breaks down the individual components of the timestamp.
	 *
	 * @param	timestamp
	 * @return	array
	 */
	public static function breakdown($ts = null)
	{
		// default to now
		if ($ts === null) $ts = sfDateTimeToolkit::now();
		
		// gather individual variables
		$H = date('H', $ts); // hour
		$i = date('i', $ts); // minute
		$s = date('s', $ts); // second
		$m = date('m', $ts); // month
		$d = date('d', $ts); // day
		$Y = date('Y', $ts); // year
		
		return array($H, $i, $s, $m, $d, $Y);
	}
	
	/**
	 * Returns the current timestamp.
	 *
	 * @return	timestamp
	 *
	 * @see		time
	 */
	public static function now()
	{
		return time();
	}
	
	/**
	 * Retrieve the timestamp from a number of different formats.
	 *
	 * @param	mixed	value to use for timestamp retrieval
	 */
	public static function getTS($value = null)
	{
		if ($value === null)
		{
			return sfDateTimeToolkit::now();
		}
		else if ($value instanceof sfDate)
		{
			return $value->get();
		}
		else if (!is_numeric($value))
		{
			return strtotime($value);
		}
		else if (is_numeric($value))
		{
			return $value;
		}
		
		throw new sfDateTimeException(sprintf('A timestamp could not be retrieved from the value: %s', $value));
	}
}