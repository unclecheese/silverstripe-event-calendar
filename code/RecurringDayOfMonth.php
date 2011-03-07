<?php

class RecurringDayOfMonth extends DataObject {
	
	static $db = array (
		'Value' => 'Int'
	);
	
	static $belongs_many_many = array (
		'CalendarEvent' => 'CalendarEvent'
	);
	
	static function doDefaultRecords()
	{
		for($i = 1; $i <= 30; $i++)
		{
			$record = new RecurringDayOfMonth();
			$record->Value = $i;
			$record->write();
		}	
	}
	
	public function requireDefaultRecords()
	{
		parent::requireDefaultRecords();
		if(!DataObject::get("RecurringDayOfMonth"))
		{
			self::doDefaultRecords();
		}
		elseif($records = DataObject::get("RecurringDayOfMonth"))
		{
			if($records->Count() < 30)
			{
				foreach($records as $record)
				{
					$record->delete();
				}
				self::doDefaultRecords();
			}
		}
        
        //SS_Database::alteration_message("Recurring Days of Month added.","created"); 		
	
	}	
}

?>