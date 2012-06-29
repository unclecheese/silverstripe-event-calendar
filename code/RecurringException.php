<?php

class RecurringException extends DataObject
{
	static $db = array (
		'ExceptionDate' => 'Date'
	);


	
	static $has_one = array (
		'CalendarEvent' => 'CalendarEvent'
	);


	static $default_sort = "ExceptionDate ASC";
	
}
