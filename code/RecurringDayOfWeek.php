<?php

class RecurringDayOfWeek extends DataObject {

	private static $db = array (
		'Value' => 'Int'
	);

	private static $default_sort = "Value ASC";
	
	private static $belongs_many_many = array (
		'CalendarEvent' => 'CalendarEvent'
	);
	
	static function create_default_records() {
		for($i = 0; $i <= 6; $i++) {
			$record = new RecurringDayOfWeek();
			$record->Value = $i;			
			$record->write();
		}	
	}

	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$records = DataList::create("RecurringDayOfWeek");
		if(!$records->exists()) {
			self::create_default_records();
		}
		elseif($records->count() != 7)  {
			foreach($records as $record) {
				$record->delete();
			}
			self::create_default_records();
		}
	}	

	public function getTitle() {
		return strftime("%a", sfDate::getInstance()->nextDay($this->Value)->get());
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
