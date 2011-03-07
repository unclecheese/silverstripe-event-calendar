<?php
class CalendarScriptInit extends Extension
{
	public function augmentInit(){
		Requirements::javascript('event_calendar/javascript/calendar_interface.js');
		Requirements::css('event_calendar/css/calendar_cms.css');
	}
}