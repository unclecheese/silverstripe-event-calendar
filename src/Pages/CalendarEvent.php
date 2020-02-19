<?php

/**
 * Calendar event page
 * 
 * @author Aaron Carlino
 * @author Grant Heggie
 * @package silverstripe-event-calendar
 */

namespace UncleCheese\EventCalendar\Pages;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxsetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\LabelField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\View\Requirements;
use UncleCheese\EventCalendar\Helpers\RecursionReader;
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

	private static $singular_name = 'Calendar event';

	private static $plural_name = 'Calendar events';

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

	public function getCMSFields() {
		
		$self = $this;
		
		$this->beforeUpdateCMSFields(function($f) use ($self) {
			Requirements::javascript('unclecheese/silverstripe-event-calendar:client/dist/js/calendar_cms.js');
			Requirements::css('unclecheese/silverstripe-event-calendar:client/dist/css/calendar_cms.css');
			
			$f->addFieldToTab("Root.Main",
				TextField::create(
					'Location',
					_t(Calendar::class.'.LOCATIONDESCRIPTION', 'The location for this event')
				), 'Content'
			);
			
			$dt = _t(__CLASS__.'DATESANDTIMES', 'Dates and Times');
			$recursion = _t(__CLASS__.'.RECURSION', 'Recursion');
		
			$f->addFieldToTab("Root.$dt",
				GridField::create(
					'DateTimes',
					_t(Calendar::class.'.DATETIMEDESCRIPTION','Add dates for this event'),
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
					DropdownField::create(
						'DailyInterval', 
						_t(__CLASS__.'.EVERY', 'Every'), 
						array_combine(range(1,10), range(1,10))
					),
					LabelField::create("days", _t(__CLASS__.'.DAYS', ' day(s)'))
				)->addExtraClass('dailyinterval')
			);
		
			$f->addFieldToTab(
				"Root.$recursion",
				FieldGroup::create(
					DropdownField::create(
						'WeeklyInterval', 
						_t(__CLASS__.'.EVERY', 'Every'), 
						array_combine(range(1,10), range(1,10))
					),
					LabelField::create("weeks", _t(__CLASS__.'.WEEKS', ' weeks'))
				)->addExtraClass('weeklyinterval')
			);
		
			$f->addFieldToTab(
				"Root.$recursion",
				ListboxField::create(
					'RecurringDaysOfWeek', 
					_t(__CLASS__.'.ONFOLLOWINGDAYS', 'On the following day(s)...'),
					RecurringDayOfWeek::get()->map("ID", "Title")->toArray()
				)
			);
		
			$f->addFieldToTab(
				"Root.$recursion", 
				FieldGroup::create(
					DropdownField::create(
						'MonthlyInterval', 
						_t(__CLASS__.'.EVERY', 'Every'), 
						array_combine(range(1,10), range(1,10))
					),
					LabelField::create("months", _t(__CLASS__.'.MONTHS', ' month(s)'))
				)->addExtraClass('monthlyinterval')
			);

			$f->addFieldsToTab(
				"Root.$recursion", 
				[
					OptionsetField::create(
						'MonthlyRecursionType1',
						'', 
						['1' => _t(__CLASS__.'.ONTHESEDATES','On these date(s)...')]
					)->setHasEmptyDefault(true),
					ListboxField::create(
						'RecurringDaysOfMonth', 
						'', 
						RecurringDayOfMonth::get()->map("ID", "Value")->toArray()
					),
					OptionsetField::create(
						'MonthlyRecursionType2',
						'', 
						['1' => _t(__CLASS__.'.ONTHE','On the...')]
					)->setHasEmptyDefault(true)
				]
			);

			$f->addFieldToTab(
				"Root.$recursion", 
				FieldGroup::create(
					DropdownField::create('MonthlyIndex', '', [
						'1' => _t(__CLASS__.'.FIRST', 'First'),
						'2' => _t(__CLASS__.'.SECOND', 'Second'),
						'3' => _t(__CLASS__.'.THIRD', 'Third'),
						'4' => _t(__CLASS__.'.FOURTH', 'Fourth'),
						'5' => _t(__CLASS__.'.LAST', 'Last')
					])->setHasEmptyDefault(true),
					DropdownField::create(
						'MonthlyDayOfWeek',
						'', 
						RecurringDayOfWeek::get()->map('Value', 'Title')->toArray()
					)->setHasEmptyDefault(true),
					LabelField::create("ofthemonth", _t(__CLASS__.'.OFTHEMONTH', " of the month."))
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

	public function getCalendarWidget()
	{
		return $this->Parent()->getCalendarWidget();
	}

}