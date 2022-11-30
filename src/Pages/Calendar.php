<?php

/**
 * Calendar page
 * 
 * @author Aaron Carlino
 * @author Grant Heggie
 * @package silverstripe-event-calendar
 */

namespace UncleCheese\EventCalendar\Pages;

use Carbon\Carbon;
use ICal\ICal;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
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
use SilverStripe\View\Requirements;
use UncleCheese\EventCalendar\Models\CachedCalendarEntry;
use UncleCheese\EventCalendar\Models\CalendarAnnouncement;
use UncleCheese\EventCalendar\Models\ICSFeed;
use UncleCheese\EventCalendar\Pages\CalendarController;
use UncleCheese\EventCalendar\Pages\CalendarEvent;
use UncleCheese\EventCalendar\Views\CalendarWidget;
use \Page;

class Calendar extends Page 
{
	private static $table_name = 'UncleCheese_Calendar';

	private static $singular_name = 'Calendar';

	private static $plural_name = 'Calendars';

	private static $db = [
		'DefaultDateHeader'		=> 'Varchar(50)',
		'OtherDatesCount'		=> 'Int',
		'RSSTitle'				=> 'Varchar(255)',
		'DefaultFutureMonths'	=> 'Int',
		'EventsPerPage'			=> 'Int',
		'DefaultView'			=> "Enum('today,week,month,weekend,upcoming','upcoming')"
	];

	private static $has_many = [
		'Announcements'	=> CalendarAnnouncement::class,
		'Feeds'			=> ICSFeed::class
	];

	private static $many_many = [
		'NestedCalendars' => self::class
	];

	private static $belongs_many_many = [
		'ParentCalendars' => self::class
	];

	private static $allowed_children = [
		CalendarEvent::class
	];

	private static $defaults = [
		'DefaultDateHeader'		=> 'Upcoming events',
		'OtherDatesCount'		=> 3,
		'DefaultFutureMonths'	=> 6,
		'EventsPerPage'			=> 10,
		'DefaultView'			=> 'upcoming'
	];

	private static $icon = "unclecheese/silverstripe-event-calendar:client/dist/images/calendar-file.gif";

	private static $description = "A collection of calendar events";

	/**
	 * @var int
	 */
	private static $reccurring_event_index = 0;

	/**
	 * @var int
	 */
	private static $recent_events_default_limit = 9999;

	/**
	 * @var int
	 */
	private static $max_recurring_events_listed = 0;

	/**
	 * @var string
	 */
	private static $event_class = CalendarEvent::class;

	/**
	 * @var string
	 */
	private static $announcement_class = CalendarAnnouncement::class;

	/**
	 * @var string
	 */
	private static $timezone = "America/New_York";

	/**
	 * @var string
	 */
	private static $language = "EN";

	/**
	 * @var bool
	 */
	private static $jquery_included = false;

	/**
	 * @var bool
	 */
	private static $include_default_css = false;

	/**
	 * @var bool
	 */
	private static $include_calendar_js = false;

	/**
	 * @var bool
	 */
	private static $caching_enabled = false;

	protected $eventClass_cache,
			  $announcementClass_cache,
			  $datetimeClass_cache,
			  $dateToEventRelation_cache,
			  $announcementToCalendarRelation_cache,
			  $eventList_cache;

	/**
	 * @return void
	 */
	public static function set_jquery_included($bool = true)
	{
		Config::modify()->set(self::class, 'jquery_included', $bool);
	}

	/**
	 * @return void
	 */
	public static function enable_caching()
	{
		Config::modify()->set(self::class, 'caching_enabled', true);
	}

	public function getCMSFields()
	{
		$self = $this;

		$this->beforeUpdateCMSFields(function($f) use ($self) {

			Requirements::javascript('unclecheese/silverstripe-event-calendar:client/dist/js/calendar_cms.js');

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

	/**
	 * @return string
	 */
	public function getEventClass()
	{
		if ($this->eventClass_cache) {
			return $this->eventClass_cache;
		} 
		return $this->eventClass_cache = self::config()->event_class;
	}

	/**
	 * @return string
	 */
	public function getAnnouncementClass()
	{
		if ($this->announcementClass_cache) {
			return $this->announcementClass_cache;
		}
		return $this->announcementClass_cache = self::config()->announcement_class;
	}

	/**
	 * @return string
	 */
	public function getDateTimeClass()
	{
		if ($this->datetimeClass_cache) {
			return $this->datetimeClass_cache;
		};
		return $this->datetimeClass_cache = Config::inst()
			->get($this->getEventClass(), 'datetime_class');
	}

	/**
	 * @return string
	 */
	public function getDateToEventRelation()
	{
		if ($this->dateToEventRelation_cache) {
			return $this->dateToEventRelation_cache;
		}
		$dateTime = Injector::inst()->get($this->getDateTimeClass());
		foreach ($dateTime->config()->has_one as $rel => $class) {
			if ($class == $this->getEventClass()) {
				return $this->dateToEventRelation_cache = $rel.'ID';
			}
		}
	}

	/**
	 * @param string $start Start date
	 * @param string $end End date
	 * @param string $filter 
	 * @param int $limit Limit result set size
	 * @return DataList
	 */
	public function getCachedEventList($start, $end, $filter = null, $limit = null)
	{
		return CachedCalendarEntry::get()
			->filter("CachedCalendarID", $this->ID)
			->exclude(
				[
					"StartDate:LessThan" => $end,
					"EndDate:GreaterThan" => $start
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

	/**
	 * Get the event list for this calendar, including standard, recurring and announcements
	 * 
	 * @param string $start Start date
	 * @param string $end End date
	 * @param string $filter Additional filters
	 * @param int $limit Limit result set size
	 * @param int $announcementFilter Filter announcements prior to their inclusion
	 * @return DataList
	 */
	public function getEventList(
		$start, 
		$end, 
		$filter = null, 
		$limit = null, 
		$announcementFilter = null
	) {
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
						"EndDate:LessThanOrEqual" => $end
					]
				);
			if ($announcementFilter) {
				$announcements = $announcements->where($announcementFilter);
			}
			if ($announcements) {
				foreach($announcements as $announcement) {
					$eventList->push($announcement);
				}
			}
			if ($recurring = $calendar->getRecurringEvents($filter)) {
				$calendar->addRecurringEvents($start, $end, $recurring, $eventList);
			}
			if ($feedevents = $calendar->getFeedEvents($start,$end)) {
				$eventList->merge($feedevents);
			}
		}

		$eventList = $eventList->sort(
			[
				'StartDate' => 'ASC',
				'StartTime' => 'ASC'
			]
		)->limit($limit);

		return $this->eventList_cache = $eventList;
	}

	/**
	 * Get non-recurring events for this calendar that fall within a start and end date
	 * 
	 * @param string $start Start date
	 * @param string $end End date
	 * @param string $filter Additional filters
	 * @return DataList
	 */
	protected function getStandardEvents($start, $end, $filter = null)
	{
		$eventTable = Config::inst()->get($this->getEventClass(), 'table_name');
		$relation = $this->getDateToEventRelation();
		$siteTreeTable = SiteTree::config()->table_name;

		$list = DataList::create($this->getDateTimeClass())
			->innerJoin($eventTable, "\"$relation\" = \"$eventTable\".\"ID\"")
			->innerJoin($siteTreeTable, "\"$siteTreeTable\".\"ID\" = \"$eventTable\".\"ID\"");

		$filters = [
			'Recursion:not' => 1
		];

		$ids = $this->AllChildren()->column('ID');
		if ($ids) {
			$filters[$relation] = $ids;
		}

		$list = $list->filter($filters);
		
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

	/**
	 * Get the list of events in this calendar that are set to recur
	 * 
	 * @param string $filter Additional filters
	 * @return DataList|false
	 */
	protected function getRecurringEvents($filter = null)
	{
		if ($relation = $this->getDateToEventRelation()) {
			$dtTable = Config::inst()->get($this->getDateTimeClass(), 'table_name');
			$siteTreeTable = SiteTree::config()->table_name;
			$events = DataList::create($this->getEventClass())
				->filter(
					[
						"Recursion" => "1",
						"ParentID" => $this->ID
					]
				)
				->innerJoin($dtTable, "\"$dtTable\".\"$relation\" = \"$siteTreeTable\".\"ID\"");
			if ($filter) {
				$events = $events->where($filter);
			}
			return $events;
		}
		
		return false;
	}

	/**
	 * Get the next set of recurring datetimes for an event
	 * 
	 * @param CalendarEvent $eventObj Event record
	 * @param CalendarDateTime $datetimeObj Datetime record
	 * @param int $limit Limit the result set size
	 * @return ArrayList
	 */
	public function getNextRecurringEvents($eventObj, $datetimeObj, $limit = null)
	{
		$counter = Carbon::parse($datetimeObj->StartDate);

		if ($event = $datetimeObj->Event()->DateTimes()->First()) {
			$endDate = strtotime($event->EndDate ?? '');
		} else {
			$endDate = false;
		}
		$counter->addDay()->startOfDay();
		$dates = ArrayList::create();
		while ($dates->Count() != $this->OtherDatesCount) {
			// check the end date
			if ($endDate && $endDate > 0 && $endDate <= $counter->getTimestamp()) {
				break;
			}
			if ($eventObj->getRecursionReader()->recursionHappensOn($counter->getTimestamp())) {
				$dates->push($this->newRecursionDateTime($datetimeObj, $counter->toDateString()));
			}
			$counter->addDay()->startOfDay();
		}

		return $dates;
	}

	/**
	 * Add recurring events to a list of events
	 * 
	 * @param string $startDate Start date
	 * @param string $endDate End date
	 * @param DataList $recurringEvents Set of recurring events
	 * @param ArrayList $allEvents The list to add events to
	 */
	protected function addRecurringEvents($startDate, $endDate, $recurringEvents, $allEvents)
	{
		$dateCounter = Carbon::parse($startDate);
		$end = Carbon::parse($endDate);

		foreach ($recurringEvents as $recurringEvent) {
			$reader = $recurringEvent->getRecursionReader();
			$relation = null;
			foreach ($recurringEvent->config()->has_many as $rel => $class) {
				if ($class == $this->getDateTimeClass()) {
					$relation = $rel;
				}
			}
			if (!$relation) {
				continue;
			}
			$recurringEventDatetimes = $recurringEvent->$relation()->filter(
				[
					'StartDate:LessThanOrEqual' => $end->toDateString(),
					'EndDate:GreaterThanOrEqual' => $dateCounter->toDateString()
				]
			);

			foreach ($recurringEventDatetimes as $recurringEventDatetime) {
				$start = Carbon::parse($recurringEventDatetime->StartDate);
				if ($start->getTimestamp() > $dateCounter->getTimestamp()) {
					$dateCounter = $start;
				}
				$recurrenceAdded = 0;
				while ($dateCounter <= $end) {
					if (self::config()->max_recurring_events_listed
						&& ($recurrenceAdded == self::config()->max_recurring_events_listed)
					) {
						break;
					}
					// check the end date
					if ($recurringEventDatetime->EndDate) {
						$endStamp = strtotime($recurringEventDatetime->EndDate ?? '');
						if ($endStamp > 0 && $endStamp < $dateCounter->getTimestamp()) {
							break;
						}
					}
					if ($reader->recursionHappensOn($dateCounter->getTimestamp())) {
						$e = $this->newRecursionDateTime($recurringEventDatetime, $dateCounter->toDateString());
						$allEvents->push($e);
						$recurrenceAdded++;
					}
					$dateCounter->addDay()->startOfDay();
				}
				$dateCounter = Carbon::parse($startDate);
			}
		}
	}

	/**
	 * Create a Datetime for a recursion instance (recurrence)
	 * 
	 * @param mixed $recurringEventDatetime The Datetime to base the recurrence off
	 * @param string $startDate The start date
	 * @return mixed An instance of the class defined in CalendarEvent::datetime_class
	 */
	public function newRecursionDateTime($recurringEventDatetime, $startDate)
	{
		$relation = $this->getDateToEventRelation();
		$e = Injector::inst()->get($this->getDateTimeClass(), false);
		foreach ($recurringEventDatetime->config()->db as $field => $type) {
			$e->$field = $recurringEventDatetime->$field;
		}
		$e->DateTimeID = $recurringEventDatetime->ID;
		$e->StartDate = $startDate;
		$e->EndDate = $startDate;
		$e->$relation = $recurringEventDatetime->$relation;
		$e->ID = "recurring" . self::$reccurring_event_index;
		self::$reccurring_event_index++;
		return $e;
	}

	/**
	 * Get events from specified feeds that fall within a date range
	 * 
	 * @param string $stardDate Range start date
	 * @param string $endDate Range end date
	 * @return ArrayList
	 */
	public function getFeedEvents($startDate, $endDate)
	{
		$start = new \DateTime($startDate);
		// single day views don't pass end dates
		$end = $endDate ? new \DateTime($endDate) : $start;
		$feeds = $this->Feeds();
		$feedevents = ArrayList::create();
		foreach ($feeds as $feed) {
			$feedreader = new ICal($feed->URL);
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
				$startdatetime = $this->iCalDateToDateTime($event['DTSTART']);
				$enddatetime = $this->iCalDateToDateTime($event['DTEND']);

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

	/**
	 * @return \DateTime
	 */
	public function iCalDateToDateTime($date)
	{
		$dt = new \DateTime($date);
		$dt->setTimeZone(new \DateTimezone($this->config()->timezone));
		return $dt;
	}

	/**
	 * @return ArrayList
	 */
	public function getAllCalendars()
	{
		$calendars = ArrayList::create();
		$calendars->push($this);
		$calendars->merge($this->NestedCalendars());
		return $calendars;
	}

	/**
	 * @return DataList
	 */
	public function UpcomingEvents($limit = 5, $filter = null)
	{
		$date = Carbon::now();
		return $this->getEventList(
			$date->toDateString(),
			$date->addMonths($this->DefaultFutureMonths)->toDateString(),
			$filter,
			$limit
		)->limit($limit);
	}

	public function UpcomingAnnouncements($limit = 5, $filter = null)
	{
		return $this->Announcements()
			->filter('StartDate:GreaterThan', 'NOW()')
			->where($filter)
			->limit($limit);
	}

	/**
	 * @return DataList
	 */
	public function RecentEvents($limit = null, $filter = null)
	{
		$startDate = Carbon::now();
		$endDate = Carbon::now();
		$l = ($limit === null) ? $this->config()->recent_events_default_limit : $limit;
		$events = $this->getEventList(
			$startDate->subMonths($this->DefaultFutureMonths)->toDateString(),
			$endDate->yesterday()->toDateString(),
			$filter,
			$l
		)->sort('StartDate DESC');

		return $events->limit($limit);
	}

	/**
	 * @return CalendarWidget
	 */
	public function getCalendarWidget()
	{
		$calendar = CalendarWidget::create($this);
		$controller = Controller::curr();
		if ($controller instanceof CalendarController) {
			if ($controller->getView() != "default") {
				if ($startDate = $controller->getStartDate()) {
					$calendar->setOption('start', $startDate->toDateString());
				}
				if ($endDate = $controller->getEndDate()) {
					$calendar->setOption('end', $endDate->toDateString());
				}
			}
		}
		return $calendar;
	}

	public function getMonthJumpForm()
	{
		$controller = Controller::curr();
		if (!($controller instanceof CalendarController)) {
			$controller = CalendarController::create($this);
		}
		return $controller->MonthJumpForm();
	}

}