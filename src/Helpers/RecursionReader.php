<?php

namespace UncleCheese\EventCalendar\Helpers;

use Carbon\Carbon;
use UncleCheese\EventCalendar\Pages\CalendarEvent;
use SilverStripe\ORM\DataList;
use SilverStripe\Core\Injector\Injectable;

class RecursionReader
{
	use Injectable;

	const DAY_SECONDS = 86400;		// Seconds in a day
	const WEEK_SECONDS = 604800;	// Seconds in a week

	/**
	 * @var CalendarEvent
	 */
	protected $event;

	/**
	 * @var string
	 */
	protected $datetimeClass;

	/**
	 * @var string
	 */
	protected $eventClass;

	/**
	 * @var string
	 */
	protected $ts;

	/**
	 * @var array
	 */
	protected $allowedDaysOfWeek = [];

	/**
	 * @var array
	 */
	protected $allowedDaysOfMonth = [];

	/**
	 * @var array
	 */
	protected $exceptions = [];

	/**
	 * @return int
	 */
	public static function difference_in_months($dateObj1, $dateObj2)
	{
		return (($dateObj1->format('Y') * 12) + $dateObj1->format('n')) - (($dateObj2->format('Y') * 12) + $dateObj2->format('n'));
	}

	public function __construct(CalendarEvent $event)
	{
		$this->event = $event;
		$cal = $event->Parent();

		$this->datetimeClass = $cal->getDateTimeClass();
		$this->eventClass = $cal->getEventClass();
		$relation = $cal->getDateToEventRelation();

		if ($datetime = DataList::create($this->datetimeClass)
			->filter($relation, $event->ID)->first()
		) {
			$this->ts = strtotime($datetime->StartDate ?? '');
		}

		if ($event->CustomRecursionType == CalendarEvent::RECUR_INTERVAL_WEEKLY) {
			if ($daysOfWeek = $event->getManyManyComponents('RecurringDaysOfWeek')) {
				foreach ($daysOfWeek as $day) {
					$this->allowedDaysOfWeek[] = $day->Value;
				}
			}
		} elseif ($event->CustomRecursionType == CalendarEvent::RECUR_INTERVAL_MONTHLY) {
			if ($daysOfMonth = $event->getManyManyComponents('RecurringDaysOfMonth')) {
				foreach ($daysOfMonth as $day) {
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

	/**
	 * @param int $ts The timestamp to check
	 * @return bool
	 */
	public function recursionHappensOn($ts)
	{
		$originalTestDate = Carbon::createFromTimestamp($ts);
		$testDate = $originalTestDate->copy();
		$startDate = Carbon::createFromTimestamp($this->ts);
		$result = false;

		// Current date is before the recurring event begins.
		if ($testDate->getTimestamp() < $startDate->getTimestamp()
			|| in_array($testDate->toDateString(), $this->exceptions)
		) {
			return $result;
		}

		switch ($this->event->CustomRecursionType) {

			// Daily
			case CalendarEvent::RECUR_INTERVAL_DAILY:
				if ($this->event->DailyInterval
					&& ((($ts - $this->ts) / self::DAY_SECONDS) % $this->event->DailyInterval == 0)
				) {
					$result = true;
				}
				break;

			// Weekly
			case CalendarEvent::RECUR_INTERVAL_WEEKLY:
				$testFirstDay = clone $testDate;
				$testFirstDay->modify(($testFirstDay->format('l') == 'Sunday')
					? 'Monday last week'
					: 'Monday this week'
				);
				if ((($testFirstDay->getTimestamp() - $startDate->startOfWeek()->getTimestamp()) / self::WEEK_SECONDS) % $this->event->WeeklyInterval == 0
					&& in_array($testDate->format('w'), $this->allowedDaysOfWeek)
				) {
					$result = true;
				};
				break;

			// Monthly
			case CalendarEvent::RECUR_INTERVAL_MONTHLY:
				if (self::difference_in_months($testDate, $startDate) % $this->event->MonthlyInterval == 0) {

					if ($this->event->MonthlyRecursionType1 == 1) {

						// A given set of dates in the month e.g. 2 and 15.
						if (in_array($originalTestDate->format('j'), $this->allowedDaysOfMonth)) {
							$result = true;
						}

					} elseif ($this->event->MonthlyRecursionType2 == 1) {

						// e.g. "First Monday of the month"
						if ($this->event->MonthlyIndex == 5) {
							// Last day of the month?
							$targetDate = $testDate->addMonth()->startOfMonth()->previous($this->event->MonthlyDayOfWeek)->format('Y-m-d H:i:s');
						} else {
							$testDate->modify("last day of previous month");
							for ($i = 0; $i < $this->event->MonthlyIndex; $i++) {
								$testDate->next($this->event->MonthlyDayOfWeek)->format('Y-m-d H:i:s');
							}
							$targetDate = $testDate->format('Y-m-d H:i:s');
						}
						return $originalTestDate->format('Y-m-d H:i:s') == $targetDate;
					}
				}
				break;
		}

		return $result;
	}
}