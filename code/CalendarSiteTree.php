<?php

class CalendarSiteTree extends Extension
{

	function extraStatics() {return array();}
  function UpcomingEvents($count = 5, $url_segment = null)
  {
    $cal = CalendarUtil::get_calendar_for($url_segment);
    return $cal ? $cal->UpcomingEvents($count) : false;
  }

  function RecentEvents($count = 5, $url_segment = null)
  {
    $cal = CalendarUtil::get_calendar_for($url_segment);
    return $cal ? $cal->RecentEvents($count) : false;
  }
  
  function CalendarWidget($url_segment = null, $start_date = null)
  {
    $cal = CalendarUtil::get_calendar_for($url_segment);
    if($cal) {
      if($start_date !== null) 
        $start_date = new sfDate(CalendarUtil::getDateFromString($start_date));
      return new CalendarWidget($cal, $start_date);
    }
    return false;
  }

  function LiveCalendarWidget($url_segment = null, $start_date = null)
  {
    $cal = CalendarUtil::get_calendar_for($url_segment);
    if($cal) {
      if($start_date !== null) 
        $start_date = new sfDate(CalendarUtil::getDateFromString($start_date));
      return new LiveCalendarWidget($cal, $start_date);
    }
    return false;
  }
  
  function MonthNavigator($url_segment = null, $start_date = null)
  {
  	$cal = CalendarUtil::get_calendar_for($url_segment);
  	if($cal) {
  		if($start_date !== null)
  			$start_date = new sfDate(CalendarUtil::getDateFromString($start_date));
  		return new MonthNavigator($cal, $start_date);
  	}
  	return false;
  }

}