<?php
require_once(Director::baseFolder().'/event_calendar/code/sfTime.class.php');
require_once(Director::baseFolder().'/event_calendar/code/sfDate.class.php');
require_once(Director::baseFolder().'/event_calendar/code/sfDateTimeToolkit.class.php');
require_once(Director::baseFolder().'/event_calendar/code/CalendarUI.class.php');

if(!class_exists("DataObjectManager"))
	user_error(_t('EventCalendar.DATAOBJECTMANAGER','Event Calendar requires the DataObjectManager module.'),E_USER_ERROR);

LeftAndMain::require_javascript('event_calendar/javascript/calendar_interface.js');
LeftAndMain::require_css('event_calendar/css/calendar_cms.css');

Object::add_extension('SiteTree','CalendarSiteTree');

Calendar::set_param('language','EN');
Calendar::set_param('timezone', 'US-Eastern');


CalendarDateTime::set_param('offset','-04:00');

CalendarDateTime::set_date_format('dmy');
CalendarDateTime::set_time_format('24');

i18n::include_locale_file('event_calendar', 'en_US');

// Override to specify custom date templates. Falls back on lang file.

/**
 * Available date format keys
 
 	** Start Date **
	%{sWeekDayShort}    e.g. Mon
	%{sWeekDayFull}     e.g. Monday
	%{sDayNumFull}      e.g. 09
	%{sDayNumShort}		e.g. 9
	%{sDaySuffix}		e.g. th, rd, st
	%{sMonNumShort}		e.g. 8
	%{sMonNumFull}		e.g. 08
	%{sMonShort}		e.g. Oct
	%{sMonFull}			e.g. October
	%{sYearShort}		e.g. 09
	%{sYearFull}		e.g. 2009
	
	** End Date **
	%{eWeekDayShort}
	%{eWeekDayFull}
	%{eDayNumFull}
	%{eDayNumShort
	%{eDaySuffix}
	%{eMonNumShort}
	%{eMonNumFull}
	%{eMonShort}
	%{eMonFull}
	%{eYearShort}
	%{eYearFull}

*/ 
global $customDateTemplates;
$customDateTemplates = array(
/*
 You can modify the date display by assigning new date templates to any of the following
   date scenarios. Use the above date format keys.
   
'OneDay' 			=> '%{sMonShort}. %{sDayNumShort}, %{sYearFull}'
'SameMonthSameYear' => '%{sMonShort}. %{sDayNumShort} - %{eDayNumShort}, %{eYearFull}'
'DiffMonthSameYear' => '%{sMonShort}. %{sDayNumShort} - %{eMonShort}. %{eDayNumShort}, %{eYearFull}'
'DiffMonthDiffYear' => '%{sMonShort}. %{sDayNumShort}, %{sYearFull} - %{eMonShort} %{eDayNumShort}, %{eYearFull}'

'OneDayHeader' 			=> '%{sMonFull} %{sDayNumShort}%{sDaySuffix}, %{sYearFull}'
'MonthHeader' 			=> '%{sMonFull}, %{sYearFull}'
'YearHeader' 				=> '%{sYearFull}'

*/
);


?>