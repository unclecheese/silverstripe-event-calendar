<?php

/**
 * A day of the week - used to configure an event that is set to recur
 * 
 * @author Aaron Carlino
 * @author Grant Heggie
 * @package silverstripe-event-calendar
 */

namespace UncleCheese\EventCalendar\Models;

use Carbon\Carbon;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use UncleCheese\EventCalendar\Pages\CalendarEvent;

class RecurringDayOfWeek extends DataObject 
{
	private static $table_name = 'UncleCheese_RecurringDayOfWeek';

	private static $singular_name = 'Recurring day of week';

	private static $plural_name = 'Recurring days of week';

	private static $db = [
		'Value' => 'Int'
	];
	
	private static $belongs_many_many = [
		'CalendarEvent' => CalendarEvent::class
	];

	private static $default_sort = "Value ASC";
	
	/**
	 * Add week recurrence records
	 * 
	 * @return void
	 */
	private static function create_default_records()
	{
		for ($i = 0; $i <= 6; $i++) {
			$record = self::create();
			$record->Value = $i;			
			$record->write();
		}	
	}

	public function requireDefaultRecords()
	{
		parent::requireDefaultRecords();
		$records = self::get();
		if (!$records->exists()) {
			self::create_default_records();
		} elseif ($records->count() != 7)  {
			foreach ($records as $record) {
				$record->delete();
			}
			self::create_default_records();
		}
	}	

	public function getTitle()
	{
		return date("D", Carbon::now()->next($this->Value)->getTimestamp());
	}
	

	public function canCreate($member = null, $context = [])
	{
	    return Permission::check("CMS_ACCESS_CMSMain");
	}
	
	public function canEdit($member = null)
	{
	    return Permission::check("CMS_ACCESS_CMSMain");
	}
	
	public function canDelete($member = null)
	{
	    return Permission::check("CMS_ACCESS_CMSMain");
	}
	
	public function canView($member = null)
	{
	    return Permission::check("CMS_ACCESS_CMSMain");
	}
}
