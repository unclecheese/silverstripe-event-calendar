<?php

class CalendarDateTime extends DataObject
{
	static $db = array (
		'Title' => 'Text',
		'StartDate' => 'Date',
		'StartTime' => 'Time',
		'EndDate' => 'Date',
		'EndTime' => 'Time',
		'Content' => 'HTMLText',
		'is_all_day' => 'Boolean',
		'is_single' => 'Boolean'
	);
	
	static $has_one = array (
		'Event' => 'CalendarEvent',
		'Calendar' => 'Calendar'
	);
	
	
	static $offset;
	static $date_delimiter;
	static $date_format = "mdy";
	static $time_format = "24";
	static $dayofweek_format_character;
	static $dayofweek_header_format_character;
	static $month_format_character = "%b";
	static $day_format_character = "%e";
	static $year_format_character = "%Y";
	static $month_header_format_character = "%B";
	static $day_header_format_character = "%e";
	static $year_header_format_character = "%Y";
			
	protected $event_class = null;
	protected $event_object = null;
	
	// Will take a DOSet if there are multiple times associated with the event.
	public $Times;
	public $Feed = false;
	
	public static function set_param($param, $value) 
	{
		self::$$param = $value;	
	}
	
	public static function get_param($param)
	{
		return self::$$param;
	}

	public static function dmy()
	{
		return self::get_param('date_format') == "dmy";
	}
		
	public static function mdy()
	{
		return self::get_param('date_format') == "mdy";
	}
		
	public static function set_date_format($f)
	{		
		if(!in_array($f, array('dmy','mdy')))
			die("<strong>CalendarDateTime::set_date_format():</strong>"._t('CalendarDateTime.INVALIDFORMAT','Invalid date format. Must be either "dmy" or "mdy"'));
		self::set_param('date_format',$f);
	}
	
	public static function set_time_format($f)
	{
	   if(!in_array($f, array('24','12')))
	     die("<strong>CalendarDateTime::set_time_format():</strong>"._t('CalendarDateTime.INVALIDTIME','Invalid time format. Must be either "24" or "12"'));
	   self::set_param('time_format',$f);
	}

	public function getEventClass()
	{
		if($this->event_class !== null)
			return $this->event_class;
			
		$class = get_class($this);
		$has_ones = Object::combined_static($class, 'has_one');
		if(is_array($has_ones)) {
			foreach($has_ones as $c) {
				if($c == 'CalendarEvent' || is_subclass_of($c, 'CalendarEvent')) {
					$this->event_class = $c;
					return $this->event_class;
				}
			}
		}
	}
	
	public function getEventObject()
	{
		if($this->event_object !== null)
			return $this->event_object;
			
		$class = $this->getEventClass();
		$this->event_object = new $class;
		return $this->event_object;
	}
	
	public function getAnnouncementPopup()
	{
		$f = new FieldSet();
		$f->push(new TextField('Title',_t('CalendarDateTime.TITLE','Title')));
		$f->push(new DatePickerField('StartDate',_t('CalendarDateTime.STARTDATE','Start Date')));
		$f->push(new TimeField('StartTime',_t('CalendarDateTime.STARTTIME','Start Time')));
		$f->push(new DatePickerField('EndDate',_t('CalendarDateTime.ENDDATE','EndDate')));
		$f->push(new TimeField('EndTime',_t('CalendarDateTime.ENDTIME','End Time')));
		$f->push(new CheckboxField('is_all_day',_t('CalendarDateTime.ALLDAY','All Day')));
		$f->push(new TextareaField('Content',_t('CalendarDateTime.CONTENT','Content')));
		
		return $f;
	}
	
	public function getDateTimePopup()
	{
		$f = new FieldSet();
		if(isset($this->table_fields['StartDate'])) $f->push(new DatePickerField('StartDate',_t('CalendarDateTime.STARTDATE','Start Date')));
		if(isset($this->table_fields['StartTime'])) $f->push(new TimeField('StartTime',_t('CalendarDateTime.STARTTIME','Start Time')));
		if(isset($this->table_fields['EndDate'])) $f->push(new DatePickerField('EndDate',_t('CalendarDateTime.ENDDATE','EndDate')));
		if(isset($this->table_fields['EndTime'])) $f->push(new TimeField('EndTime',_t('CalendarDateTime.ENDTIME','End Time')));
		if(isset($this->table_fields['is_all_day'])) $f->push(new CheckboxField('is_all_day',_t('CalendarDateTime.ALLDAY','All Day')));
		return $f;
	}

	protected $table_fields = array(
		'StartDate' => 'DatePickerField',
		'EndDate' => 'DatePickerField',
		'StartTime' => 'TimeField',
		'EndTime' => 'TimeField',
		'is_all_day' => 'CheckboxField'	
	);
	
	protected $table_titles = array (
		'FormattedStartDate' => 'Start Date',
		'FormattedEndDate' => 'End Date',
		'FormattedStartTime' => 'Start Time', 
		'FormattedEndTime' => 'End Time',
		'FormattedAllDay' => 'All day'
	);
	
	protected $announcement_titles = array(
		'Title' => 'Title', 
		'FormattedStartDate' => 'Start Date', 
		'FormattedStartTime' => 'Start Time', 
		'EndTime' => 'End Time'
	);

	
	// This gets populated with objects, which is not allowed by default.
	// We'll use initPopupFields() to saturate this when needed.
	protected $popup_table_fields = null;
	
	protected $announcement_table_fields = null;
	
	protected $complex = true;

	// Meant to be overloaded by subclasses.
	function extendTable() {return;}	
	function extendAnnouncement() {return;}
	
	public function getDateTimeTable($eventID)
	{
		$this->initPopupFields();
		$this->extendTable();
		$name = 'DateTimes';
		$titles = $this->getTableTitles();
		$filter = "`CalendarDateTime`.EventID = {$eventID}";
		$fields = $this->getPopupFields();			
		$fields->push(new HiddenField('EventID','',$eventID));
		$table = new DataObjectManager($this->getEventObject(), $name, get_class($this), $titles, $fields, $filter);
		$table->setAddTitle(_t("CalendarDateTime.ADATE","a Date"));

		return $table;
	}
	
	public function getAnnouncementTable($calendarID)
	{
		$this->extendAnnouncement();
		$table = new DataObjectManager(
			$this->getEventObject()->Parent(),
			'Announcements',
			$this->class,
			$this->getAnnouncementTitles(),
			$this->getAnnouncementFields()
		);
		$table->setAddTitle("Announcement");
		$table->setParentClass("Calendar");		

		return $table;
	
	}
	
	public function addTableField($name, $type) 
	{
		$this->table_fields[$name] = $type;
	}
	
	protected function initPopupFields() 
	{
		$this->popup_table_fields = array(
			new DatePickerField('StartDate',_t('CalendarEvent.STARTDATE','Start Date')),
			new DatePickerField('EndDate', _t('CalendarEvent.ENDDATE','End Date')),
			new TimeField('StartTime',_t('CalendarEvent.STARTTIME','Start Time')),
			new TimeField('EndTime', _t('CalendarEvent.ENDTIME','End Time (blank if none)')),
			new CheckboxField('is_all_day',_t('CalendarEvent.ALLDAY','All Day'))
		);
	}

	private function initAnnouncementFields() 
	{
		$this->announcement_table_fields = array(
			new TextField('Title'),
			new DatePickerField('StartDate',_t('CalendarEvent.STARTDATE','Start Date')),
			new TimeField('StartTime',_t('CalendarEvent.STARTTIME','Start Time')),
			new DatePickerField('EndDate',_t('CalendarEvent.ENDDATE','EndDate')),
			new TimeField('EndTime',_t('CalendarEvent.ENDTIME','End Time')),
			new CheckboxField('is_all_day',_t('CalendarEvent.ALLDAY','All Day')),
			new TextareaField('Content',_t('CalendarEvent.CONTENT','Content')),
		);
	}

	
	protected function getPopupFields() 
	{
		if($this->popup_table_fields === null) $this->initPopupFields();
		$fields = new FieldSet();
		foreach($this->popup_table_fields as $field)
			$fields->push($field);
		$customized = true;
		$this->extend('updateCMSFields', $fields, $customized);
		return $fields;
	}

	protected function getAnnouncementFields() 
	{
		if($this->announcement_table_fields === null) $this->initAnnouncementFields();
		$this->extendAnnouncement();	
		$fields = new FieldSet();
		foreach($this->announcement_table_fields as $field) {
			$fields->push($field);
		}
		$customized = true;
		$this->extend('updateCMSFields', $fields, $customized);
		return $fields;
	}

	
	public function addPopupField($field)
	{
		if($this->popup_table_fields === null) $this->initPopupFields();
		if(!$this->isComplex()) $this->setComplex(true);
		$this->popup_table_fields[] = $field;
	}

	public function addAnnouncementField($field)
	{
		if($this->announcement_table_fields === null) $this->initAnnouncementFields();
		if(!$this->isComplex()) $this->setComplex(true);
		$this->announcement_table_fields[] = $field;
	}
	
	public function removePopupField($name)
	{
		for($i = 0; $i < sizeof($this->popup_table_fields); $i++) {
			$fieldObj = $this->popup_table_fields[$i];
			if($fieldObj instanceof FormField) {
				if($fieldObj->Name() == $name) {
					unset($this->popup_table_fields[$i]);
					break;
				}
			}
		}
	}

	public function removeAnnouncementField($name)
	{
		for($i = 0; $i < sizeof($this->announcement_table_fields); $i++) {
			$fieldObj = $this->announcement_table_fields[$i];
			if($fieldObj instanceof Field) {
				if($fieldObj->Name == $name) {
					unset($this->announcement_table_fields[$i]);
					break;
				}
			}
		}
	}
	
	public function addTableFields($fields)
	{
		if(is_array($fields)) {	
				foreach($fields as $k => $v)
					$this->addTableField($k,$v);
		}
	}
	
	public function addPopupFields($fields)
	{
		if(is_array($fields)) {
			foreach($fields as $field)
				$this->addPopupField($field);
		}
	}

	public function addAnnouncementFields($fields)
	{
		if(is_array($fields)) {
			foreach($fields as $field)
				$this->addAnnouncementField($field);
		}
	}
		
	public function addTableTitle($name, $title) 
	{
		$this->table_titles[$name] = $title;
	}

	public function addAnnouncementTitle($name, $title) 
	{
		$this->announcement_titles[$name] = $title;
	}

	public function addAnnouncementTitles($titles)
	{
		if(is_array($titles)) {
			foreach($titles as $k => $v)
				$this->addAnnouncementTitle($k,$v);
		}
	}
	
	
	
	public function addTableTitles($titles)
	{
		if(is_array($titles)) {
			foreach($titles as $k => $v)
				$this->addTableTitle($k,$v);
		}
	}

	public function getFormattedStartDate()
	{
	   if(!$this->StartDate) return "<em>none</em>";
	   return self::mdy() ? $this->obj('StartDate')->Format('m-d-Y') : $this->obj('StartDate')->Format('d-m-Y');
	}
	
	public function getFormattedEndDate()
	{
	   if(!$this->EndDate) return "<em>none</em>";
	   return self::mdy() ? $this->obj('EndDate')->Format('m-d-Y') : $this->obj('EndDate')->Format('d-m-Y');
	}
		
	public function getFormattedStartTime()
	{
	   if(!$this->StartTime) return "<em>none</em>";
	   return self::get_param('time_format') == "12" ? $this->obj('StartTime')->Nice() : $this->obj('StartTime')->Nice24();
	}
	
	public function getFormattedEndTime()
	{
	   if(!$this->EndTime) return "<em>none</em>";
	   return self::get_param('time_format') == "12" ? $this->obj('EndTime')->Nice() : $this->obj('EndTime')->Nice24();
	}
	
	public function getFormattedAllDay()
	{
	   return $this->is_all_day == 1 ? _t('YES','Yes') : _t('NO','No');
	}
	
	
	
	public function removeTableField($name)
	{
		unset($this->table_fields[$name]);
	}

	public function removeTableTitle($name)
	{
		unset($this->table_titles[$name]);
	}
	
	public function removeAnnouncementTitle($name)
	{
		unset($this->announcement_titles[$name]);
	}
	

	protected function setComplex($bool)
	{
		$this->complex = $bool;
	}
	
	protected function isComplex()
	{
		return $this->complex;
	}
	
	public function getTableFields()
	{
		return $this->table_fields;
	}
	
	public function getTableTitles()
	{
		return $this->table_titles;
	}

	public function getAnnouncementTitles()
	{
		return $this->announcement_titles;
	}

		
	public function Announcement()
	{
		return $this->CalendarID > 0;
	}
	
	public function MultipleTimes()
	{
		return isset($this->Times) && $this->Times->Count() > 1;
	}
	
	public function Times()
	{
		return $this->Event()->Recursion ? $this : $this->Times;
	}
	
	public function AllDay()
	{
		return $this->is_all_day == 1;
	}
	
	/**
	 * For the RSS feed
	 */
	public function Link()
	{
		if($this->Announcement())
			return $this->Calendar()->Link();
		else {
			$date = date('Y-m-d',strtotime($this->StartDate));
			$filters = Calendar::buildFilterString();
			$params = Controller::curr()->urlParams;
			if(isset($params['ID'])) {
				$calendar_params = "CalendarStart=" . $params['ID'];
				if(isset($params['OtherID']))
					$calendar_params .= "&CalendarEnd=" . $params['OtherID'];
			}
			else {
				$calendar_params = null;
			}
			if($filters || $calendar_params)
				$query_string = !empty($filters) ? "?" . $filters . "&amp;" . $calendar_params : "?" . $calendar_params;
			else
				$query_string = "";

			return $this->Event()->Link('view/'.$date.$query_string);
		}	
	}
	
	
	public function NestedLink() {
		if($this->Announcement()) {
			return $this->Calendar()->Link();
		}
		return Controller::curr()->Link('showevent/'.$this->Event()->URLSegment)."?d=".date('Y-m-d',strtotime($this->StartDate));
	}
	
	public function AbsoluteLink() {
		return Director::absoluteURL($this->Link());
	}
	
	public function AbsoluteNestedLink() {
		return Director::absoluteURL($this->NestedLink());
	}


	public function EventTitle()
	{
		return $this->Announcement() ? $this->Title : $this->Event()->Title;
	}
	
	public function EventContent()
	{
		return $this->Announcement() ? $this->Content : $this->Event()->Content;
	}
	
	public function ICSLink()
	{
		if($this->Feed)
			$ret = $this->Calendar()->Link() . "ics/" . $this->ID . "/"  . $this->MicroformatStart(false) . "-" . $this->MicroformatEnd(false) . "?title=".urlencode($this->Title);
		else if($this->Announcement())
				$ret = $this->Calendar()->Link() . "ics/announcement-" . $this->ID . "/"  . $this->MicroformatStart(false) . "-" . $this->MicroformatEnd(false); 	
		else
			$ret = $this->Event()->Parent()->Link() . "ics/" . $this->Event()->ID . "/" . $this->MicroformatStart(false) . "-" . $this->MicroformatEnd(false);
		return $ret;
	}
	
	public function OtherDates()
	{
		if($this->Announcement())
			return false;
		
		if($this->Event()->Recursion == 1) {
			
			return $this->Event()->Parent()->getNextRecurringEvents($this->Event(), $this);
		}
			
		return DataObject::get(
			get_class($this), 
			"EventID = {$this->EventID} AND StartDate != '{$this->StartDate}'", 
			"StartDate ASC",
			"",
			$this->Event()->Calendar()->DefaultEventDisplay
		);
	}
	
	protected function MicroformatStart($offset = true)
	{
		if(!$this->StartDate)
			return "";
			
		$date = $this->StartDate;
	
		if($this->AllDay())
			$time = "00:00:00";
		else
			$time = $this->StartTime ? $this->StartTime : "00:00:00";
	
		return CalendarUtil::Microformat($date, $time, $offset);
	}
	
	protected function MicroformatEnd($offset = true)
	{
		if($this->AllDay() && $this->StartDate) {
			$time = "00:00:00";
			$end = new sfDate($this->StartDate);
			$date = $end->tomorrow()->date();
			unset($end);
		}
		else {
			$date = $this->EndDate ? $this->EndDate : $this->StartDate;
			$time = $this->EndTime && $this->StartTime ? $this->EndTime : (!$this->EndTime && $this->StartTime ? $this->StartTime : "00:00:00");
		}

		return CalendarUtil::Microformat($date, $time, $offset);
	}
	
	
	
	public function _Dates()
	{
		list($strStartDate,$strEndDate) = CalendarUtil::getDateString($this->StartDate,$this->EndDate);
		$html =   "<span class='dtstart' title='".$this->MicroformatStart()."'>" . $strStartDate . "</span>"; 
		$html .=	($strEndDate != "") ? self::$date_delimiter : "";
		$html .= "<span class='dtend' title='" .$this->MicroformatEnd() ."'>";
		$html .=    ($strEndDate != "") ? $strEndDate : "";
		$html .=  "</span>";
		
		return $html;
	}
	
	public function _Times()
	{
		$func = self::get_param('time_format') == "24" ? "Nice24" : "Nice";
		$ret = $this->obj('StartTime')->$func();
		$ret .= $this->EndTime ? " &mdash; " . $this->obj('EndTime')->$func() : "";
		return $ret;
	}

	
}


?>
