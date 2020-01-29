<?php

namespace UncleCheese\EventCalendar\Pages;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxsetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\LabelField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;
use UncleCheese\EventCalendar\Models\CalendarDateTime;
use UncleCheese\EventCalendar\Models\RecurringDayOfMonth;
use UncleCheese\EventCalendar\Models\RecurringDayOfWeek;
use UncleCheese\EventCalendar\Models\RecurringException;
use UncleCheese\EventCalendar\Pages\Calendar;
use \Page;

class CalendarEvent extends Page 
{
	CONST RECUR_INTERVAL_DAILY = 1;
	CONST RECUR_INTERVAL_WEEKLY = 2;
	CONST RECUR_INTERVAL_MONTHLY = 3;

	private static $table_name = 'UncleCheese_CalendarEvent';

	private static $db = [
		'Location' => 'Text',
		'Recursion' => 'Boolean',
		'CustomRecursionType' => 'Int',
		'DailyInterval' => 'Int',
		'WeeklyInterval' => 'Int',
		'MonthlyInterval' => 'Int',
		'MonthlyRecursionType1' => 'Int',
		'MonthlyRecursionType2' => 'Int',
		'MonthlyIndex' => 'Int',
		'MonthlyDayOfWeek' => 'Int'
	];
	
	private static $has_many = [
		'DateTimes'		=> CalendarDateTime::class,
		'Exceptions'	=> RecurringException::class
	];
	
	private static $many_many = [
		'RecurringDaysOfWeek'	=> RecurringDayOfWeek::class,
		'RecurringDaysOfMonth'	=> RecurringDayOfMonth::class
	];

	private static $icon = "unclecheese/silverstripe-event-calendar:client/dist/images/event-file.gif";	

	private static $description = "An individual event entry";

	private static $datetime_class = CalendarDateTime::class;
	
	private static $can_be_root = false;

	public function getCMSFields()
	{
		$self = $this;
		
		$this->beforeUpdateCMSFields(function($f) use ($self) {
			Requirements::javascript('unclecheese/silverstripe-event-calendar:client/dist/js/calendar_cms.js');
			Requirements::css('unclecheese/silverstripe-event-calendar:client/dist/css/calendar_cms.css');
			
			$f->addFieldToTab("Root.Main",
				TextField::create(
					"Location",
					_t(Calendar::class.'.LOCATIONDESCRIPTION','The location for this event')
				), 'Content'
			);
			
			$dt = _t(__CLASS__.'DATESANDTIMES','Dates and Times');
			$recursion = _t(__CLASS__.'.RECURSION','Recursion');
		
			$f->addFieldToTab("Root.$dt",
				GridField::create(
					"DateTimes",
					_t('Calendar.DATETIMEDESCRIPTION','Add dates for this event'),
					$self->DateTimes(),
					GridFieldConfig_RecordEditor::create()
				)
			);

			$f->addFieldsToTab("Root.$recursion", array(
				CheckboxField::create('Recursion', _t(__CLASS__.'.REPEATEVENT','Repeat this event'))->addExtraClass('recursion'),
				OptionsetField::create(
					'CustomRecursionType',
					_t(__CLASS__.'.DESCRIBEINTERVAL','Describe the interval at which this event recurs.'),
					[
						self::RECUR_INTERVAL_DAILY => _t(__CLASS__.'.DAILY', 'Daily'),
						self::RECUR_INTERVAL_WEEKLY => _t(__CLASS__.'.WEEKLY', 'Weekly'),
						self::RECUR_INTERVAL_MONTHLY => _t(__CLASS__.'.MONTHLY', 'Monthly')
					]
				)->setHasEmptyDefault(true)
			));
		
			$f->addFieldToTab(
				"Root.$recursion", 
				FieldGroup::create(
					LabelField::create("every1", _t(__CLASS__.'.EVERY', "Every ")),
					DropdownField::create('DailyInterval', '', array_combine(range(1,10), range(1,10))),
					LabelField::create("days", _t(__CLASS__.'.DAYS', " day(s)"))
				)->addExtraClass('dailyinterval')
			);
		
			$f->addFieldToTab(
				"Root.$recursion",
					FieldGroup::create(
					LabelField::create("every2", _t("CalendarEvent.EVERY","Every ")),
					DropdownField::create('WeeklyInterval', '', array_combine(range(1,10), range(1,10))),
					LabelField::create("weeks", _t("CalendarEvent.WEEKS", " weeks"))
				)->addExtraClass('weeklyinterval')
			);
		
			$f->addFieldToTab(
				"Root.$recursion",
				CheckboxSetField::create(
					'RecurringDaysOfWeek', 
					_t('CalendarEvent.ONFOLLOWINGDAYS','On the following day(s)...'),
					DataList::create("RecurringDayOfWeek")->map("ID", "Title")
				)
			);
		
			$f->addFieldToTab(
				"Root.$recursion", 
				FieldGroup::create(
					LabelField::create("every3", _t("CalendarEvent.EVERY", "Every ")),
					DropdownField::create('MonthlyInterval', '', array_combine(range(1,10), range(1,10))),
					LabelField::create("months", _t("CalendarEvent.MONTHS", " month(s)"))
				)->addExtraClass('monthlyinterval')
			);

			$f->addFieldsToTab(
				"Root.$recursion", 
				[
					OptionsetField::create(
						'MonthlyRecursionType1',
						'', 
						['1' => _t('CalendarEvent.ONTHESEDATES','On these date(s)...')]
					)->setHasEmptyDefault(true),
					CheckboxsetField::create('RecurringDaysOfMonth', '', DataList::create("RecurringDayOfMonth")->map("ID", "Value")),
					OptionsetField::create('MonthlyRecursionType2','', array('1' => _t('CalendarEvent.ONTHE','On the...')))->setHasEmptyDefault(true)
				]
			);

			$f->addFieldToTab(
				"Root.$recursion", 
				FieldGroup::create(
					DropdownField::create('MonthlyIndex', '', [
						'1' => _t('CalendarEvent.FIRST', 'First'),
						'2' => _t('CalendarEvent.SECOND', 'Second'),
						'3' => _t('CalendarEvent.THIRD', 'Third'),
						'4' => _t('CalendarEvent.FOURTH', 'Fourth'),
						'5' => _t('CalendarEvent.LAST', 'Last')
					])->setHasEmptyDefault(true),
					DropdownField::create('MonthlyDayOfWeek','', DataList::create('RecurringDayOfWeek')->map('Value', 'Title'))->setHasEmptyDefault(true),
					LabelField::create($name = "ofthemonth", $title = _t(__CLASS__.'.OFTHEMONTH', " of the month."))
				)->addExtraClass('monthlyindex')
			);
			$f->addFieldToTab("Root.$recursion",
				GridField::create(
					'Exceptions',
					_t(__CLASS__.'.ANYEXCEPTIONS', 'Any exceptions to this pattern? Add the dates below.'),
					$self->Exceptions(),
					GridFieldConfig_RecordEditor::create()
				)
			);

		});
		
		$f = parent::getCMSFields();
		
		return $f;
	}

	public function getRecursionReader()
	{
		return RecursionReader::create($this);
	}

	public function getDateTimeClass()
	{
		return $this->config()->datetime_class;
	}

	public function CalendarWidget()
	{
		return $this->Parent()->CalendarWidget();
	}

}