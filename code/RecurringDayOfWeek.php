<?php

class RecurringDayOfWeek extends DataObject {
	
	static $db = array (
		'Value' => 'Int',
		'Skey' => 'Varchar(1)'
	);
	
	static $belongs_many_many = array (
		'CalendarEvent' => 'CalendarEvent'
	);
	
	static $days_initial = array ('S', 'M', 'T', 'W', 'T', 'F', 'S');
	
	static function doDefaultRecords()
	{
		for($i = 0; $i <= 6; $i++)
		{
			$record = new RecurringDayOfWeek();
			$record->Value = $i;
			$record->Skey = self::$days_initial[$i];
			$record->write();
		}	
	}
	public function requireDefaultRecords()
	{
		parent::requireDefaultRecords();
		if(!DataObject::get("RecurringDayOfWeek"))
		{
			self::doDefaultRecords();
		}
		elseif($records = DataObject::get("RecurringDayOfWeek"))
		{
			if($records->Count() < 7)
			{
				foreach($records as $record)
				{
					$record->delete();
				}
				self::doDefaultRecords();
			}
		}
        
        //SS_Database::alteration_message("Recurring Days of Week added.","created"); 		
	
	}	
	
}

?>