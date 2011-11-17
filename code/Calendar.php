<?php

class Calendar extends Page
{

	static $db = array(
 		'DefaultEventDisplay' => 'Int',
		'DefaultDateHeader' => 'Varchar(50)',
		'OtherDatesCount' => 'Int',
		'RSSTitle' => 'Varchar(255)'
	);
	
	static $has_many = array (
		'Announcements' => 'CalendarDateTime',
		'CalendarEvents' => 'CalendarEvent',
		'Feeds' => 'ICSFeed'
	);
	
	static $many_many = array (
		'NestedCalendars' => 'Calendar'
	);
	
	static $belongs_many_many = array (
		'ParentCalendars' => 'Calendar'
	);
	
	static $allowed_children = array (
		'CalendarEvent'
	);
	
	static $defaults = array (
		'DefaultEventDisplay' => '10',
		'DefaultDateHeader' => 'Upcoming Events',
		'OtherDatesCount' => '3'
	);
	
	static $icon = "event_calendar/images/calendar";
	
	static $language;
	static $timezone;
	static $calScale = "GREGORIAN";
  	static $defaultFutureMonths = 6;
  
	protected $event_class = null;
	protected $event_object = null;
	protected $event_datetime_class = null;
	protected $event_datetime_object = null;
	
	protected $start_date;
	protected $end_date;
	
	protected $filter_fields;
	
	// Used to create a fake ID for the recursive events
	static $recurring_event_index = 1;
	
	
	static function set_param($param, $value)
	{
		self::$$param = $value;
	}
	
	public function getProjectConfiguration(BedrockSetting $settings) {
		if($format = $settings->getDateFormat())
			CalendarDateTime::set_date_format($format);
		if($months = $settings->getDefaultFutureMonths())
			self::$defaultFutureMonths = $months;
		if($time = $settings->getTimeFormat())
			CalendarDateTime::set_time_format($time);
	}
	
	public function getEventClass() {
		if($this->event_class !== null)
			return $this->event_class;
			
		$class = get_class($this);
		$has_manys = eval("return {$class}::\$has_many;");
		if(is_array($has_manys)) {
			foreach($has_manys as $c) {
				if($c == 'CalendarEvent' || is_subclass_of($c, 'CalendarEvent')) {
					$this->event_class = $c;
					return $c;
				}
			}			
		}
	}
	
	public function getEventObject() {
		if($this->event_object !== null)
			return $this->event_object;

		$c = $this->getEventClass();
		$this->event_object = new $c;
		return $this->event_object;
	}
	
	public function getEventDateTimeClass() {
		if($this->event_datetime_class !== null)
			return $this->event_datetime_class;
		$this->event_datetime_class = $this->getEventObject()->getDateTimeClass();
		return $this->event_datetime_class;
	}
	
	public function getEventDateTimeObject() {
		if($this->event_datetime_object !== null)
			return $this->event_datetime_object;
		
		$c = $this->getEventDateTimeClass();	
		$this->event_datetime_object = new $c; 
		return $this->event_datetime_object;
	}
	
	public function getCMSFields()
	{
		$datetimeObj = $this->getEventDateTimeObject();
		$f = parent::getCMSFields();
		$configuration = _t('Calendar.CONFIGURATION','Configuration');
		$f->addFieldsToTab("Root.Content.$configuration", array(
			new NumericField('DefaultEventDisplay', _t('Calendar.NUMBEROFEVENTS','Number of events to display on default view.')),
			new TextField('DefaultDateHeader', _t('Calendar.DEFAULTDATEHEADER','Default date header (displays when no date range has been selected)')),
			new NumericField('OtherDatesCount', _t('Calendar.NUMBERFUTUREDATES','Number of future dates to show for repeating events'))
		));
		
		$table = $this->getEventDateTimeObject()->getAnnouncementTable($this->ID);
		$announcements = _t('Calendar.Announcements','Announcements');
		$f->addFieldToTab("Root.Content.$announcements", $table);
		
		$tableClass = class_exists('DataObjectManager') ? 'DataObjectManager' : 'ComplexTableField';
		$table = new $tableClass(
			$this,
			'Feeds',
			'ICSFeed',
			array('Title' => 'Title of Feed', 'URL' => 'URL'),
			'getCMSFields_forPopup'
		);
		$table->setAddTitle(_t('Calendar.ICSFEED','ICS Feed'));
		$table->setParentClass("Calendar");
		$feeds = _t('Calendar.FEEDS','Feeds');
		$f->addFieldToTab("Root.Content.$feeds", $table);
		if($cals = DataObject::get("Calendar")) {
			if($cals->Count() > 1) {
				$map = $cals->toDropdownMap();
				unset($map[$this->ID]);
				$f->addFieldToTab("Root.Content.$feeds", new CheckboxSetField(
					'NestedCalendars', 
					_t('Calendar.NESTEDCALENDARS','Include events from these calendars'),
					$map
				));
			}
		}
		
		$f->addFieldToTab("Root.Content.Main", new TextField('RSSTitle', _t('Calendar.RSSTITLE','Title of RSS Feed')),'Content');
		$this->extend('updateCalendarFields',$f);
		return $f;	
	}
	
	public function getEventJoin()
	{
		//$suffix = Versioned::current_stage() == "Live" ? "_Live" : "";
		$join = "LEFT JOIN `CalendarEvent` ON `CalendarEvent`.ID = `CalendarDateTime`.EventID";
		if(is_subclass_of($this->getEventObject(), "CalendarEvent")) {
			$parents = array_reverse(ClassInfo::ancestry($this->getEventClass()));
			foreach($parents as $class) {
				if(ClassInfo::hasTable($class)) {				
					if($class == "CalendarEvent") break;
						$join .= " LEFT JOIN `$class` ON `$class`.ID = `CalendarEvent`.ID";
				}
			}
		}
		return $join;
	}
	
	public function getDateJoin()
	{
		$join = "LEFT JOIN `CalendarDateTime` ON `CalendarDateTime`.EventID = `CalendarEvent`.ID";
		if(is_subclass_of($this->getEventDateTimeObject(), "CalendarDateTime")) {
		  $parents = array_reverse(ClassInfo::ancestry($this->getEventDateTimeClass()));
		  foreach($parents as $class) {
		    if(ClassInfo::hasTable($class)) {
		      if($class == "CalendarDateTime") break;
		      $join .= " LEFT JOIN `$class` ON `$class`.ID = `CalendarDateTime`.ID";
		    }
		  }
		}
		return $join;
	}
	
	
	
	protected function getEventIds()
	{
		$ids = array();
		foreach($this->getAllCalendars() as $calendar) {
			if($children = $calendar->AllChildren()) {
				$ids = array_merge($ids, $children->column('ID'));
			}
		}
		if(!empty($ids)) return $ids;
		return false;
	}
	
	
	public function newRecursionDateTime($recurring_event_datetime, $start_date)
	{
		$c = $this->getEventDateTimeClass();
		$e = new $c;
		foreach($recurring_event_datetime->db() as $field => $type) {
			$e->$field = $recurring_event_datetime->$field;
		}
		$e->StartDate = $start_date;
		$e->EndDate = $start_date;
		$e->EventID = $recurring_event_datetime->EventID;
		$e->ID = "recurring" . self::$recurring_event_index;
		self::$recurring_event_index++;
		return $e;
	}
	
	protected function getStandardEvents($filter = null)
	{
		if(!$ids = $this->getEventIds()) return false;
		$start = $this->start_date->date();
		$end = $this->end_date->date();

		$where = "
			Recursion != 1 
			AND (
				(StartDate <= '$start' AND EndDate >= '$end') OR
				(StartDate BETWEEN '$start' AND '$end') OR
				(EndDate BETWEEN '$start' AND '$end')
			) 
			AND 
			`CalendarDateTime`.EventID IN (" . implode(',',$ids) . ")
		";
		$where .= $filter !== null ? " AND " . $filter : "";
		return DataObject::get(
			$this->getEventDateTimeClass(),
			$where,
			"StartDate ASC, StartTime ASC, `CalendarDateTime`.EventID ASC",
			$this->getEventJoin()
		);
	}

	/**
	 * Gets all recurring events attached to this calendar and any nested
	 * calendars.
	 *
	 * @param  string $filter
	 * @return DataObjectSet
	 */
	protected function getRecurringEvents($filter = null) {
		$parents = array();

		foreach ($this->getAllCalendars() as $calendar) {
			$parents[] = $calendar->ID;
		}

		$where = sprintf(
			'"Recursion" = 1 AND "ParentID" IN (%s)', implode(', ', $parents)
		);

		if ($filter) $where .= "AND $filter";

		return DataObject::get(
			$this->getEventClass(),
			$where,
			'"CalendarDateTime"."StartDate" ASC',
			$this->getDateJoin());
	}


	protected function addRecurringEvents($recurring_events,$all_events)
	{
		$date_counter = $this->start_date;
		foreach($recurring_events as $recurring_event) {
			if($recurring_event_datetime = DataObject::get_one($this->getEventDateTimeClass(),"EventID = {$recurring_event->ID}")) {			
				while($date_counter->get() <= $this->end_date->get()){
					// check the end date
					if($recurring_event_datetime->EndDate) {
						$end = strtotime($recurring_event_datetime->EndDate);
						if($end > 0 && $end <= $date_counter->get())
							break;
					}
					if($recurring_event->recursionHappensOn($date_counter->get())) {
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
	
	public function getNextRecurringEvents($event_obj, $datetime_obj, $limit = null)
	{
		$counter = new sfDate($datetime_obj->StartDate);
		if($event = $datetime_obj->Event()->DateTimes()->First())
			$end_date = strtotime($event->EndDate);
		else
			$end_date = false;
		$counter->tomorrow();
		$dates = new DataObjectSet();
		while($dates->Count() != $this->OtherDatesCount) {
			// check the end date
			if($end_date) {
				if($end_date > 0 && $end_date <= $counter->get())
					break;
			}
			if($event_obj->recursionHappensOn($counter->get()))
				$dates->push($this->newRecursionDateTime($datetime_obj,$counter->date()));
			$counter->tomorrow();
		}
		return $dates;	
	}
	
	/**
	* Gets all (recurring) events from .ics feeds (enough to support recurring events from a Gcal feed
	* SUPPORTS recurring events; RRULE, FREQ, INTERVAL, UNTIL (NOT supported: WKST, BYDAY/MONTH/ETC, COUNT, ETC)
	* @TODO: fix duplicated code adding new event to $all_events (needed for correct dates to show)...
	*/
	protected function importFromFeeds($all_events, $start_date = null, $end_date = null)
	{
		foreach($this->Feeds() as $feed) {
			$parser = new iCal(array($feed->URL));
			$ics_events = $parser->iCalReader();
			if(is_array($ics_events) && isset($ics_events[$feed->URL]) && is_array($ics_events[$feed->URL])) {
				$dt_start = null;
				$dt_end = null;
				foreach($ics_events[$feed->URL] as $event) {
					if( (!$dt_start && !$dt_end) || (!isset($event[$dt_start]) || (!isset($event[$dt_end]))) ) {
						foreach($event as $k => $v) {
							if(substr($k, 0, 7) == "DTSTART")
								$dt_start = $k;
							if(substr($k, 0, 5) == "DTEND")
								$dt_end = $k;
						}
					}
					if(isset($event[$dt_start]) && isset($event[$dt_end])) {
						list($start_date, $end_date, $start_time, $end_time) = CalendarUtil::date_info_from_ics($event[$dt_start], $event[$dt_end]);
						$t_start = strtotime($start_date);
						$t_end = strtotime($end_date);
						if($t_start >= $this->start_date->get() || array_key_exists("RRULE", $event) ) {
							
							if (array_key_exists("RRULE", $event)){
								// Set recurring events;
								//RRULE:FREQ=WEEKLY;BYDAY=TH
								//RRULE:FREQ=WEEKLY;INTERVAL=2;BYDAY=TH
								//RRULE:FREQ=WEEKLY;COUNT=10;WKST=SU;BYDAY=TU,TH
								//RRULE:FREQ=WEEKLY;BYDAY=TU,TH;UNTIL=20110426T151500Z
								
								$rrule = explode(';', $event["RRULE"]);
								// build nice keyed array from rrule;
								$rrule_arr = array();
								foreach ($rrule as $part) {
									$part = explode('=', $part);
									$rrule_arr[$part[0]] = $part[1];
								}
								if( array_key_exists("INTERVAL", $rrule_arr) ){ 
									$interval = (int) $rrule_arr["INTERVAL"];
								} else { $interval = 1; }
								if(array_key_exists("UNTIL", $rrule_arr)){
									$until = explode("T", $rrule_arr["UNTIL"]);
									$until = CalendarUtil::getDateFromString($until[0]);
								} else { $until = date( "Y-m-d", $this->end_date->get() ); }
								if (array_key_exists("FREQ", $rrule_arr)){
									if($rrule_arr["FREQ"]=='DAILY') $freq = 'day';
									if($rrule_arr["FREQ"]=='WEEKLY') $freq = 'week';
									if($rrule_arr["FREQ"]=='MONTHLY') $freq = 'month';
									if($rrule_arr["FREQ"]=='YEARLY') $freq = 'year';
								}

								for( $occur = $t_start; 
									($occur<=$this->end_date->get() && $occur<=strtotime($until)); 
									$occur = strtotime( date("Y-m-d", $occur)." +".$interval." $freq") ) {
									
									if( $occur >= $this->start_date->get() && $occur <= $this->end_date->get() ){
										$c = $this->getEventDateTimeClass();
										$new_date = new $c();
										$new_date->StartDate = date("Y-m-d", $occur);
										$new_date->EndDate = date("Y-m-d", $occur);
										$new_date->StartTime = $start_time;
										$new_date->EndTime = $end_time; 
										if(isset($event['DESCRIPTION']) && !empty($event['DESCRIPTION']))
											$new_date->Content = $event['DESCRIPTION'];
										if(isset($event['SUMMARY']) && !empty($event['SUMMARY']))
											$new_date->Title = $event['SUMMARY'];
										$new_date->CalendarID = $this->ID;
										$new_date->ID = "feed" . self::$recurring_event_index;
										$new_date->Feed = true;
										$new_date->WeeklyInterval = true;

										$all_events->push($new_date);
										self::$recurring_event_index++;
									}
								}
									
									
							} else {
								if( $t_end <= $this->end_date->get() ){
									// place single (non-recurring) event in $all_events;
									$c = $this->getEventDateTimeClass();
									$new_date = new $c();
									$new_date->StartDate = $start_date;
									$new_date->StartTime = $start_time;
									$new_date->EndDate = $end_date;
									$new_date->EndTime = $end_time;
									if(isset($event['DESCRIPTION']) && !empty($event['DESCRIPTION']))
										$new_date->Content = $event['DESCRIPTION'];
									if(isset($event['SUMMARY']) && !empty($event['SUMMARY']))
										$new_date->Title = $event['SUMMARY'];
									$new_date->CalendarID = $this->ID;
									$new_date->ID = "feed" . self::$recurring_event_index;
									$new_date->Feed = true;
									$all_events->push($new_date);
									self::$recurring_event_index++;
								}
							}
							
						}
					}
				}
			}
		}
		return $all_events;
	}
  	
  	public function Events($filter = null, $start_date = null, $end_date = null, $default_view = false, $limit = null, $announcement_filter = null)
  	{
		$this->start_date = ($start_date instanceof sfDate) ? $start_date : ($start_date !== null ? new sfDate($start_date) : new sfDate());
		$this->end_date = ($end_date instanceof sfDate) ? $end_date : ($end_date !== null ? new sfDate($end_date) : new sfDate());

		if($end_date instanceof sfDate)
			$this->end_date = $end_date;
		elseif($end_date !== null) 
			$this->end_date = new sfDate($end_date);
		else {
			$this->end_date = new sfDate($this->start_date->addMonth(Calendar::$defaultFutureMonths)->date());
			$default_view = true;
			$this->start_date->reset();
		}
		
		if($events = $this->getStandardEvents($filter))
			$event_list = $events;
		else
			$event_list = new DataObjectSet();
		
		$where = $announcement_filter !== null ? " AND $announcement_filter" : "";
		$start = $this->start_date->date();
		$end = $this->end_date->date();
		
		
		foreach($this->getAllCalendars() as $calendar) {
			if($announcements = DataObject::get(
					"CalendarDateTime",
				   "CalendarDateTime.CalendarID={$calendar->ID}
				      AND (
				         (StartDate <= '$start' AND EndDate >= '$end') OR
				         (StartDate BETWEEN '$start' AND '$end') OR
				         (EndDate BETWEEN '$start' AND '$end')
				      ) 
				     
				      $where",				
	      			"StartDate ASC"
			)) {
	
				foreach($announcements as $announcement)
					$event_list->push($announcement);
			}		
		}

		
		if($recurring = $this->getRecurringEvents($filter)) {
			$event_list = $this->addRecurringEvents($recurring, $event_list);
		}
		
		if($this->Feeds())
			$event_list = $this->importFromFeeds($event_list);
		
		$e = $event_list->toArray();		
		CalendarUtil::date_sort($e);
		$max = $limit === null ? $this->DefaultEventDisplay : $limit;
		if($default_view && $event_list->Count() > $max) {
			$e = array_slice($e, 0, $max);
		}
		$event_list = new DataObjectSet($e);
		return $event_list;
	}
	
	public function UpcomingEvents($limit = null, $filter = null, $announcement_filter = null) 
	{
		return $this->Events($filter, null, null, true, ($limit === null ? $this->DefaultEventDisplay : $limit), $announcement_filter);
	}

	public function RecentEvents($limit = null, $filter = null, $announcement_filter = null) 
	{
		$start_date = new sfDate();
		$end_date = new sfDate();
		$l = ($limit === null) ? "9999" : $limit;
		$events = $this->Events(
			$filter, 
			$start_date->subtractMonth(Calendar::$defaultFutureMonths), 
			$end_date->yesterday(), 
			false, 
			$l,
			$announcement_filter
		);
		$events->sort('StartDate','DESC');
		return $events->getRange(0,$limit);
	}
	
	public static function is_filtered()
	{
		return isset($_GET['filter']) && $_GET['filter'] == 1;
	}
	
	public static function buildFilterString()
	{
		$filters = "";
		if(Calendar::is_filtered()) {
			$filters .= "filter=1";
			foreach(Calendar::getRawFilters() as $key => $value)
				$filters .= "&amp;$key=$value";
		}
		return $filters;
	}
	
	private static function is_filter_key($key)
	{
		return substr($key, 0, 7) == "filter_";
	}
	
	public static function getRawFilters()
	{
		$filters = array();
		foreach($_GET as $key => $value) {
			if(self::is_filter_key($key) && !empty($value))
				$filters[$key] = $value;
		}
		return $filters;
	}

	public static function getCleanFilters()
	{
		if(self::is_filtered()) {
			$filters = array();
			foreach($_GET as $key => $value) {
				if(self::is_filter_key($key)) {
					$filters[str_replace("filter_","",$key)] = $value;
				}
			}
			return $filters;
		}
		return false;
	}
	
	/**
	 * Swaps out underscores with periods for relational data in the SQL query.
	 *	e.g. "MyEvent_Location" becomes "MyEvent.Location"
	 */
	public static function getFiltersForDB()
	{
		if($filters = self::getCleanFilters()) {
			$for_db = array();
			$event_filters = array();
			$datetime_filters = array();
			foreach($filters as $key => $value) {
				$db_field = str_replace("_",".",$key);
				if(stristr($db_field,".") !== false) {
					$parts = explode(".",$db_field);
					$table = $parts[0];
					$field = $parts[1];
					$db_field = "`".$table."`.".$field;
				}
				else {
					$table = $db_field;
					$db_field = "`".$table."`";
				}
        if($table == "CalendarEvent" || is_subclass_of($table, "CalendarEvent"))
          $event_filters[] = $table;
        else if($table == "CalendarDateTime" || is_subclass_of($table, "CalendarDateTime"))
          $datetime_filters[] = $table;
				$for_db[] = "$db_field = '$value'";
			}
			return array($for_db, $event_filters, $datetime_filters);
		}
		return false;
	}

	
	
	public function getFilterFields() {
		return new CalendarFilterFieldSet();
	}
	
	
	public function MonthJumper() {
		return $this->renderWith('MonthJumper');
	}
	
	public function MonthJumpForm() {
		if(!$date = CalendarUtil::getDateFromURL()) {
			$date = null;
		}
		$start_date = new sfDate($date);
		$dummy = new sfDate($start_date->dump());
		$range = range(($dummy->subtractYear(3)->format('Y')), ($dummy->addYear(6)->format('Y')));
		$year_map = array_combine($range, $range);
		$f = new Form(
			Controller::curr(),
			"MonthJumpForm",
			new FieldSet (
				new DropdownField('Month','', CalendarUtil::getMonthsMap('%B'), $start_date->format('m')),
				new DropdownField('Year','', $year_map, $start_date->format('Y'))
			),
			new FieldSet (
				new FormAction('doMonthJump', _t('Calendar.JUMP','Go'))
			)
		);
		return $f;	
	}
	
	public function doMonthJump($data, $form) {
		return Director::redirect($this->Link('view').'/'.$data['Year'].$data['Month']);
	}
	
	public function getAllCalendars() {
		$calendars = array($this);
		if($extras = $this->NestedCalendars()) {
			foreach($extras as $cal) {
				$calendars[] = $cal;
			}
		}
		return $calendars;
	}
	
}

class Calendar_Controller extends Page_Controller
{
	protected $view;
	protected $year;
	protected $month;
	protected $day;
	
	protected $start_date;
	protected $end_date;
	
	public $filter_form;
	
	
	static $allowed_actions = array (
		'MonthJumpForm',
		'view',
		'rss',
		'ics',
		'ical',
		'import',
		'showevent',
		'CalendarFilterForm'
	);
	

	public function init()
	{
		RSSFeed::linkToFeed($this->Link() . "rss", "RSS Feed of this calendar");
		$this->parseURL();
		parent::init();
	}

	public function parseURL()
	{
		
		if($this->urlParams['Action'] && $this->urlParams['Action'] == "view") {		
			$this->start_date = new sfDate(CalendarUtil::getDateFromString($this->urlParams['ID']));
			// User has specified an end date.
			if(isset($this->urlParams['OtherID'])) {
				$this->view = "range";
				$this->end_date = new sfDate(CalendarUtil::getDateFromString(str_replace("-","",$this->urlParams['OtherID'])));
			}
			// No end date specified. Now we have to make one based on the amount of data given in the start date.
			// e.g. 2008-08 will show the entire month of August, and 2008-08-03 will only show events for one day.
			else {
				switch(strlen(str_replace("-","",$this->urlParams['ID'])))
				{
					case 8:
					$this->view = "day";
					$this->end_date = new sfDate($this->start_date->get()+1);
					break;
					
					case 6:
					$this->view = "month";
					$this->end_date = new sfDate($this->start_date->finalDayOfMonth()->date());
					break;
					
					case 4:
					$this->view = "year";
					$this->end_date = new sfDate($this->start_date->finalDayOfYear()->date());
					break;
					
					default:
					$this->view = "default";
					$this->end_date = new sfDate($this->start_date->addMonth(Calendar::$defaultFutureMonths)->date());
					break;
				}
			}
		}
		else {
			// The default "landing page." No dates specified. Just show first X number of events (see Calendar::DefaultEventDisplay)
			// Why 6 months? Because otherwise the loop will just run forever.
	
			$this->view = "default";
			$this->start_date = new sfDate(date('Y-m-d'));
			$this->end_date = new sfDate($this->start_date->addMonth(Calendar::$defaultFutureMonths)->date());
		}
		$this->start_date->reset();
	}
	
	public function view()
	{
		return array();
	}
	
	public function showevent()
	{
		if(!$url_segment = $this->getRequest()->param('ID')) {
			return Director::redirectBack();
		}
		if(!$event = SiteTree::get_by_link($url_segment)) {
			return $this->httpError(404);
		}
		$valid_parents = array($this->ID);
		if($calendars = $this->NestedCalendars()) {
			$valid_parents = array_merge($valid_parents, $calendars->column('ID'));
		}
		if(!in_array($event->ParentID, $valid_parents)) {
			return $this->httpError(404);
		}
		return array(
			'Event' => $event
		);
		
	}

	/**
	 * Send ical file of multiple upcoming events using ICSWriter
	 *
	 * @todo Support recurring events.
	 * @see ICSWriter
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function ical() {
		$writer = new ICSWriter($this->data(), Director::absoluteURL('/'));
		$writer->sendDownload();
	}
	
	// TO-DO: Account for recurring events.
	public function ics()
	{
		$feed = false;
		$announcement = false;
		if(stristr($this->urlParams['ID'], "feed") !== false) {
			$id = str_replace("feed","",$this->urlParams['ID']);
			$feed = true;		
		}
		else if(stristr($this->urlParams['ID'], "announcement-") !== false) {

			$id = str_replace("announcement-","",$this->urlParams['ID']);
			$announcement = true;
		}
		else {
			$id = $this->urlParams['ID'];
			$announcement = false;
		}
		if(is_numeric($id) && isset($this->urlParams['OtherID'])) {
			if(!$feed) { 
				$event = DataObject::get_by_id($announcement ? $this->data()->getEventDateTimeClass() : $this->data()->getEventClass(), $id);
				$FILENAME = $announcement ? preg_replace("/[^a-zA-Z0-9s]/", "", $event->Title) : $event->URLSegment;
			}
			else
				$FILENAME = preg_replace("/[^a-zA-Z0-9s]/", "", urldecode($_REQUEST['title']));

			$FILENAME .= ".ics";
			$HOST = $_SERVER['HTTP_HOST'];
			$TIMEZONE = Calendar::$timezone;
			$LANGUAGE = Calendar::$language;
			$CALSCALE = Calendar::$calScale;
			$parts = explode('-',$this->urlParams['OtherID']);
			$START_TIMESTAMP = $parts[0];
			$END_TIMESTAMP = $parts[1];
			if(!$feed)
				$URL = $announcement ? $event->Calendar()->AbsoluteLink() : $event->AbsoluteLink();
			else
				$URL = "";
			$TITLE = $feed ? $_REQUEST['title'] : $event->Title;
			header("Cache-Control: private");
			header("Content-Description: File Transfer");
			header("Content-Type: text/calendar");
			header("Content-Transfer-Encoding: binary");
	  		if(stristr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
 				header("Content-disposition: filename=".$FILENAME."; attachment;");
 			else
 				header("Content-disposition: attachment; filename=".$FILENAME);
			
			// pull out the html comments
			return trim(strip_tags($this->customise(array(
				'HOST' => $HOST,
				'LANGUAGE' => $LANGUAGE,
				'TIMEZONE' => $TIMEZONE,
				'CALSCALE' => $CALSCALE,
				'START_TIMESTAMP' => $START_TIMESTAMP,
				'END_TIMESTAMP' => $END_TIMESTAMP,
				'URL' => $URL,
				'TITLE' => $TITLE
			))->renderWith(array('ics'))));
		}
		else {
			Director::redirectBack();
		}
	}
	
	
	public function RSSLink()
	{
		return $this->Link('rss');
	}
	
	public function rss() 
	{
		$events = $this->data()->UpcomingEvents(null,$this->DefaultEventDisplay);
		foreach($events as $event) {
			$event->Title = strip_tags($event->_Dates()) . " : " . $event->EventTitle();
			$event->Description = $event->EventContent();
		}
		$rss_title = $this->RSSTitle ? $this->RSSTitle : sprintf(_t("Calendar.UPCOMINGEVENTSFOR","Upcoming Events for %s"),$this->Title);
		$rss = new RSSFeed($events, $this->Link(), $rss_title, "", "Title", "Description");

		if(is_int($rss->lastModified)) {
			HTTP::register_modification_timestamp($rss->lastModified);
			header('Last-Modified: ' . gmdate("D, d M Y H:i:s", $rss->lastModified) . ' GMT');
		}
		if(!empty($rss->etag)) {
			HTTP::register_etag($rss->etag);
		}
		$xml = str_replace('&nbsp;', '&#160;', $rss->renderWith('RSSFeed'));
		$xml = preg_replace('/<!--(.|\s)*?-->/', '', $xml);
		$xml = trim($xml);
		HTTP::add_cache_headers();
		header("Content-type: text/xml");
		echo $xml;
	}
	
	public function import()
	{
    if(isset($this->urlParams['ID']))
      $file = Director::baseFolder()."/event_calendar/import/".$this->urlParams['ID'].".ics";
      if(file_exists($file)) {
  			$parser = new iCal(array($file));
  			$ics_events = $parser->iCalReader();
  			if(is_array($ics_events) && is_array($ics_events[$file])) {
  				$dt_start = null;
  				$dt_end = null;
  				$i=1;
  				foreach($ics_events[$file] as $event) {
  					if( (!$dt_start && !$dt_end) || (!isset($event[$dt_start]) || (!isset($event[$dt_end]))) ) {
  						foreach($event as $k => $v) {
  							if(substr($k, 0, 7) == "DTSTART")
  								$dt_start = $k;
  							if(substr($k, 0, 5) == "DTEND")
  								$dt_end = $k;
  						}
  					}
  					if(isset($event[$dt_start]) && isset($event[$dt_end])) {
  						list($start_date, $end_date, $start_time, $end_time) = CalendarUtil::date_info_from_ics($event[$dt_start], $event[$dt_end]);
							$c = $this->data()->getEventDateTimeClass();
							$new_date = new $c();
							$new_date->StartDate = $start_date;
							$new_date->StartTime = $start_time;
							$new_date->EndDate = $end_date;
							$new_date->EndTime = $end_time;
							if(isset($event['DESCRIPTION']) && !empty($event['DESCRIPTION']))
								$new_date->Content = $event['DESCRIPTION'];
							if(isset($event['SUMMARY']) && !empty($event['SUMMARY']))
								$new_date->Title = $event['SUMMARY'];
							$new_date->CalendarID = $this->ID;
							$new_date->write();
							echo sprintf("<p style='color:green;'>Event <em>%s</em> imported successfully, and was assigned ID %d</p>",$new_date->Title, $new_date->ID);
  					}
  					else
            	echo sprintf("<p style='color:red;'>Event #%d could not be imported.</p>",$i);  					 
  				  $i++;	   					
  				}
  		  }
  		  die();        
      }
      else
        die("The file $file could not be found.");      
	}
	
	public function doCalendarFilter($data,$form)
	{
		$link = $this->Link('view');
		if(isset($data['StartYear'])) {
			$link .= '/' . $data['StartYear'] . "-" . $data['StartMonth'] . "-" . $data['StartDay'] . "/" . $data['EndYear'] . "-" . $data['EndMonth'] . "-" . $data['EndDay'];
		}
		if(isset($data['filter'])) {
			$link .= "?filter=1";
			foreach($data['filter'] as $key => $value) {
				if(!empty($value))
					$link .= "&filter_" . $key . "=" . urlencode($value);
			}
		}
							
		Director::redirect($link);
	}
		
	public function DateHeader()
	{
		switch($this->view)
		{
			case "day":
				return CalendarUtil::localize($this->start_date->get(), null, CalendarUtil::ONE_DAY_HEADER);
			break;
			
			case "month":
				return CalendarUtil::localize($this->start_date->get(), null, CalendarUtil::MONTH_HEADER);
			break;
			
			case "year":
				return CalendarUtil::localize($this->start_date->get(), null, CalendarUtil::YEAR_HEADER);
			break;
			
			case "range":
				list($strStartDate,$strEndDate) = CalendarUtil::getDateString($this->start_date->date(),$this->end_date->date());
				return $strStartDate.$strEndDate;
			break;
			
			default: 
				return $this->DefaultDateHeader;
			break;
		}
	 }
	public function Events($filter = null, $announcement_filter = null)
	{
		if(list($db_clauses,$event_filters,$datetime_filters) = Calendar::getFiltersForDB()) {
			$filter = (sizeof($db_clauses > 1)) ? implode(" AND ", $db_clauses) : $db_clauses;
      if(!empty($datetime_filters))
        $announcement_filter = sizeof($datetime_filters) > 1 ? implode(" AND ", $datetime_filters) : $datetime_filters;
		}
		return $this->data()->Events($filter, $this->start_date, $this->end_date, ($this->view == "default"), null, $announcement_filter);
	}
		
	public function CalendarWidget()
	{
		return new CalendarWidget($this, $this->start_date, $this->end_date, ($this->view == "default"));
	}
	
	public function MonthNavigator()
	{
		return new MonthNavigator($this, $this->start_date, $this->end_date);
	}

	public function LiveCalendarWidget()
	{
		return new LiveCalendarWidget($this, $this->start_date, $this->end_date, ($this->view == "default"));
	}
	

	public function CalendarFilterForm()
	{
		$start_date = $this->start_date;
		if($this->end_date === null || !$this->end_date instanceof sfDate || $this->view == "default")
			$end_date = $start_date;
		else
			$end_date = $this->end_date;

		$form = new Form(
			$this,
			'CalendarFilterForm',
			$this->data()->getFilterFields(),
			new FieldSet(
				new FormAction('doCalendarFilter',_t('Calendar.FILTER','Filter'))
			)
		);

		$form_data = array (
			'StartMonth' => $start_date->format('m'),
			'StartDay' => $start_date->format('d'),
			'StartYear' => $start_date->format('Y'),
			'EndMonth' => $end_date->format('m'),
			'EndDay' => $end_date->format('d'),
			'EndYear' => $end_date->format('Y')
		);

		if($filters = Calendar::getCleanFilters()) {
			foreach($filters as $key => $value) {
				$form_data["filter[".$key."]"] = $value;
			}
		}
		$form->loadDataFrom($form_data);
		$form->unsetValidator();
		
		return $form;

	}
}