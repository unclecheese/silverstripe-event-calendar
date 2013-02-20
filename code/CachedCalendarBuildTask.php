<?php

class CachedCalendarBuildTask extends BuildTask {

	protected $title = "Cache the Event Calendars";
	

	protected $description = 'Generates a given number of years of events and populates a readonly table with all the event information. Useful when using recurring events or multiple calendars.';
	

	public function run($request) {
		CachedCalendarTask::create()->process();
	}
	
}