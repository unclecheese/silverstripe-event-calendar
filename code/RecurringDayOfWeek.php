<?php

class RecurringDayOfWeek extends DataObject {
	


	static $db = array (
		'Value' => 'Int'
	);



	static $default_sort = "Value ASC";
	
	

	static $belongs_many_many = array (
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
	
}
