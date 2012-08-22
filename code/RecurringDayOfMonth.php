<?php

class RecurringDayOfMonth extends DataObject {
	


	static $db = array (
		'Value' => 'Int'
	);


	
	static $belongs_many_many = array (
		'CalendarEvent' => 'CalendarEvent'
	);


	
	static $default_sort = "Value ASC";


	
	static function create_default_records() {
		for($i = 1; $i <= 30; $i++) {
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
		elseif($records->count() != 30) {
			foreach($records as $record) {
				$record->delete();
			}
			self::create_default_records();			
		}	
	}


}
