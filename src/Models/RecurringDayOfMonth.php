<?php

namespace UncleCheese\EventCalendar\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use UncleCheese\EventCalendar\Pages\CalendarEvent;

class RecurringDayOfMonth extends DataObject 
{
	private static $table_name = 'UncleCheese_RecurringDayOfMonth';
	
	private static $db = [
		'Value' => 'Int'
	];

	private static $belongs_many_many = [
		'CalendarEvent' => CalendarEvent::class
	];
	
	private static $default_sort = "Value ASC";
	
	private static function create_default_records()
	{
		for ($i = 1; $i <= 31; $i++) {
			$record = self::create();
			$record->Value = $i;
			$record->write();
		}
	}
	
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$records = self::get();
		if (!$records->exists()) {
			self::create_default_records();
		} elseif ($records->count() != 31) {
			foreach($records as $record) {
				$record->delete();
			}
			self::create_default_records();			
		}	
	}


	public function canCreate($member = null, $context = []) {
	    return Permission::check("CMS_ACCESS_CMSMain");
	}
	
	public function canEdit($member = null) {
	    return Permission::check("CMS_ACCESS_CMSMain");
	}
	
	public function canDelete($member = null) {
	    return Permission::check("CMS_ACCESS_CMSMain");
	}
	
	public function canView($member = null) {
	    return Permission::check("CMS_ACCESS_CMSMain");
	}
	
}
