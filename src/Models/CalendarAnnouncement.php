<?php

/**
 * A calendar announcement
 * 
 * @author Aaron Carlino
 * @author Grant Heggie
 * @package silverstripe-event-calendar
 */

namespace UncleCheese\EventCalendar\Models;

use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use UncleCheese\EventCalendar\Pages\Calendar;
use UncleCheese\EventCalendar\Models\CalendarDateTime;

class CalendarAnnouncement extends CalendarDateTime 
{
	private static $table_name = 'UncleCheese_CalendarAnnouncement';

	private static $singular_name = 'Calendar accouncement';

	private static $plural_name = 'Calendar accouncements';

	private static $db = [
		'Title' => 'Varchar(255)',
		'Content' => 'Text'
	];

	private static $has_one = [
		'Calendar' => Calendar::class
	];

	public function getCMSFields()
	{
		$self = $this;

		$this->beforeUpdateCMSFields(function($f) use ($self) {
			$f->insertBefore(
				'StartDate', 
				TextField::create('Title', _t(__CLASS__.'.TITLE', 'Title of announcement'))
			);
			$f->insertBefore(
				'StartDate', 
				TextareaField::create('Content', _t(__CLASS__.'.CONTENT', 'Announcement content'))
			);
		});

		return $f = parent::getCMSFields();;
	}

	public function summaryFields()
	{
		return [
			'Title' => _t(__CLASS__.'.TITLE','Title of announcement'),
			'FormattedStartDate' => _t(Calendar::class.'.STARTDATE','Start date'),
			'FormattedEndDate' => _t(Calendar::class.'.ENDDATE','End date'),
			'FormattedStartTime' => _t(Calendar::class.'.STARTTIME','Start time'),
			'FormattedEndTime' => _t(Calendar::class.'.ENDTIME','End time'),
			'FormattedAllDay' => _t(Calendar::class.'.ALLDAY','All day'),
		];
	}
	
	public function getTitle()
	{
		return $this->getField('Title');
	}

	public function getContent()
	{
		return $this->getField('Content');
	}

}
