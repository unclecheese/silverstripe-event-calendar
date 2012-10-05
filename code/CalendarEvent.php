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
	
	
	static $many_many = array (
		'RecurringDaysOfWeek' => 'RecurringDayOfWeek',
		'RecurringDaysOfMonth' => 'RecurringDayOfMonth'
	);

	static $icon = "event_calendar/images/event";	

	static $description = "An individual event entry";

	static $datetime_class = "CalendarDateTime";


	public function getCMSFields()
	{
		Requirements::javascript('event_calendar/javascript/calendar_cms.js');
		$f = parent::getCMSFields();
		$dt = _t('CalendarEvent.DATESANDTIMES','Dates and Times');
		$recursion = _t('CalendarEvent.RECURSION','Recursion');
		$f->addFieldToTab("Root.$dt",
			GridField::create(
				"DateTimes",
				_t('Calendar.DATETIMEDESCRIPTION','Add dates for this event'),
				$this->DateTimes(),
				GridFieldConfig_RecordEditor::create()
			)
		);

		$f->addFieldsToTab("Root.$recursion", array(
			new CheckboxField('Recursion',_t('CalendarEvent.REPEATEVENT','Repeat this event')),			
			new OptionsetField(
				'CustomRecursionType',
				_t('CalendarEvent.DESCRIBEINTERVAL','Describe the interval at which this event recurs.'),
				array (
					'1' => _t('CalendarEvent.DAILY','Daily'),
					'2' => _t('CalendarEvent.WEEKLY','Weekly'),
					'3' => _t('CalendarEvent.MONTHLY','Monthly')
				)
			)
		));
		
		$f->addFieldToTab("Root.$recursion", $dailyInterval = new FieldGroup(
					new LabelField($name = "every1", $title = _t("CalendarEvent.EVERY","Every ")),
					new DropdownField('DailyInterval', '', array_combine(range(1,10), range(1,10))),
					new LabelField($name = "days",$title = _t("CalendarEvent.DAYS"," day(s)"))
		));			
		
		$f->addFieldToTab("Root.$recursion", $weeklyInterval = new FieldGroup(
					new LabelField($name = "every2", $title = _t("CalendarEvent.EVERY","Every ")),
					new DropdownField('WeeklyInterval', '', array_combine(range(1,10), range(1,10))),
					new LabelField($name = "weeks", $title = _t("CalendarEvent.WEEKS", " weeks"))
		));
		
		$f->addFieldToTab("Root.$recursion", new CheckboxSetField(
				'RecurringDaysOfWeek', 
				_t('CalendarEvent.ONFOLLOWINGDAYS','On the following day(s)...'), 
				DataList::create("RecurringDayOfWeek")->map("ID", "Title")
		));
		
		$f->addFieldToTab("Root.$recursion", $monthlyInterval = new FieldGroup(
				new LabelField($name="every3", $title = _t("CalendarEvent.EVERY","Every ")),
				new DropdownField('MonthlyInterval', '', array_combine(range(1,10), range(1,10))),
				new LabelField($name = "months", $title = _t("CalendarEvent.MONTHS"," month(s)"))
		));

		$f->addFieldsToTab("Root.$recursion", array (
			new OptionsetField('MonthlyRecursionType1','', array('1' => _t('CalendarEvent.ONTHESEDATES','On these date(s)...'))),
			new CheckboxSetField('RecurringDaysOfMonth', '', DataList::create("RecurringDayOfMonth")->map("ID", "Value")),
			new OptionsetField('MonthlyRecursionType2','', array('1' => _t('CalendarEvent.ONTHE','On the...')))
		));

		$f->addFieldToTab("Root.$recursion", $monthlyIndex = new FieldGroup(
				new DropdownField('MonthlyIndex','', array (
					'1' => _t('CalendarEvent.FIRST','First'),
					'2' => _t('CalendarEvent.SECOND','Second'),
					'3' => _t('CalendarEvent.THIRD','Third'),
					'4' => _t('CalendarEvent.FOURTH','Fourth'),
					'5' => _t('CalendarEvent.LAST','Last')
				)),
				new DropdownField('MonthlyDayOfWeek','', DataList::create("RecurringDayOfWeek")->map("Value", "Title")),
				new LabelField( $name = "ofthemonth", $title = _t("CalendarEvent.OFTHEMONTH"," of the month."))
		));
		// $f->addFieldToTab("Root.$recursion",
		// 	GridField::create(
		// 		"Exceptions",
		// 		_t('CalendarEvent.ANYEXCEPTIONS','Any exceptions to this pattern? Add the dates below.'),
		// 		$this->Exceptions(),
		// 		GridFieldConfig_RecordEditor::create()
		// 	)
		// ));
		$dailyInterval->addExtraClass('dailyinterval');
		$weeklyInterval->addExtraClass('weeklyinterval');
		$monthlyInterval->addExtraClass('monthlyinterval');
		$monthlyIndex->addExtraClass('monthlyindex');

		$this->extend('updateCMSFields',$f);
		
		return $f;
	
	}


	public function getRecursionReader() {
		return new RecursionReader($this);
	}




	public function getDateTimeClass() {
		return $this->stat('datetime_class');
	}


	public function CalendarWidget() {
		return $this->Parent()->CalendarWidget();
	}


}





class CalendarEvent_Controller extends Page_Controller {

	public function init() {
		parent::init();
		Requirements::themedCSS('calendar','event_calendar');
	}



	public function MultipleDates() {
		return DataList::create($this->data()->getDateTimeClass())
			->where("EventID = $this->ID")
			->sort("StartDate ASC")
			->count() > 1;
	}


	
	public function DateAndTime() {
		return DataList::create($this->data()->getDateTimeClass())
			->where("EventID = $this->ID")
			->sort("StartDate ASC");
	}


	
	public function UpcomingDates($limit = 3) {
		return DataList::create($this->data()->getDateTimeClass())
			->where("EventID = {$this->ID} AND StartDate >= DATE(NOW())")
			->sort("StartDate ASC")
			->limit($limit);
	}
	
	

	public function OtherDates() {
		if(!isset($_REQUEST['date'])) {
			$date_obj =  DataList::create($this->data()->getDateTimeClass())
				->where("EventID = {$this->ID}")
				->sort("StartDate ASC")
				->first();
			if(!$date_obj) return false;
		  else $date = $date_obj->StartDate;
		}
		elseif(strtotime($_REQUEST['date']) > 0) {
			$date = date('Y-m-d', strtotime($_REQUEST['date']));
			$cal = $this->Parent();
			if($this->Recursion == 1) {
				$datetime_obj = DataList::create($this->data()->getDateTimeClass())
					->where("EventID = {$this->ID}")
					->first();
				$datetime_obj->StartDate = $date;
				return $cal->getNextRecurringEvents($this, $datetime_obj);
			}
			else {
				return DataList::create($this->data()->getDateTimeClass())
					->where("EventID = {$this->ID} AND StartDate != '".$date."'")
					->sort("StartDate ASC")
					->limit($cal->OtherDatesCount);
			}
		}
		return false;
	}


	
	public function CurrentDate() {
		if(!isset($_REQUEST['date'])) {
			$obj = DataList::create($this->data()->getDateTimeClass())
				->where("EventID = {$this->ID}")
				->sort("StartDate ASC")
				->first();
			if($obj) {
				$date = $obj->StartDate;
			}
		}
		elseif(strtotime($_REQUEST['date']) > 0) {
			$date = date('Y-m-d', strtotime($_REQUEST['date']));
			if($this->Recursion) {
				$datetime = DataList::create($this->data()->getDateTimeClass())
					->where("EventID = {$this->ID}")
					->first();
				if($datetime) {
					$datetime->StartDate = $date;
					return $datetime;
				}
			}			
			$result = DataList::create($this->data()->getDateTimeClass())
					->where("EventID = {$this->ID} AND StartDate = '".$date."'")
					->first();
			return $result;			
		}
	}


}
