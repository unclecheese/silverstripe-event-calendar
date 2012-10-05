<?php

class Calendar extends Page {
	

	static $db = array(
		'DefaultDateHeader' => 'Varchar(50)',
		'OtherDatesCount' => 'Int',
		'RSSTitle' => 'Varchar(255)',
		'DefaultFutureMonths' => 'Int',
		'EventsPerPage' => 'Int',
		'DefaultView' => "Enum('today,week,,month,weekend,upcoming','upcoming')"
	);


	
	static $has_many = array (
		'Announcements' => 'CalendarAnnouncement',
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
		'OtherDatesCount' => '3',
		'DefaultFutureMonths' => '6',
		'EventsPerPage' => '10',
		'DefaultView' => 'upcoming'
	);
	
	

	static $reccurring_event_index = 0;


	static $icon = "event_calendar/images/calendar";


	static $description = "A collection of Calendar Events";


	static $event_class = "CalendarEvent";


	static $announcement_class = "CalendarAnnouncement";


	static $timezone = "America/New_York";


	static $language = "EN";


	static $jquery_included = false;



	protected $eventClass_cache, 
			  $announcementClass_cache, 
			  $datetimeClass_cache, 
			  $dateToEventRelation_cache,
			  $announcementToCalendarRelation_cache;



	public static function set_jquery_included($bool = true) {
		self::$jquery_included = $bool;
	}


	
	public function getCMSFields()
	{

		Requirements::javascript('event_calendar/javascript/calendar_cms.js');

		$f = parent::getCMSFields();
		$configuration = _t('Calendar.CONFIGURATION','Configuration');
		$f->addFieldsToTab("Root.$configuration", array(
			new DropdownField('DefaultView',_t('Calendar.DEFAULTVIEW','Default view'), array (
				'upcoming' => _t('Calendar.UPCOMINGVIEW',"Show a list of upcoming events."),
				'month' => _t('Calendar.MONTHVIEW',"Show this month's events."),				
				'week' => _t('Calendar.WEEKVIEW',"Show this week's events. If none, fall back on this month's"),
				'today' => _t('Calendar.TODAYVIEW',"Show today's events. If none, fall back on this week's events"),	
				'weekend' => _t('Calendar.WEEKENDVIEW',"Show this weekend's events.")

			)),
			new NumericField('DefaultFutureMonths', _t('Calendar.DEFAULTFUTUREMONTHS','Number maximum number of future months to show in default view')),
			new NumericField('EventsPerPage', _t('Calendar.EVENTSPERPAGE','Events per page')),
			new TextField('DefaultDateHeader', _t('Calendar.DEFAULTDATEHEADER','Default date header (displays when no date range has been selected)')),
			new NumericField('OtherDatesCount', _t('Calendar.NUMBERFUTUREDATES','Number of future dates to show for repeating events'))			
		));
		
		$announcements = _t('Calendar.Announcements','Announcements');
		$f->addFieldToTab("Root.$announcements", GridField::create(
				"Announcements",
				_t('Calendar.ANNOUNCEMENTDESCRIPTION','Announcements are simple entries you can add to your calendar that do not have detail pages, e.g. "Office closed"'),
				$this->Announcements(),
				GridFieldConfig_RecordEditor::create()				
			));
		
		$feeds = _t('Calendar.FEEDS','Feeds');
		$f->addFieldToTab("Root.$feeds", GridField::create(
				"Feeds",
				_t('Calendar.ICSFEEDDESCRIPTION','Add ICS feeds to your calendar to include events from external sources, e.g. a Google Calendar'),
				$this->Feeds(),
				GridFieldConfig_RecordEditor::create()				
			));

		$otherCals = Calendar::get()->exclude(array("ID" => $this->ID));
		if($otherCals->exists()) {				
			$f->addFieldToTab("Root.$feeds", new CheckboxSetField(
				'NestedCalendars', 
				_t('Calendar.NESTEDCALENDARS','Include events from these calendars'),
				$otherCals->map('ID', 'Link')
			));
		}
		
		$f->addFieldToTab("Root.Main", new TextField('RSSTitle', _t('Calendar.RSSTITLE','Title of RSS Feed')),'Content');
		$this->extend('updateCMSFields',$f);
		return $f;
	}


	public function getEventClass() {
		if($this->eventClass_cache) return $this->eventClass_cache;
		$this->eventClass_cache = $this->stat('event_class');
		return $this->eventClass_cache;
	}



	public function getAnnouncementClass() {
		if($this->announcementClass_cache) return $this->announcementClass_cache;
		$this->announcementClass_cache = $this->stat('announcement_class');
		return $this->announcementClass_cache;
	}



	public function getDateTimeClass() {
		if($this->datetimeClass_cache) return $this->datetimeClass_cache;
		$this->datetimeClass_cache = singleton($this->getEventClass())->stat('datetime_class');
		return $this->datetimeClass_cache;		
	}



	public function getDateToEventRelation() {
		if($this->dateToEventRelation_cache) return $this->dateToEventRelation_cache;
		$this->dateToEventRelation_cache = singleton($this->getDateTimeClass())->getReverseAssociation($this->getEventClass())."ID";
		return $this->dateToEventRelation_cache;
	}



	public function getEventList($start, $end, $filter = null, $limit = null, $announcement_filter = null) {		
		$eventList = new ArrayList();
		foreach($this->getAllCalendars() as $calendar) {
			if($events = $calendar->getStandardEvents($start, $end, $filter)) {
				$eventList->merge($events);
			}					
			$announcements = DataList::create($this->getAnnouncementClass())
				->filter('CalendarID', $calendar->ID)
				->where("
					(StartDate <= '$start' AND EndDate >= '$end') OR
				    (StartDate BETWEEN '$start' AND '$end') OR
				    (EndDate BETWEEN '$start' AND '$end')
				");
			if($filter) {
				$announcements = $announcements->where($announcement_filter);
			}

			if($announcements) {
				foreach($announcements as $announcement) {
					$eventList->push($announcement);
				}

			}	

			if($recurring = $calendar->getRecurringEvents($filter)) {
				$eventList = $calendar->addRecurringEvents($start, $end, $recurring, $eventList);
			}

		}


		$eventList = $eventList->sort(array("StartDate" => "ASC", "StartTime" => "ASC"));						
		$eventList = $eventList->limit($limit);

		return $eventList;
	}




	protected function getStandardEvents($start, $end, $filter = null) {
		if(!$children = $this->AllChildren()) return false;
		$ids = $children->column('ID');
		$datetime_class = $this->getDateTimeClass();
		$relation = $this->getDateToEventRelation();		
		$event_class = $this->getEventClass();
		$list = DataList::create($datetime_class);
		$list = $list->where("Recursion != 1");
		$list = $list->where("
				(StartDate <= '$start' AND EndDate >= '$end') OR
				(StartDate BETWEEN '$start' AND '$end') OR
				(EndDate BETWEEN '$start' AND '$end')
		");
		$list = $list->filter(array($relation => $ids));				
		$list = $list->innerJoin($event_class, "$relation = \"{$event_class}\".ID");
		return $list;
	}




	protected function getRecurringEvents($filter = null) {
		$event_class = $this->getEventClass();
		$datetime_class = $this->getDateTimeClass();
		$SNG = singleton($datetime_class);
		if($relation = $this->getDateToEventRelation()) {
			$events = DataList::create($event_class)
				->filter("Recursion", "1")
				->filter("ParentID", $this->ID)
				->innerJoin($datetime_class, "\"{$datetime_class}\".{$relation} = \"SiteTree\".ID");			
			if($filter) {
				$events = $events->where($filter);
			}
			return $events;
		}
		return false;
	}




	public function getNextRecurringEvents($event_obj, $datetime_obj, $limit = null) {
		$counter = sfDate::getInstance($datetime_obj->StartDate);
		if($event = $datetime_obj->Event()->DateTimes()->First()) {
			$end_date = strtotime($event->EndDate);
		}
		else {
			$end_date = false;
		}
		$counter->tomorrow();
		$dates = new ArrayList();
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




	protected function addRecurringEvents($start_date, $end_date, $recurring_events,$all_events) {		
		$date_counter = sfDate::getInstance($start_date);
		$end = sfDate::getInstance($end_date);
		foreach($recurring_events as $recurring_event) {			
			$reader = $recurring_event->getRecursionReader();
			$relation = $recurring_event->getReverseAssociation($this->getDateTimeClass());
			if(!$relation) continue;

			if($recurring_event_datetime = $recurring_event->$relation()->first()) {				
				while($date_counter->get() <= $end->get()){
					// check the end date
					if($recurring_event_datetime->EndDate) {
						$end_stamp = strtotime($recurring_event_datetime->EndDate);
						if($end_stamp > 0 && $end_stamp <= $date_counter->get()) {							
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


	public function newRecursionDateTime($recurring_event_datetime, $start_date) {
		$c = $this->getDateTimeClass();
		$relation = $this->getDateToEventRelation();		
		$e = new $c();
		foreach($recurring_event_datetime->db() as $field => $type) {
			$e->$field = $recurring_event_datetime->$field;
		}
		$e->StartDate = $start_date;
		$e->EndDate = $start_date;
		$e->$relation = $recurring_event_datetime->$relation;
		$e->ID = "recurring" . self::$reccurring_event_index;
		self::$reccurring_event_index++;
		return $e;
	}


	public function getAllCalendars() {
		$calendars = new ArrayList();
		$calendars->push($this);
		$calendars->merge($this->NestedCalendars());
		return $calendars;
	}




	public function UpcomingEvents($limit = 5, $filter = null)  {				
		$all = $this->getEventList(
			sfDate::getInstance()->date(),
			sfDate::getInstance()->addMonth($this->DefaultFutureMonths)->date(),
			$filter,
			$limit
		);
		return $all->limit($limit);			
	}



	public function RecentEvents($limit = null, $filter = null)  {
		$start_date = sfDate::getInstance();
		$end_date = sfDate::getInstance();
		$l = ($limit === null) ? "9999" : $limit;
		$events = $this->getEventList(
			$start_date->subtractMonth($this->DefaultFutureMonths)->date(),
			$end_date->yesterday()->date(), 
			$filter,
			$l
		);
		$events->sort('StartDate','DESC');
		return $events->getRange(0,$limit);
	}



	 public function CalendarWidget() {
	 	$calendar = new CalendarWidget($this);
	 	$controller = Controller::curr();
	 	if($controller->class == "Calendar_Controller" || is_subclass_of($controller, "Calendar_Controller")) {
	 		if($controller->getView() != "default") {	 			
	 			$calendar->setOption('start', $controller->getStartDate()->format('Y-m-d'));
	 			$calendar->setOption('end', $controller->getEndDate()->format('Y-m-d'));
	 		}
	 	}
	 	return $calendar;
	 }


	 public function MonthJumpForm() {
	 	$controller = Controller::curr();
	 	if($controller->class == "Calendar_Controller" || is_subclass_of($controller, "Calendar_Controller")) {
	 		return Controller::curr()->MonthJumpForm();
	 	}
	 	$c = new Calendar_Controller($this);
	 	return $c->MonthJumpForm();
	 }


}



class Calendar_Controller extends Page_Controller {
	

	static $allowed_actions = array (
		'show',
		'month',
		'year',
		'rss',
		'today',
		'week',
		'weekend',
		'ical',
		'ics',
		'monthjson',
		'MonthJumpForm'
	);



	protected $view;


	protected $startDate;


	protected $endDate;



	public function init() {
		parent::init();
		RSSFeed::linkToFeed($this->Link() . "rss", $this->RSSTitle ? $this->RSSTitle : $this->Title);
		Requirements::themedCSS('calendar','event_calendar');
		if(!Calendar::$jquery_included) {
			Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.js');
		}
		Requirements::javascript('event_calendar/javascript/calendar.js');
	}



	public function getStartDate() {
		return $this->startDate;
	}



	public function getEndDate() {
		return $this->endDate;
	}



	public function getView() {
		return $this->view;
	}



	public function setDefaultView() {
		$this->view = "default";
		$this->startDate = sfDate::getInstance();
		$this->endDate = sfDate::getInstance()->addMonth(6);				
	}



	public function setTodayView() {
		$this->view = "day";
		$this->startDate = sfDate::getInstance();
		$this->endDate = sfDate::getInstance();
	}



	public function setWeekView() {
		$this->view = "week";
		$this->startDate = sfDate::getInstance()->firstDayOfWeek();
		$this->endDate = sfDate::getInstance()->finalDayOfWeek();
		if(CalendarUtil::get_first_day_of_week() == sfTime::MONDAY) {
			$this->startDate->tomorrow();
			$this->endDate->tomorrow();
		}
	}



	public function setWeekendView() {
 		$this->view = "weekend";
		$start = sfDate::getInstance();
		if($start->format('w') == sfTime::SATURDAY) {
			$start->yesterday();
		}
		elseif($start->format('w') != sfTime::FRIDAY) {
			$start->nextDay(sfTime::FRIDAY);
		}
		$this->startDate = $start;
		$this->endDate = sfDate::getInstance($start)->nextDay(sfTime::SUNDAY);
	}



	public function setMonthView() {
		$this->view = "month";
		$this->startDate = sfDate::getInstance()->firstDayOfMonth();
		$this->endDate = sfDate::getInstance()->finalDayOfMonth();
	}



	public function getOffset() {
		if(!isset($_REQUEST['start'])) {
			$_REQUEST['start'] = 0;
		}
		return $_REQUEST['start'];
	}



	protected function getRangeLink(sfDate $start, sfDate $end) {
		return Controller::join_links($this->Link(), "show", $start->format('Ymd'), $end->format('Ymd'));
	}



	public function respond() {
		if(Director::is_ajax()) {
			return $this->renderWith('EventList');
		}
		return array();
	}


	public function index(SS_HTTPRequest $r) {
		switch($this->DefaultView) {
			case "month":
				return $this->redirect($this->Link('show/month'));
			break;

			case "week":
				$this->setWeekView();
				// prevent pagination on these default views
				$this->EventsPerPage = 999;
				$e = $this->Events();
				if($e->count() > 0) {
					return array('Events' => $e);
				}
				else {
					$this->setMonthView();
					return array();
				}
			break;

			case "today":
				// prevent pagination on these default views
				$this->EventsPerPage = 999;
				$this->setTodayView();
				$e = $this->Events();
				if($e->count() > 0) {
					return array('Events' => $e);
				}
				else {
					$this->setWeekView();
					return array();
				}
			break;

			default:
				$this->setDefaultView();
				$list = $this->Events();
				return $this->respond();
			break;


		}
	}



	public function today(SS_HTTPRequest $r) {		
		$this->setTodayView();
		return $this->respond();
	}



	public function week(SS_HTTPRequest $r) {
		$this->setWeekView();
		return $this->respond();
	}



	public function weekend(SS_HTTPRequest $r) {
		$this->setWeekendView();
		return $this->respond();
	}




	public function month(SS_HTTPReqest $r) {
		$this->setMonthView();
		return $this->respond();
	}



	public function show(SS_HTTPRequest $r) {
		$this->parseURL($r);
		return $this->respond();
	}



	public function rss() {
		$this->setDefaultView();
		$events = $this->Events();
		foreach($events as $event) {
			$event->Title = strip_tags($event->DateRange()) . " : " . $event->getTitle();
			$event->Description = $event->getContent();
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



	public function monthjson(SS_HTTPRequest $r) {
		if(!$r->param('ID')) return false;
		$this->startDate = sfDate::getInstance(CalendarUtil::get_date_from_string($r->param('ID')));
		$this->endDate = sfDate::getInstance($this->startDate)->finalDayOfMonth();
		
		$json = array ();
		$counter = clone $this->startDate;		
		while($counter->get() <= $this->endDate->get()) {
			$d = $counter->format('Y-m-d');
			$json[$d] = array (
				'events' => array ()
			);
			$counter->tomorrow();
		}		
		$list = $this->Events();
		foreach($list as $e) {
			foreach($e->getAllDatesInRange() as $date) {
				if(isset($json[$date])) {
					$json[$date]['events'][] = $e->getTitle();
				}
			}
		}
		return Convert::array2json($json);
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



	public function ics(SS_HTTPRequest $r) {		
		$feed = false;
		$announcement = false;
		$id = $r->param('ID');
		$oid = $r->param('OtherID');

		if(stristr($id, "feed") !== false) {
			$id = str_replace("feed","",$id);
			$feed = true;		
		}
		else if(stristr($id, "announcement-") !== false) {
			$id = str_replace("announcement-","",$id);
			$announcement = true;
		}
		else {			
			$announcement = false;
		}
		if(is_numeric($id) && $oid) {
			if(!$feed) { 
				$event = DataObject::get_by_id($announcement ? $this->data()->getDateTimeClass() : $this->data()->getEventClass(), $id);
				$FILENAME = $announcement ? preg_replace("/[^a-zA-Z0-9s]/", "", $event->Title) : $event->URLSegment;
			}
			else {
				$FILENAME = preg_replace("/[^a-zA-Z0-9s]/", "", urldecode($_REQUEST['title']));
			}

			$FILENAME .= ".ics";
			$HOST = $_SERVER['HTTP_HOST'];
			$TIMEZONE = Calendar::$timezone;
			$LANGUAGE = Calendar::$language;
			$CALSCALE = "GREGORIAN";
			$parts = explode('-',$oid);
			$START_TIMESTAMP = $parts[0];
			$END_TIMESTAMP = $parts[1];
			if(!$feed) {
				$URL = $announcement ? $event->Calendar()->AbsoluteLink() : $event->AbsoluteLink();
			}
			else {
				$URL = "";
			}
			$TITLE = $feed ? $_REQUEST['title'] : $event->Title;
			header("Cache-Control: private");
			header("Content-Description: File Transfer");
			header("Content-Type: text/calendar");
			header("Content-Transfer-Encoding: binary");
	  		if(stristr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
 				header("Content-disposition: filename=".$FILENAME."; attachment;");
 			}
 			else {
 				header("Content-disposition: attachment; filename=".$FILENAME);
 			}
			$result = trim(strip_tags($this->customise(array(
				'HOST' => $HOST,
				'LANGUAGE' => $LANGUAGE,
				'TIMEZONE' => $TIMEZONE,
				'CALSCALE' => $CALSCALE,
				'START_TIMESTAMP' => $START_TIMESTAMP,
				'END_TIMESTAMP' => $END_TIMESTAMP,
				'URL' => $URL,
				'TITLE' => $TITLE
			))->renderWith(array('ics'))));
			return $result;
		}
		else {
			$this->redirectBack();
		}
	}





	public function parseURL(SS_HTTPRequest $r) {
		if(!$r->param('ID')) return;
		$this->startDate = sfDate::getInstance(CalendarUtil::get_date_from_string($r->param('ID')));
		if($r->param('OtherID')) {
			$this->view = "range";
			$this->endDate = sfDate::getInstance(CalendarUtil::get_date_from_string($r->param('OtherID')));			
		}
		else {
			$d = clone $this->startDate;
			switch(strlen(str_replace("-","",$r->param('ID')))) {				
				case 8:
				$this->view = "day";
				$this->endDate = sfDate::getInstance($d->get()+1);
				break;
				
				case 6:
				$this->view = "month";								
				$this->endDate = sfDate::getInstance($d->finalDayOfMonth()->date());
				break;
				
				case 4:
				$this->view = "year";
				$this->endDate = sfDate::getInstance($d->finalDayOfYear()->date());
				break;
				
				default:
				$this->view = "default";
				$this->endDate = sfDate::getInstance($d->addMonth($this->DefaultFutureMonths)->date());
				break;
			}
		}
	}



	public function Events() {
		$event_filter = null;
		$announcement_filter = null;
		if($search = $this->getRequest()->getVar('s')) {
			$s = Convert::raw2sql($search);
			$event_filter = "\"SiteTree\".Title LIKE '%$s%' OR \"SiteTree\".Content LIKE '%$s%'";
			$announcement_filter = "\"CalendarAnnouncement\".Title LIKE '%$s%' OR \"CalendarAnnouncement\".Content LIKE '%$s%'";
			$this->SearchQuery = $search;
		}
		$all = $this->data()->getEventList(
			$this->startDate->date(),
			$this->endDate->date(),
			$event_filter,
			null,
			$announcement_filter
		);		
		$list = $all->limit($this->EventsPerPage, $this->getOffset());		
		$next = $this->getOffset()+$this->EventsPerPage;
		$this->MoreEvents = ($next < $all->count());
		$this->MoreLink = HTTP::setGetVar("start", $next);
		return $list;
	}



	public function DateHeader() {
		switch($this->view) {
			case "day":
				return CalendarUtil::localize($this->startDate->get(), null, CalendarUtil::ONE_DAY_HEADER);
			break;
			
			case "month":
				return CalendarUtil::localize($this->startDate->get(), null, CalendarUtil::MONTH_HEADER);
			break;
			
			case "year":
				return CalendarUtil::localize($this->startDate->get(), null, CalendarUtil::YEAR_HEADER);
			break;

			
			case "range":
			case "week":
			case "weekend":
				list($strStartDate,$strEndDate) = CalendarUtil::get_date_string($this->startDate->date(),$this->endDate->date());
				return $strStartDate.$strEndDate;
			break;
			
			default: 
				return $this->DefaultDateHeader;
			break;
		}
	 }


	public function CurrentAction($a) {
	 	return $this->getAction() == $a;
	}


	public function PreviousDayLink() {
	 	$s = sfDate::getInstance($this->startDate)->yesterday();
	 	return $this->getRangeLink($s, $s);
	}



	public function NextDayLink() {
	 	$s = sfDate::getInstance($this->startDate)->tomorrow();
	 	return $this->getRangeLink($s, $s);
	}



	public function PreviousWeekLink() {
	 	$s = sfDate::getInstance($this->startDate)->subtractWeek();
	 	$e = sfDate::getInstance($this->endDate)->subtractWeek();
	 	return $this->getRangeLink($s, $e);
	}



	public function NextWeekLink() {
	 	$s = sfDate::getInstance($this->startDate)->addWeek();
	 	$e = sfDate::getInstance($this->endDate)->addWeek();
	 	return $this->getRangeLink($s, $e);
	}



	public function NextMonthLink() {
	 	$s = sfDate::getInstance($this->startDate)->addMonth();
	 	$e = sfDate::getInstance($s)->finalDayOfMonth();
	 	return $this->getRangeLink($s, $e);
	}



	public function PreviousMonthLink() {
	 	$s = sfDate::getInstance($this->startDate)->subtractMonth();
	 	$e = sfDate::getInstance($s)->finalDayOfMonth();
	 	return $this->getRangeLink($s, $e);
	}


	public function NextWeekendLink() {
	 	return $this->NextWeekLink();
	}


	public function PreviousWeekendLink() {
	 	return $this->PreviousWeekLink();
	}


	public function IsSegment($segment) {
	 	switch($segment) {
	 		case "today":
	 			return $this->startDate->date() == $this->endDate->date();
	 		case "week":
	 			if(CalendarUtil::get_first_day_of_week() == sfTime::MONDAY) {
	 				return ($this->startDate->format('w') == sfTime::MONDAY) && ($this->startDate->format('w') == sfTime::SUNDAY);
	 			}	 			
	 			return ($this->startDate->format('w') == sfTime::SUNDAY) && ($this->endDate->format('w') == sfTime::SATURDAY);

	 		case "month":
	 			return ($this->startDate->format('j') == 1) && (sfDate::getInstance($this->startDate)->finalDayOfMonth()->format('j') == $this->endDate->format('j'));
	 		case "weekend":
	 			return ($this->startDate->format('w') == sfTime::FRIDAY) && ($this->endDate->format('w') == sfTime::SUNDAY);
	 	}
	}


	public function MonthJumper() {
		return $this->renderWith('MonthJumper');
	}
	


	public function MonthJumpForm() {
		$this->parseURL($this->getRequest());
		$dummy = sfDate::getInstance($this->startDate);
		$range = range(($dummy->subtractYear(3)->format('Y')), ($dummy->addYear(6)->format('Y')));
		$year_map = array_combine($range, $range);
		$f = new Form(
			$this,

			"MonthJumpForm",
			new FieldList (
				$m = new DropdownField('Month','', CalendarUtil::get_months_map('%B')),
				$y = new DropdownField('Year','', $year_map)
			),
			new FieldList (
				new FormAction('doMonthJump', _t('Calendar.JUMP','Go'))
			)
		);

		if($this->startDate) {
			$m->setValue($this->startDate->format('m'));
			$y->setValue($this->startDate->format('Y'));
		}
		else {
			$m->setValue(date('m'));
			$y->setValue(date('Y'));
		}
		return $f;	
	}
	
	public function doMonthJump($data, $form) {
		return $this->redirect($this->Link('show').'/'.$data['Year'].$data['Month']);
	}


}
