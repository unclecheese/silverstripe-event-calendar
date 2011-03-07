<?php

class CalendarFilterFieldSet extends FieldSet
{

	public function __construct($items = null) 
	{
		parent::__construct();
		if(CalendarDateTime::dmy()) {
			$firstName = "Day";
			$firstFunc = "getDaysMap";
			$secondName = "Month";
			$secondFunc = "getMonthsMap";
		}
		else {
			$firstName = "Month";
			$firstFunc = "getMonthsMap";
			$secondName = "Day";
			$secondFunc = "getDaysMap";
		}

		$this->addFilterField(
			new FieldGroup(
				new DropdownField('Start'.$firstName, _t('CalendarFilterFieldSet.START','Start'), CalendarUtil::$firstFunc()),
				new DropdownField('Start'.$secondName,'', CalendarUtil::$secondFunc()),
				new DropdownField('StartYear', '', CalendarUtil::getYearsMap())
			)
		);
		$this->addFilterField(
			new FieldGroup(
				new DropdownField('End'.$firstName, _t('CalendarFilterFieldSet.END','End'), CalendarUtil::$firstFunc()),
				new DropdownField('End'.$secondName,'', CalendarUtil::$secondFunc()),
				new DropdownField('EndYear', '', CalendarUtil::getYearsMap())
			)
		);
	}	
	
	public function addFilterField($item, $key = null)
	{
		if($item instanceof FormField) {
			$name = $item->Name();
			$item->name = "filter[". $name . "]"; 
			parent::push($item, $key);
		}
	}
	
	public function removeFilterField($name)
	{
		parent::removeByName($name);
	}

	public function removeStartFields()
	{
		$this->removeByName('StartMonth');
		$this->removeByName('StartDay');
		$this->removeByName('StartYear');
	}
	
	public function removeEndFields()
	{
		$this->removeByName('EndMonth');
		$this->removeByName('EndDay');
		$this->removeByName('EndYear');
	}
}


?>