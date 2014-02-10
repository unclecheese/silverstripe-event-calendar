<?php

class CachedCalendarEntry extends DataObject {

	 private static $db = array (		
		'StartDate' => 'Date',
		'StartTime' => 'Time',
		'EndDate' => 'Date',
		'EndTime' => 'Time',
		'AllDay' => 'Boolean',
		'Announcement' => 'Boolean',
		'DateRange' => 'HTMLText',
		'TimeRange' => 'HTMLText',
		'ICSLink' => 'Varchar(100)',
		'Title' => 'Varchar(255)',
		'Content' => 'HTMLText'
	);

	private static $has_one = array (
		'CachedCalendar' => 'Calendar',
		'Calendar' => 'Calendar',
		'Event' => 'CalendarEvent'
	);
	
	private static $default_sort = "StartDate ASC, StartTime ASC";

	public static function create_from_datetime(CalendarDateTime $dt, Calendar $calendar) {
		$cached = CachedCalendarEntry::create();
		$cached->hydrate($dt, $calendar);
		return $cached;
	}

	public static function create_from_announcement(CalendarAnnouncement $dt, Calendar $calendar) {
		$cached = CachedCalendarEntry::create();
		$cached->hydrate($dt, $calendar);
		$cached->CalendarID = $dt->CalendarID;		
		$cached->Announcement = 1;
		return $cached;
	}

	public function OtherDates() {
		if($this->Announcement) {
			return false;
		}
		
		return CachedCalendarEntry::get()
			->filter(array(
				"EventID" => $this->EventID				
			))
			->exclude(array(
				"StartDate" => $this->StartDate
			))
			->limit($this->CachedCalendar()->OtherDatesCount);
	}



	public function hydrate(CalendarDateTime $dt, Calendar $calendar) {
		$this->CachedCalendarID = $calendar->ID;
		foreach($dt->db() as $field => $type) {
			$this->$field = $dt->$field;
		}
		foreach($dt->has_one() as $field => $type) {
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
