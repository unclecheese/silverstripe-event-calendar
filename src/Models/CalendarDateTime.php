<?php

namespace UncleCheese\EventCalendar\Models;

use Carbon\Carbon;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TimeField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use UncleCheese\EventCalendar\Helpers\CalendarUtil;
use UncleCheese\EventCalendar\Model\CalendarAnnouncement;
use UncleCheese\EventCalendar\Pages\CalendarEvent;

class CalendarDateTime extends DataObject 
{
	private static $table_name = 'UncleCheese_CalendarDateTime';

	private static $db = [		
		'StartDate' => 'Date',
		'StartTime' => 'Time',
		'EndDate' => 'Date',
		'EndTime' => 'Time',
		'AllDay' => 'Boolean'		
	];
	
	private static $has_one = [
		'Event' => CalendarEvent::class
	];

	private static $date_format_override;

	private static $time_format_override;

	private static $default_sort = "StartDate ASC, StartTime ASC";

	private static $formatted_field_empty_string = '--';

	private static $time_range_separator = ' &mdash; ';

	/**
	 * Set to the timezone offset (E.g. +12:00 for GMT+12). Must be in ISO 8601 format
	 * 
	 * @config
	 * @see http://php.net/manual/en/function.date.php
	 * @var string
	 */
	private static $offset = "+00:00";

	public function getCMSFields() 
	{
		$fields = FieldList::create(
			DateField::create('StartDate', _t(__CLASS__.'.STARTDATE','Start date')),
			DateField::create('EndDate', _t(__CLASS__.'.ENDDATE','End date')),
			TimeField::create('StartTime', _t(__CLASS__.'.STARTTIME','Start time')),
			TimeField::create('EndTime', _t(__CLASS__.'.ENDTIME','End time')),
			CheckboxField::create('AllDay', _t(__CLASS__.'.ALLDAY','This event lasts all day'))
		);

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	public function summaryFields() {
		return [
			'FormattedStartDate' => _t(__CLASS__.'.STARTDATE','Start date'),
			'FormattedEndDate' => _t(__CLASS__.'.ENDDATE','End date'),
			'FormattedStartTime' => _t(__CLASS__.'.STARTTIME','Start time'),
			'FormattedEndTime' => _t(__CLASS__.'.ENDTIME','End time'),
			'FormattedAllDay' => _t(__CLASS__.'.ALLDAY','All day'),
		];
	}

	public function Link()
	{
		return Controller::join_links($this->Event()->Link(),"?date=".$this->StartDate);
	}

	public function getDateRange()
	{
		list($startDate, $endDate) = CalendarUtil::get_date_string($this->StartDate, $this->EndDate);
		return $this->customise(
			[
				'StartDate'	=> $startDate,
				'EndDate'	=> $endDate
			]
		)->renderWith(__CLASS__ .'\DateRange');
	}
	
	public function getTimeRange()
	{
		$func = CalendarUtil::get_time_format() == "24" ? "Nice24" : "Nice";
		$ret = $this->obj('StartTime')->$func();
		$ret .= $this->EndTime ? self::config()->time_range_separator . $this->obj('EndTime')->$func() : "";
		return $ret;
	}

	public function Announcement()
	{
		return $this->ClassName == CalendarAnnouncement::class;
	}

	public function getOtherDates()
	{
		if ($this->Announcement()) {
			return false;
		}

		if ($this->Event()->Recursion) {	
			return $this->Event()->Parent()->getNextRecurringEvents($this->Event(), $this);
		}
		
		return self::get()->filter(
			[
				'EventID' => $this->EventID,
				'StartDate:Not' => $this->StartDate
			]
		)->limit($this->Event()->Parent()->OtherDatesCount);
	}

	public function MicroformatStart($offset = true) {
		if (!$this->StartDate) {
			return "";
		}
		$time = (!$this->AllDay && $this->StartTime) ? $this->StartTime : "00:00:00";
		return CalendarUtil::microformat($this->StartDate, $time, self::config()->offset);
	}

	public function MicroformatEnd($offset = true) {
		if ($this->AllDay && $this->StartDate) {
			$time = "00:00:00";
			$date = new Carbon($this->StartDate);
			$date = $date->addDay()->format('Y-m-d');
		} else {
			$date = $this->EndDate ? $this->EndDate : $this->StartDate;
			if ($this->EndTime && $this->StartTime) {
				$time = $this->EndTime;
			} else {
				$time = (!$this->EndTime && $this->StartTime) ? $this->StartTime : "00:00:00";
			}
		}
		return CalendarUtil::microformat($date, $time, self::config()->offset);
	}

	public function ICSLink() {
		$ics_start = $this->obj('StartDate')->Format('Ymd')."T".$this->obj('StartTime')->Format('His');
		if ($this->EndDate) {
			$ics_end = $this->obj('EndDate')->Format('Ymd')."T".$this->obj('EndTime')->Format('His'); 
		} else {
			$ics_end = $ics_start;
		}
		if ($this->Feed) {
			return Controller::join_links(
				$this->Calendar()->Link(),
				"ics",
				$this->ID,
				$ics_start . "-" . $ics_end,
				"?title=".urlencode($this->Title)
			);
		} elseif ($this->Announcement()) {
			return Controller::join_links(
				$this->Calendar()->Link(),
				"ics",
				"announcement-".$this->ID, 
				$ics_start . "-" . $ics_end
			); 
		}
		return Controller::join_links(
			$this->Event()->Parent()->Link(),
			"ics",
			$this->Event()->ID,
			$ics_start . "-" . $ics_end
		);
	}

	public function getFormattedStartDate()
	{
		if (!$this->StartDate) {
			return $this->config()->formatted_field_empty_string;
		}
		return CalendarUtil::get_date_format() == "mdy" 
			? $this->obj('StartDate')->Format('MM-dd-Y') 
			: $this->obj('StartDate')->Format('dd-MM-Y');
	}
	
	public function getFormattedEndDate()
	{
		if (!$this->EndDate) {
			return $this->config()->formatted_field_empty_string;
		}
		return CalendarUtil::get_date_format() == "mdy" 
			? $this->obj('EndDate')->Format('MM-dd-Y') 
			: $this->obj('EndDate')->Format('dd-MM-Y');
	}

	public function getFormattedStartTime()
	{
		if (!$this->StartTime) {
			return $this->config()->formatted_field_empty_string;
		}
		return CalendarUtil::get_time_format() == "12" 
			? $this->obj('StartTime')->Nice() 
			: Carbon::createFromTimeString($this->StartTime)->format('H:i');
	}

	public function getFormattedEndTime()
	{
		if (!$this->EndTime) {
			return $this->config()->formatted_field_empty_string;
		}
		return CalendarUtil::get_time_format() == "12" 
			? $this->obj('EndTime')->Nice() 
			: Carbon::createFromTimeString($this->EndTime)->format('H:i');
	}

	public function getFormattedAllDay()
	{
	   return $this->AllDay == 1 ? _t(__CLASS__.'.YES', 'Yes') : _t(__CLASS__.'.NO', 'No');
	}

	public function getTitle()
	{
		return $this->Event()->Title;
	}

	public function getContent()
	{
		return $this->Event()->Content;
	}

	public function getAllDatesInRange()
	{
		$start = new \DateTime($this->StartDate);
		$end = new \DateTime($this->EndDate);
		$end = $end->getTimestamp();
		$dates = [];
		do {
			$dates[] = $start->format('Y-m-d');
			$start->add(new \DateInterval('P1D'));
		} while ($start->getTimestamp() <= $end);

		return $dates;
	}
	
	public function canCreate($member = null, $context = [])
	{
		if (!$member) {
			$member = Member::currentUser();
		}
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}

	public function canEdit($member = null)
	{
		if (!$member) {
			$member = Member::currentUser();
		}
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}

	public function canDelete($member = null)
	{
		if (!$member) {
			$member = Member::currentUser();
		}
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}

	public function canView($member = null)
	{
		if (!$member) {
			$member = Member::currentUser();
		}
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}

}
