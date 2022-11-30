<?php

/**
 * Controller for the calendar event page
 * 
 * @author Aaron Carlino
 * @author Grant Heggie
 * @package silverstripe-event-calendar
 */

namespace UncleCheese\EventCalendar\Pages;

use SilverStripe\ORM\DataList;
use SilverStripe\View\Requirements;
use UncleCheese\EventCalendar\Pages\Calendar;
use \PageController;

class CalendarEventController extends PageController 
{
	public function init()
	{
		parent::init();
		if (Calendar::config()->include_default_css) {
			Requirements::css('unclecheese/silverstripe-event-calendar:client/dist/css/calendar.css');
		}
	}
	
	/**
	 * @return bool
	 */
	public function MultipleDates()
	{
		return $this->DateAndTime()->count() > 1;
	}

	/**
	 * @return DataList
	 */
	public function DateAndTime()
	{
		return DataList::create($this->data()->getDateTimeClass())
			->filter("EventID", $this->ID)
			->sort("StartDate ASC");
	}

	/**
	 * @return DataList
	 */
	public function UpcomingDates($limit = 3)
	{
		return $this->DateAndTime()
			->where("StartDate:GreaterThanOrEqual", "DATE(NOW())")
			->limit($limit);
	}
	
	/**
	 * @return DataList
	 */
	public function getOtherDates()
	{
		if (!isset($_REQUEST['date'])) {
			$dateObj =  $this->DateAndTime()->first();
			if (!$date_obj) {
				return false;
			}
			$date = $dateObj->StartDate;
		} elseif (strtotime($_REQUEST['date']) > 0) {
			$date = date('Y-m-d', strtotime($_REQUEST['date']));
		}
		
		$cal = $this->Parent();

		if ($this->Recursion == 1) {
			$datetimeObj = DataList::create($this->data()->getDateTimeClass())
				->filter('EventID', $this->ID)
				->first();
			$datetimeObj->StartDate = $date;
			return $cal->getNextRecurringEvents($this, $datetimeObj);
		}
		
		return $this->DateAndTime()
			->exclude("StartDate", $date)
			->limit($cal->OtherDatesCount);
	}
	
	/**
	 * @return mixed
	 */
	public function getCurrentDate()
	{
		$allDates = DataList::create($this->data()->getDateTimeClass())
			->filter("EventID", $this->ID)
			->sort("StartDate ASC");
		if (!isset($_REQUEST['date'])) {
			// If no date filter specified, return the first one
			return $allDates->first();
		}
		if (strtotime($_REQUEST['date']) > 0) {
			$date = date('Y-m-d', strtotime($_REQUEST['date'] ?? ''));
			if ($this->Recursion) {
				$datetime = $allDates->first();
				if ($datetime) {
					$datetime->StartDate = $date;
					$datetime->EndDate = $date;
					return $datetime;
				}
			}
			return $allDates->filter("StartDate", $date)->first();
		}
	}
}