<?php

class RecurringException extends DataObject {
	
	private static $db = array (
		'ExceptionDate' => 'Date'
	);
	
	private static $has_one = array (
		'CalendarEvent' => 'CalendarEvent'
	);

	private static $default_sort = "ExceptionDate ASC";
	
}
