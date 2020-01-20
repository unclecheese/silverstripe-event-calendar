<?php

namespace UncleCheese\EventCalendar\Pages;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxsetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use UncleCheese\EventCalendar\Models\CachedCalendarEntry;
use UncleCheese\EventCalendar\Models\CalendarAnnouncement;
use UncleCheese\EventCalendar\Pages\CalendarController;
use \Page;

class Calendar extends Page 
{
	private static $table_name = 'UncleCheese_Calendar';

	private static $db = [
		'DefaultDateHeader'		=> 'Varchar(50)',
		'OtherDatesCount'		=> 'Int',
		'RSSTitle'				=> 'Varchar(255)',
		'DefaultFutureMonths'	=> 'Int',
		'EventsPerPage'			=> 'Int',
		'DefaultView'			=> "Enum('today,week,month,weekend,upcoming','upcoming')"
	];

	private static $has_many = [
		'Announcements'	=> 'CalendarAnnouncement',
		'Feeds'			=> 'ICSFeed'
	];

	private static $many_many = [
		'NestedCalendars' => self::class
	];

	private static $belongs_many_many = [
		'ParentCalendars' => self::class
	];

	private static $allowed_children = [
		'CalendarEvent'
	];

	private static $defaults = [
		'DefaultDateHeader'		=> 'Upcoming Events',
		'OtherDatesCount'		=> 3,
		'DefaultFutureMonths'	=> 6,
		'EventsPerPage'			=> 10,
		'DefaultView'			=> 'upcoming'
	];

	private static $reccurring_event_index = 0;

	private static $recent_events_default_limit = 9999;

	private static $icon = "event_calendar/images/calendar";

	private static $description = "A collection of Calendar Events";

	private static $event_class = CalendarEvent::class;

	private static $announcement_class = CalendarAnnouncement::class;

	private static $timezone = "America/New_York";

	private static $language = "EN";

	public static $jquery_included = false;

	private static $caching_enabled = false;

	protected $eventClass_cache,
			  $announcementClass_cache,
			  $datetimeClass_cache,
			  $dateToEventRelation_cache,
			  $announcementToCalendarRelation_cache,
			  $eventList_cache;

	public static function set_jquery_included($bool = true)
	{
		Config::modify()->set(self::class, 'jquery_included', true);
	}

	public static function enable_caching()
	{
		Config::modify()->set(self::class, 'caching_enabled', true);
	}

	public function getCMSFields()
	{
		$self = $this;

		$this->beforeUpdateCMSFields(function($f) use ($self) {

			Requirements::javascript('unclecheese/silverstripe-event-calendar:client/js/calendar_cms.js');

			$configuration = _t(__CLASS__.'.CONFIGURATION','Configuration');
			$f->addFieldsToTab(
				"Root.$configuration", 
				[
					DropdownField::create(
						'DefaultView', 
						_t(__CLASS__.'.DEFAULTVIEW','Default view'), 
						[
							'upcoming' => _t(__CLASS__.'.UPCOMINGVIEW',"Show a list of upcoming events."),
							'month' => _t(__CLASS__.'.MONTHVIEW',"Show this month's events."),
							'week' => _t(__CLASS__.'.WEEKVIEW',"Show this week's events. If none, fall back on this month's"),
							'today' => _t(__CLASS__.'.TODAYVIEW',"Show today's events. If none, fall back on this week's events"),
							'weekend' => _t(__CLASS__.'.WEEKENDVIEW',"Show this weekend's events.")
						]
					)->addExtraClass('defaultView'),
					NumericField::create('DefaultFutureMonths', _t(__CLASS__.'.DEFAULTFUTUREMONTHS','Number maximum number of future months to show in default view'))->addExtraClass('defaultFutureMonths'),
					NumericField::create('EventsPerPage', _t(__CLASS__.'.EVENTSPERPAGE','Events per page')),
					TextField::create('DefaultDateHeader', _t(__CLASS__.'.DEFAULTDATEHEADER','Default date header (displays when no date range has been selected)')),
					NumericField::create('OtherDatesCount', _t(__CLASS__.'.NUMBERFUTUREDATES','Number of future dates to show for repeating events'))
				]
			);

			// Announcements
			$announcements = _t(__CLASS__.'.Announcements', 'Announcements');
			$f->addFieldToTab(
				"Root.$announcements",
				GridField::create(
					"Announcements",
					$announcements,
					$self->Announcements(),
					GridFieldConfig_RecordEditor::create()
				)->setDescription(
					_t(__CLASS__.'.ANNOUNCEMENTDESCRIPTION','Announcements are simple entries you can add to your calendar that do not have detail pages, e.g. "Office closed"')
				)
			);

			// Feeds
			$feeds = _t(__CLASS__.'.FEEDS', 'Feeds');
			$f->addFieldToTab(
				"Root.$feeds", 
				GridField::create(
					"Feeds",
					$feeds,
					$self->Feeds(),
					GridFieldConfig_RecordEditor::create()
				)->setDescription(
					_t(__CLASS__.'.ICSFEEDDESCRIPTION','Add ICS feeds to your calendar to include events from external sources')
				)
			);

			$otherCals = self::get()->exclude("ID", $self->ID);
			if ($otherCals->exists()) {
				$f->addFieldToTab(
					"Root.$feeds", 
					CheckboxsetField::create(
						'NestedCalendars',
						_t(__CLASS__.'.NESTEDCALENDARS','Include events from these calendars'),
						$otherCals->map('ID', 'Link')
					)
				);
			}
			$f->addFieldToTab(
				"Root.Main", 
				TextField::create('RSSTitle', _t(__CLASS__.'.RSSTITLE', 'Title of RSS feed')),
				'Content'
			);

		});

		return $f = parent::getCMSFields();
	}

	public function getEventClass()
	{
		if ($this->eventClass_cache) {
			return $this->eventClass_cache;
		} 
		return $this->eventClass_cache = self::config()->event_class;
	}

	public function getAnnouncementClass()
	{
		if ($this->announcementClass_cache) {
			return $this->announcementClass_cache;
		}
		return $this->announcementClass_cache = self::config()->announcement_class;
	}

	public function getDateTimeClass()
	{
		if ($this->datetimeClass_cache) {
			return $this->datetimeClass_cache;
		};
		return $this->datetimeClass_cache = Config::inst()->get($this->getEventClass(), 'datetime_class');
	}

	public function getDateToEventRelation()
	{
		if ($this->dateToEventRelation_cache) {
			return $this->dateToEventRelation_cache;
		}
		return $this->dateToEventRelation_cache = singleton(
			$this->getDateTimeClass()
		)->getReverseAssociation($this->getEventClass())."ID";
	}

	public function getCachedEventList($start, $end, $filter = null, $limit = null)
	{
		return CachedCalendarEntry::get()
			->filter("CachedCalendarID", $this->ID)
			->exclude(
				[
					"StartDate:LessThan" => $end,
					"EndDate:GreaterThan" => $start,
				]
			)
			->sort(
				[
					"StartDate" => "ASC",
					"StartTime" => "ASC"
				]
			)
			->limit($limit);
	}

	public function getEventList($start, $end, $filter = null, $limit = null, $announcement_filter = null)
	{
		if (self::config()->caching_enabled) {
			return $this->getCachedEventList($start, $end, $filter, $limit);
		}

		$eventList = ArrayList::create();

		foreach ($this->getAllCalendars() as $calendar) {
			if ($events = $calendar->getStandardEvents($start, $end, $filter)) {
				$eventList->merge($events);
			}

			$announcements = DataList::create($this->getAnnouncementClass())
				->filter(
					[
						"CalendarID" => $calendar->ID,
						"StartDate:GreaterThanOrEqual" => $start,
						"EndDate:LessThanOrEqual" => $end,
					]
				);
			if ($announcement_filter) {
				$announcements = $announcements->where($announcement_filter);
			}
			if ($announcements) {
				foreach($announcements as $announcement) {
					$eventList->push($announcement);
				}
			}
			if ($recurring = $calendar->getRecurringEvents($filter)) {
				$eventList = $calendar->addRecurringEvents($start, $end, $recurring, $eventList);
			}
			if ($feedevents = $calendar->getFeedEvents($start,$end)) {
				$eventList->merge($feedevents);
			}
		}

		$eventList = $eventList->sort(
			[
				"StartDate" => "ASC", 
				"StartTime" => "ASC"
			]
		)->limit($limit);

		return $this->eventList_cache = $eventList;
	}

	protected function getStandardEvents($start, $end, $filter = null)
	{
		$ids = $this->AllChildren()->column('ID');
		$relation = $this->getDateToEventRelation();
		$eventClass = $this->getEventClass();

		$list = DataList::create($this->getDateTimeClass())
			->filter($relation, $ids)
			->innerJoin($eventClass, "$relation = \"{$eventClass}\".\"ID\"")
			->innerJoin("SiteTree", "\"SiteTree\".\"ID\" = \"{$eventClass}\".\"ID\"")
			->where("Recursion != 1");
		if ($start && $end) {
			$list = $list->where("
				(StartDate <= '$start' AND EndDate >= '$end') OR
				(StartDate BETWEEN '$start' AND '$end') OR
				(EndDate BETWEEN '$start' AND '$end')"
			);
		} elseif($start) {
			$list = $list->where("(StartDate >= '$start' OR EndDate > '$start')");
		} elseif($end) {
			$list = $list->where("(EndDate <= '$end' OR StartDate < '$end')");
		}
		if ($filter) {
			$list = $list->where($filter);
		}

		return $list;
	}

	protected function getRecurringEvents($filter = null)
	{
		if ($relation = $this->getDateToEventRelation()) {
			$datetime_class = $this->getDateTimeClass();
			$events = DataList::create($this->getEventClass())
				->filter(
					[
						"Recursion" => "1",
						"ParentID" => $this->ID
					]
				)
				->innerJoin($datetime_class, "\"{$datetime_class}\".{$relation} = \"SiteTree\".ID");
			if ($filter) {
				$events = $events->where($filter);
			}
			return $events;
		}
		return false;
	}

	public function getNextRecurringEvents($event_obj, $datetime_obj, $limit = null)
	{
		$counter = sfDate::getInstance($datetime_obj->StartDate);
		$counter = new \DateTime($datetime_obj->StartDate);

		if($event = $datetime_obj->Event()->DateTimes()->First()) {
			$end_date = strtotime($event->EndDate);
		}
		else {
			$end_date = false;
		}
		$counter->tomorrow();
		$dates = ArrayList::create();
		while($dates->Count() != $this->OtherDatesCount) {
			// check the end date
			if($end_date) {
				if($end_date > 0 && $end_date <= $counter->get())
					break;
			}
			if($event_obj->getRecursionReader()->recursionHappensOn($counter->get())) {
				$dates->push($this->newRecursionDateTime($datetime_obj,$counter->date()));
			}
			$counter->tomorrow();
		}
		return $dates;
	}

	protected function addRecurringEvents($start_date, $end_date, $recurring_events,$all_events)
	{
		$date_counter = sfDate::getInstance($start_date);
		$end = sfDate::getInstance($end_date);
		foreach($recurring_events as $recurring_event) {
			$reader = $recurring_event->getRecursionReader();
			$relation = $recurring_event->getReverseAssociation($this->getDateTimeClass());
			if(!$relation) continue;

			$recurring_event_datetimes = $recurring_event->$relation()->filter(array(
				'StartDate:LessThanOrEqual' => $end->date(),
				'EndDate:GreaterThanOrEqual' => $date_counter->date(),
			));

			foreach ($recurring_event_datetimes as $recurring_event_datetime) {
				$date_counter = sfDate::getInstance($start_date);
				$start = sfDate::getInstance($recurring_event_datetime->StartDate);
				if ($start->get() > $date_counter->get()) {
					$date_counter = $start;
				}
				while($date_counter->get() <= $end->get()){
					// check the end date
					if($recurring_event_datetime->EndDate) {
						$end_stamp = strtotime($recurring_event_datetime->EndDate);
						if($end_stamp > 0 && $end_stamp < $date_counter->get()) {
							break;
						}
					}
					if($reader->recursionHappensOn($date_counter->get())) {
						$e = $this->newRecursionDateTime($recurring_event_datetime, $date_counter->date());
						$all_events->push($e);
					}
					$date_counter->tomorrow();
				}
				$date_counter->reset();
			}
		}
		return $all_events;
	}

	public function newRecursionDateTime($recurring_event_datetime, $start_date)
	{
		$relation = $this->getDateToEventRelation();
		$e = Injector::inst()->get($this->getDateTimeClass(), false);
		foreach ($recurring_event_datetime->db() as $field => $type) {
			$e->$field = $recurring_event_datetime->$field;
		}
		$e->DateTimeID = $recurring_event_datetime->ID;
		$e->StartDate = $start_date;
		$e->EndDate = $start_date;
		$e->$relation = $recurring_event_datetime->$relation;
		$e->ID = "recurring" . self::$reccurring_event_index;
		self::$reccurring_event_index++;
		return $e;
	}


	public function getFeedEvents($start_date, $end_date)
	{
		$start = new \DateTime($start_date);
		// single day views don't pass end dates
		if ($end_date) {
			$end = new \DateTime($end_date);
		} else {
			$end = $start;
		}

		$feeds = $this->Feeds();
		$feedevents = ArrayList::create();
		foreach( $feeds as $feed ) {
			$feedreader = iCal::create($feed->URL);
			foreach ($feedreader->events() as $event) {
				// translate iCal schema into CalendarAnnouncement schema (datetime + title/content)
				$feedevent = CalendarAnnouncement::create()
					->update(
						[
							'ID' => 'ICS_'.$feed->ID,
							'Feed' => true,
							'CalendarID' => $this->ID,
							'Title' => $event['SUMMARY']
						]
					);
				if (isset($event['DESCRIPTION'])) {
					$feedevent->Content = $event['DESCRIPTION'];
				}
				$startdatetime = $this->iCalDateToDateTime($event['DTSTART']);//->setTimezone(new DateTimeZone($this->stat('timezone')));
				$enddatetime = $this->iCalDateToDateTime($event['DTEND']);//->setTimezone(new DateTimeZone($this->stat('timezone')));

                //Set event start/end to midnight to allow comparisons below to work
   				$startdatetime->modify('00:00:00');
				$enddatetime->modify('00:00:00');

				if (($startdatetime < $start && $enddatetime < $start)
					|| $startdatetime > $end && $enddatetime > $end) {
					// do nothing; dates outside range
				} else {
					$feedevent->update(
						[
							'StartDate' => $startdatetime->format('Y-m-d'),
							'StartTime' => $startdatetime->format('H:i:s'),
							'EndDate' => $enddatetime->format('Y-m-d'),
							'EndTime' => $enddatetime->format('H:i:s')
						]
					);
					$feedevents->push($feedevent);
				}
			}
		}
		return $feedevents;
	}

	public function iCalDateToDateTime($date)
	{
		$dt = new \DateTime($date);
		$dt->setTimeZone(new \DateTimezone($this->config()->timezone));
		return $dt;
	}


	public function getAllCalendars()
	{
		$calendars = ArrayList::create();
		$calendars->push($this);
		$calendars->merge($this->NestedCalendars());
		return $calendars;
	}

	public function UpcomingEvents($limit = 5, $filter = null)
	{
		$all = $this->getEventList(
			sfDate::getInstance()->date(),
			sfDate::getInstance()->addMonth($this->DefaultFutureMonths)->date(),
			$filter,
			$limit
		);
		return $all->limit($limit);
	}

	public function UpcomingAnnouncements($limit = 5, $filter = null)
	{
		return $this->Announcements()
			->filter('StartDate:GreaterThan', 'NOW')
			->where($filter)
			->limit($limit);
	}

	public function RecentEvents($limit = null, $filter = null)
	{
		$start_date = sfDate::getInstance();
		$end_date = sfDate::getInstance();
		$l = ($limit === null) ? $this->config()->recent_events_default_limit : $limit;
		$events = $this->getEventList(
			$start_date->subtractMonth($this->DefaultFutureMonths)->date(),
			$end_date->yesterday()->date(),
			$filter,
			$l
		)->sort('StartDate','DESC');

		return $events->limit($limit);
	}

	public function CalendarWidget()
	{
		$calendar = CalendarWidget::create($this);
		$controller = Controller::curr();
		if ($controller instanceof CalendarController) {
			if ($controller->getView() != "default") {
				if ($startDate = $controller->getStartDate()) {
					$calendar->setOption('start', $startDate->format('Y-m-d'));
				}
				if ($endDate = $controller->getEndDate()) {
					$calendar->setOption('end', $endDate->format('Y-m-d'));
				}
			}
		}
		return $calendar;
	}

	public function MonthJumpForm()
	{
		$controller = Controller::curr();
		if (!($controller instanceof CalendarController)) {
			$controller = CalendarController::create($this);
		}
		return $controller->MonthJumpForm();
	}

}