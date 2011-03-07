<?php

class CalendarEvent extends Page
{
	static $db = array (
		'Recursion' => 'Boolean',
		'CustomRecursionType' => 'Int',
		'DailyInterval' => 'Int',
		'WeeklyInterval' => 'Int',
		'MonthlyInterval' => 'Int',
		'MonthlyRecursionType1' => 'Int',
		'MonthlyRecursionType2' => 'Int',
		'MonthlyIndex' => 'Int',
		'MonthlyDayOfWeek' => 'Int'
	);
	
	static $has_many = array (
		'DateTimes' => 'CalendarDateTime',
		'Exceptions' => 'RecurringException'
	);
	
	static $has_one = array (
		'Calendar' => 'Calendar'
	);
	
	static $many_many = array (
		'RecurringDaysOfWeek' => 'RecurringDayOfWeek',
		'RecurringDaysOfMonth' => 'RecurringDayOfMonth'
	);

	static $icon = "event_calendar/images/event";	
	
	protected $datetime_class = null;
	protected $datetime_object = null;

	protected $ts = null;
	protected $allowedDaysOfWeek = array();
	protected $allowedDaysOfMonth = array();
	protected $exceptions = null;
	
	const DAY = 86400;
	const WEEK = 604800;

	public function getDateTimeClass()
	{
		if($this->datetime_class !== null)
			return $this->datetime_class;
			
		$class = get_class($this);
		$has_manys = eval("return $class::\$has_many;");
		if(is_array($has_manys)) {
			foreach($has_manys as $c) {
				if($c == 'CalendarDateTime' || is_subclass_of($c, 'CalendarDateTime')) {
					$this->datetime_class = $c;
					return $this->datetime_class;
				}
			}
		}

	}
	
	public function getDateTimeObject()
	{
		if($this->datetime_object !== null)
			return $this->datetime_object;
		
		$c = $this->getDateTimeClass();
		$this->datetime_object = new $c;
		return $this->datetime_object;
	}
	

	protected static function map_interval($max = 10)
	{
		$map = array();
		for($i=1;$i <= $max;$i++) {$map[$i] = $i;}
		return $map;
	}
	protected static function map_days_of_month()
	{
		return DataObject::get("RecurringDayOfMonth",null,"Value ASC")->toDropdownMap('ID', 'Value');
	}

	protected static function map_days_of_week()
	{
		return DataObject::get("RecurringDayOfWeek",null,"Value ASC")->toDropdownMap('ID', 'Skey');
	}
	
	public static function days_of_week_lookup() 
	{ 
		return array (
			'0' => _t('CalendarEvent.SUNDAY','Sunday'),
			'1' => _t('CalendarEvent.MONDAY','Monday'),
			'2' => _t('CalendarEvent.TUESDAY','Tuesday'),
			'3' => _t('CalendarEvent.WEDNESDAY','Wednesday'),
			'4' => _t('CalendarEvent.THURSDAY','Thursday'),
			'5' => _t('CalendarEvent.FRIDAY','Friday'),
			'6' => _t('CalendarEvent.SATURDAY','Saturday')
		);
	}	
	protected static function map_monthly_index()
	{
		return array (
			'1' => _t('CalendarEvent.FIRST','First'),
			'2' => _t('CalendarEvent.SECOND','Second'),
			'3' => _t('CalendarEvent.THIRD','Third'),
			'4' => _t('CalendarEvent.FOURTH','Fourth'),
			'5' => _t('CalendarEvent.LAST','Last')
		);
	}
	
	protected function initDates()
	{
		if($this->ts === null) {
			if($datetime = DataObject::get_one($this->getDateTimeClass(), "EventID = {$this->ID}")) {
				$this->ts = strtotime($datetime->StartDate);
			}
		}

		if($this->CustomRecursionType == 2 && empty($this->allowedDaysOfWeek)) {
			if($days_of_week = $this->getManyManyComponents('RecurringDaysOfWeek')) {
				foreach($days_of_week as $day)
					$this->allowedDaysOfWeek[] = $day->Value;
			}
		}
		else if($this->CustomRecursionType == 3 && empty($this->allowedDaysOfWeek)) {
			if($days_of_month = $this->getManyManyComponents('RecurringDaysOfMonth')) {
				foreach($days_of_month as $day)
					$this->allowedDaysOfMonth[] = $day->Value;
			}		
		}
		
		if($this->exceptions === null) {
			$this->exceptions = array();
			if($exceptions = $this->getComponents('Exceptions')) {
				foreach($exceptions as $exception)
					$this->exceptions[] = $exception->ExceptionDate;
			}
		}
	}
	
	public function getCMSFields()
	{
		$f = parent::getCMSFields();
		$dt = _t('CalendarEvent.DATESANDTIMES','Dates and Times');
		$f->addFieldsToTab("Root.Content.$dt", array (
					
			new LiteralField('wrapper1','<div id="Form_EditForm_DateTimes_Wrapper">'),
				$this->getDateTimeObject()->getDateTimeTable($this->ID),
			new LiteralField('wrapper_close1','</div>'),	
			
			new LiteralField('alert_message', '<div id="Repeat_Alert_Message" style="display:none;"></div>'),
			new CheckboxField('Recursion',_t('CalendarEvent.REPEATEVENT','Repeat this event')),
			
			new OptionsetField(
				'CustomRecursionType',
				_t('CalendarEvent.DESCRIBEINTERVAL','Describe the interval at which this event recurs.'),
				array (
					'1' => _t('CalendarEvent.DAILY','Daily'),
					'2' => _t('CalendarEvent.WEEKLY','Weekly'),
					'3' => _t('CalendarEvent.MONTHLY','Monthly')
				)
			),
			new LiteralField('wrapper2','<div id="Form_EditForm_DailyInterval_Wrapper">'),
				new FieldGroup(
					new LabelField($name = "every1", $title = _t("CalendarEvent.EVERY","Every ")),
					new DropdownField('DailyInterval', '', self::map_interval()),
					new LabelField($name = "days",$title = _t("CalendarEvent.DAYS"," day(s)"))
				),
			new LiteralField('wrapper_close2', '</div>'),

			new LiteralField('wrapper3','<div id="Form_EditForm_WeeklyInterval_Wrapper">'),
				new FieldGroup(
					new LabelField($name = "every2", $title = _t("CalendarEvent.EVERY","Every ")),
					new DropdownField('WeeklyInterval', '', self::map_interval()),
					new LabelField($name = "weeks", $title = _t("CalendarEvent.WEEKS", " weeks"))
				),			
				new CheckboxSetField('RecurringDaysOfWeek', _t('CalendarEvent.ONFOLLOWINGDAYS','On the following day(s)...'), self::map_days_of_week()),
			new LiteralField('wrapper_close3', '</div>'),
			

			new LiteralField('wrapper4','<div id="Form_EditForm_MonthlyInterval_Wrapper">'),
				new FieldGroup(
					new LabelField($name="every3", $title = _t("CalendarEvent.EVERY","Every ")),
					new DropdownField('MonthlyInterval', '', self::map_interval()),
					new LabelField($name = "months", $title = _t("CalendarEvent.MONTHS"," month(s)"))
				),
				new OptionsetField('MonthlyRecursionType1','', array('1' => _t('CalendarEvent.ONTHESEDATES','On these date(s)...'))),
				new CheckboxSetField('RecurringDaysOfMonth', '', self::map_days_of_month()),
				new OptionsetField('MonthlyRecursionType2','', array('1' => _t('CalendarEvent.ONTHE','On the...'))),				
				new FieldGroup(
					new DropdownField('MonthlyIndex','',self::map_monthly_index()),
					new DropdownField('MonthlyDayOfWeek','', self::days_of_week_lookup()),
					new LabelField( $name = "ofthemonth", $title = _t("CalendarEvent.OFTHEMONTH"," of the month."))
				),			
			new LiteralField('wrapper_close4', '</div>'),
			new LiteralField('wrapper_5','<div id="Form_EditForm_Exceptions_Wrapper">'),
				new HeaderField($title = _t('CalendarEvent.ANYEXCEPTIONS','Any exceptions to this pattern? Add the dates below.'), $headingLevel = '3'),
				$this->getExceptionsTable(),
			new LiteralField('wrapper_close5','</div>')
		));

		return $f;
	
	}
	
	private function getExceptionsTable()
	{
		$table = new TableField(
			'Exceptions', 
			'RecurringException', 
			array('ExceptionDate' => _t('CalendarEvent.DATE','Date')), 
			array('ExceptionDate' => 'DateField'), 
			null, 
			"CalendarEventID = $this->ID"
		);
		
		$table->setExtraData(array(
			'CalendarEventID' => $this->ID
		));
		
		return $table;
	}
	
	public function onBeforeWrite() 
	{
		
		// If the user has changed recursion types, we need to wipe out all
		// the empty fields that may result from resetting the TableField.
		if($empties = DataObject::get($this->getDateTimeClass(), "StartDate IS NULL")) {
			foreach($empties as $empty)
				$empty->delete();
		}
		parent::onBeforeWrite();
	}
	
	public function MultipleDates()
	{
		return DataObject::get($this->getDateTimeClass(),"EventID = $this->ID","StartDate ASC")->Count() > 1;
	}
	
	public function DateAndTime()
	{
		return DataObject::get($this->getDateTimeClass(),"EventID = $this->ID","StartDate ASC");
	}
	
	public function UpcomingDates($limit = 3)
	{
		return DataObject::get($this->getDateTimeClass(),"EventID = {$this->ID} AND StartDate >= DATE(NOW())","StartDate ASC","",$limit);	
	}
	
	

	public function OtherDates()
	{
		if(!$date = CalendarUtil::getDateFromURL()) {
			$date_obj =  DataObject::get_one($this->getDateTimeClass(),"EventID = {$this->ID}", "StartDate ASC");
			if(!$date_obj) return false;
		  else $date = $date_obj->StartDate;
		}
		if($date) {
			$cal = $this->Calendar();
			if($this->Recursion == 1) {
				$datetime_obj = DataObject::get_one($this->getDateTimeClass(), "EventID = {$this->ID}");
				$datetime_obj->StartDate = $date;
				return $cal->getNextRecurringEvents($this, $datetime_obj);
			}
			else {
				if($dates = DataObject::get($this->getDateTimeClass(), "EventID = {$this->ID} AND StartDate != '".$date."'","StartDate ASC","",$cal->OtherDatesCount))
					return CalendarUtil::CollapseDatesAndTimes($dates);
			}
		}
		else
			return false;
	}
	
	public function CurrentDate()
	{
		$date = false;
		if(!$date = CalendarUtil::getDateFromURL()) {
			if($obj =  DataObject::get_one($this->getDateTimeClass(),"EventID = {$this->ID}", "StartDate ASC"))
				$date = $obj->StartDate;
		}
		if($date) {
			if($this->Recursion) {
				$datetime = DataObject::get_one($this->getDateTimeClass(), "EventID = {$this->ID}");
				$datetime->StartDate = $date;
				return $datetime;
			}
			elseif($dates = DataObject::get($this->getDateTimeClass(), "EventID = {$this->ID} AND StartDate = '".$date."'")) 
				return CalendarUtil::CollapseDatesAndTimes($dates);
			else
				return false;
		}
	}
	
	
	public function Dates()
	{
		return CalendarUtil::CollapseDatesAndTimes($this->DateAndTime());
	}
	
		
	public function recursionHappensOn($ts)
	{
		$this->initDates();
		if($this->ts === null)
			return false;
			
		$objTestDate = new sfDate($ts);
		$objStartDate = new sfDate($this->ts);
		
		// Current date is before the recurring event begins.
		if($objTestDate->get() < $objStartDate->get())
			return false;
		elseif(in_array($objTestDate->date(), $this->exceptions))
			return false;
		
		switch($this->CustomRecursionType)
		{
			// Daily
			case 1:
				return $this->DailyInterval ? (($ts - $this->ts) / self::DAY) % $this->DailyInterval == 0 : false;
			break;
			// Weekly
			case 2:
				return ((($objTestDate->firstDayOfWeek()->get() - $objStartDate->firstDayOfWeek()->get()) / self::WEEK) % $this->WeeklyInterval == 0)
						&&
					   (in_array($objTestDate->reset()->format('w'), $this->allowedDaysOfWeek));							
			break;
			// Monthly
			case 3:
				if(CalendarUtil::differenceInMonths($objTestDate,$objStartDate) % $this->MonthlyInterval == 0) {
					// A given set of dates in the month e.g. 2 and 15.
					if($this->MonthlyRecursionType1 == 1) {
						return (in_array($objTestDate->reset()->format('j'), $this->allowedDaysOfMonth));
					}
					// e.g. "First Monday of the month"
					elseif($this->MonthlyRecursionType2 == 1) {
						// Last day of the month?
						if($this->MonthlyIndex == 5)							
							$targetDate = $objTestDate->addMonth()->firstDayOfMonth()->previousDay($this->MonthlyDayOfWeek)->dump();
						else {
							$objTestDate->subtractMonth()->finalDayOfMonth();
							for($i=0; $i < $this->MonthlyIndex; $i++)
								$objTestDate->nextDay($this->MonthlyDayOfWeek)->dump();
							$targetDate = $objTestDate->dump();
						}
						return $objTestDate->reset()->dump() == $targetDate;
					}
					else
						return false;
				}
				else
					return false;
		}
	}

}


class CalendarEvent_Controller extends Page_Controller
{

	public static $allowed_actions = array(
		'view'
	);


	public function init()
	{
		RSSFeed::linkToFeed($this->Parent()->Link() . "rss", _t("CalendarEvent.RSSFEED","RSS Feed of this calendar"));		
		parent::init();
		
		Requirements::css('event_calendar/css/calendar.css');
		Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.js');
		Requirements::javascript('event_calendar/javascript/calendar_core.js');
		
	}
	
	protected $date_from_url;
	
	public function RSSLink()
	{
		return $this->Parent()->Link('rss');
	}
	
	protected function Widget($type)
	{
		if($date = CalendarUtil::getDateFromURL())
			$date_obj = new sfDate($date);
		elseif($date = DataObject::get_one($this->getDateTimeClass(),"EventID = {$this->ID}", "StartDate ASC"))
			$date_obj = new sfDate($date->StartDate);
		else
			return false;
							
		return new $type($this->Parent(),$date_obj,$date_obj);
	}
	
	public function CalendarWidget()
	{
		return $this->Widget('CalendarWidget');
	}
	
	public function MonthNavigator()
	{
		return $this->Widget('MonthNavigator');
	}
	
	public function LiveCalendarWidget()
	{
	  return $this->Widget('LiveCalendarWidget');
	}
	
	public function MonthJumper() {
		if($date = CalendarUtil::getDateFromURL())
			$date_obj = new sfDate($date);
		elseif($date = DataObject::get_one($this->getDateTimeClass(),"EventID = {$this->ID}", "StartDate ASC"))
			$date_obj = new sfDate($date->StartDate);
		else
			return false;
			
		return $this->Parent()->MonthJumper();
	}

	public function CalendarFilterForm()
	{
		if($date = CalendarUtil::getDateFromURL())
			$date_obj = new sfDate($date);
		elseif($date = DataObject::get_one($this->getDateTimeClass(),"EventID = {$this->ID}", "StartDate ASC"))
			$date_obj = new sfDate($date->StartDate);
		else
			return false;
		
		$controller_name = $this->Parent()->class."_Controller";
		if(class_exists($controller_name)) {
			$controller = new $controller_name($this->Parent());
			$controller->parseURL();
			return $controller->CalendarFilterForm($date_obj);
		}
	}
	
	public function CalendarBackLink()
	{
		$filters = Calendar::buildFilterString();
		if(isset($_GET['CalendarStart'])) {
			$suffix = "view/";
			$suffix .= $_GET['CalendarStart'];
			if(isset($_GET['CalendarEnd'])) {
				$suffix .= "/" . $_GET['CalendarEnd'];
			}
		}
		else
			$suffix = "";
		
		$suffix .= !empty($filters) ? "?" . $filters : "";
		return $this->Parent()->Link() . $suffix;
	}
	
	public function view()
	{
		return array();
	}
	
	
	
}