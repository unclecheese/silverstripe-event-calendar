<?php

/**
 * A cached calendar datetime entry with some values pre-calculated for storage
 * 
 * @author Aaron Carlino
 * @author Grant Heggie
 * @package silverstripe-event-calendar
 */

namespace UncleCheese\EventCalendar\Models;

use UncleCheese\EventCalendar\Models\CalendarAnnouncement;
use UncleCheese\EventCalendar\Models\CalendarDateTime;
use UncleCheese\EventCalendar\Pages\CalendarEvent;
use UncleCheese\EventCalendar\Pages\Calendar;
use SilverStripe\ORM\DataObject;

class CachedCalendarEntry extends DataObject 
{
	private static $table_name = 'UncleCheese_CachedCalendarEntry';

	private static $db = [		
		'StartDate'		=> 'Date',
		'StartTime'		=> 'Time',
		'EndDate'		=> 'Date',
		'EndTime'		=> 'Time',
		'AllDay'		=> 'Boolean',
		'Announcement'	=> 'Boolean',
		'DateRange'		=> 'HTMLText',
		'TimeRange'		=> 'HTMLText',
		'ICSLink'		=> 'Varchar(100)',
		'Title'			=> 'Varchar(255)',
		'Content'		=> 'HTMLText'
	];

	private static $has_one = [
		'CachedCalendar'	=> Calendar::class,
		'Calendar'			=> Calendar::class,
		'Event'				=> CalendarEvent::class
	];
	
	private static $default_sort = "StartDate ASC, StartTime ASC";

	/**
	 * @return CachedCalendarEntry
	 */
	public static function create_from_datetime(CalendarDateTime $dt, Calendar $calendar)
	{
		$cached = self::create();
		$cached->hydrate($dt, $calendar);
		return $cached;
	}

	/**
	 * @return CachedCalendarEntry
	 */
	public static function create_from_announcement(CalendarAnnouncement $dt, Calendar $calendar)
	{
		$cached = self::create();
		$cached->hydrate($dt, $calendar);
		$cached->CalendarID = $dt->CalendarID;		
		$cached->Announcement = 1;
		return $cached;
	}

	/**
	 * @return \SilverStripe\ORM\DataList
	 */
	public function OtherDates()
	{
		if ($this->Announcement) {
			return false;
		}
		return self::get()
			->filter('EventID', $this->EventID)			
			->exclude(
				[
					'StartDate' => $this->StartDate
				]
			)->limit($this->CachedCalendar()->OtherDatesCount);
	}

	public function hydrate(CalendarDateTime $dt, Calendar $calendar)
	{
		$this->CachedCalendarID = $calendar->ID;
		foreach ($dt->config()->db as $field => $type) {
			$this->$field = $dt->$field;
		}
		foreach ($dt->config()->has_one as $field => $type) {
			$this->{$field."ID"} = $dt->{$field."ID"};
		}
		$this->DateRange = $dt->DateRange();
		$this->TimeRange = $dt->TimeRange();
		$this->ICSLink = $dt->ICSLink();
		$this->Title = $dt->getTitle();
		$this->Content = $dt->getContent();
		return $this;
	}

}
