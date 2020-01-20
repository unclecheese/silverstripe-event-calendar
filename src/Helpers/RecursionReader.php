<?php

namespace UncleCheese\EventCalendar\Helpers;

use UncleCheese\EventCalendar\Pages\CalendarEvent;
use SilverStripe\ORM\DataList;
use SilverStripe\Core\Injector\Injectable;

class RecursionReader
{
	use Injectable;
	
	const DAY = 86400;

	const WEEK = 604800;

	protected $event, $datetimeClass, $eventClass, $ts;

	protected $allowedDaysOfWeek = [];
	
	protected $allowedDaysOfMonth = [];

	protected $exceptions = [];

	public static function difference_in_months($dateObj1,$dateObj2) {
		return (($dateObj1->format('Y') * 12) + $dateObj1->format('n')) - (($dateObj2->format('Y') * 12) + $dateObj2->format('n'));
	}

	public function __construct(CalendarEvent $event) {
		$this->event = $event;
		$this->datetimeClass = $event->Parent()->getDateTimeClass();
		$this->eventClass = $event->Parent()->getEventClass();
		$relation = $event->Parent()->getDateToEventRelation();
	
		if ($datetime = DataList::create($this->datetimeClass)->where("{$relation} = {$event->ID}")->first()) {
			$this->ts = strtotime($datetime->StartDate);
		}

		if ($event->CustomRecursionType == CalendarEvent::RECUR_INTERVAL_WEEKLY) {
			if ($days_of_week = $event->getManyManyComponents('RecurringDaysOfWeek')) {
				foreach ($days_of_week as $day) {
					$this->allowedDaysOfWeek[] = $day->Value;
				}
			}
		} elseif ($event->CustomRecursionType == CalendarEvent::RECUR_INTERVAL_MONTHLY) {
			if ($days_of_month = $event->getManyManyComponents('RecurringDaysOfMonth')) {
				foreach ($days_of_month as $day) {
					$this->allowedDaysOfMonth[] = $day->Value;
				}
			}		
		}
				
		if ($exceptions = $event->getComponents('Exceptions')) {
			foreach ($exceptions as $exception) {
				$this->exceptions[] = $exception->ExceptionDate;
			}			
		}
	}

	public function recursionHappensOn($ts)
	{
		$objTestDate = new \DateTime($ts); //new sfDate($ts);
		$objStartDate = new \DateTime($this->ts);//new sfDate($this->ts);
		
		// Current date is before the recurring event begins.
		if ($objTestDate->getTimestamp() < $objStartDate->getTimestamp() 
			|| in_array($objTestDate->format('Y-m-d'), $this->exceptions)
		) {
			return false;
		}

		switch($this->event->CustomRecursionType)
		{
			// Daily
			case CalendarEvent::RECUR_INTERVAL_DAILY:
				return $this->event->DailyInterval ? (($ts - $this->ts) / self::DAY) % $this->event->DailyInterval == 0 : false;
				break;
			// Weekly
			case CalendarEvent::RECUR_INTERVAL_WEEKLY:
				return ((($objTestDate->firstDayOfWeek()->get() - $objStartDate->firstDayOfWeek()->get()) / self::WEEK) % $this->event->WeeklyInterval == 0)
						&&
					   (in_array($objTestDate->reset()->format('w'), $this->allowedDaysOfWeek));							
				break;
			// Monthly
			case CalendarEvent::RECUR_INTERVAL_MONTHLY:
				if(self::difference_in_months($objTestDate,$objStartDate) % $this->event->MonthlyInterval == 0) {
					// A given set of dates in the month e.g. 2 and 15.
					if($this->event->MonthlyRecursionType1 == 1) {
						return (in_array($objTestDate->reset()->format('j'), $this->allowedDaysOfMonth));
					}
					// e.g. "First Monday of the month"
					elseif($this->event->MonthlyRecursionType2 == 1) {
						// Last day of the month?
						if($this->event->MonthlyIndex == 5) {							
							$targetDate = $objTestDate->addMonth()->firstDayOfMonth()->previousDay($this->event->MonthlyDayOfWeek)->dump();
						}
						else {
							$objTestDate->subtractMonth()->finalDayOfMonth();
							for($i=0; $i < $this->event->MonthlyIndex; $i++) {
								$objTestDate->nextDay($this->event->MonthlyDayOfWeek)->dump();
							}
							$targetDate = $objTestDate->dump();
						}
						return $objTestDate->reset()->dump() == $targetDate;
					}				
				}				
				return false;
		}
	}

}
