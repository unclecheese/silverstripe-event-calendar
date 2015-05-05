<?php

class RecurringDayOfMonth extends DataObject {
	
	private static $db = array (
		'Value' => 'Int'
	);

	private static $belongs_many_many = array (
		'CalendarEvent' => 'CalendarEvent'
	);
	
	private static $default_sort = "Value ASC";
	
	static function create_default_records() {
		for($i = 1; $i <= 31; $i++) {
			$record = new RecurringDayOfMonth();
			$record->Value = $i;
			$record->write();
		}	
	}
	
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$records = DataList::create("RecurringDayOfMonth");
		if(!$records->exists()) {
			self::create_default_records();
		}
		elseif($records->count() != 31) {
			foreach($records as $record) {
				$record->delete();
			}
			self::create_default_records();			
		}	
	}


	public function canCreate($member = null) {
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
