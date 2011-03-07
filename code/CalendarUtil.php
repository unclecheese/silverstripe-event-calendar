<?php

class CalendarUtil
{
	const ONE_DAY = "OneDay";
	const SAME_MONTH_SAME_YEAR = "SameMonthSameYear";
	const DIFF_MONTH_SAME_YEAR = "DiffMonthSameYear";
	const DIFF_MONTH_DIFF_YEAR = "DiffMonthDiffYear";
	
	const ONE_DAY_HEADER = "OneDayHeader";
	const MONTH_HEADER = "MonthHeader";
	const YEAR_HEADER = "YearHeader";
	
	public static $months_map = array (
		'01' => 'Jan',
		'02' => 'Feb',
		'03' => 'Mar',
		'04' => 'Apr',
		'05' => 'May',
		'06' => 'Jun',
		'07' => 'Jul',
		'08' => 'Aug',
		'09' => 'Sep',
		'10' => 'Oct',
		'11' => 'Nov',
		'12' => 'Dec'
	);
	
	private static $format_character_placeholders = array(
		'%{sWeekDayShort}',
		'%{sWeekDayFull}',
		'%{sDayNumShort}',
		'%{sDayNumFull}',
		'%{sDaySuffix}',
		'%{sMonNumShort}',
		'%{sMonNumFull}',
		'%{sMonShort}',
		'%{sMonFull}',
		'%{sYearShort}',
		'%{sYearFull}',
		'%{eWeekDayShort}',
		'%{eWeekDayFull}',
		'%{eDayNumShort}',
		'%{eDayNumFull}',
		'%{eDaySuffix}',
		'%{eMonNumShort}',
		'%{eMonNumFull}',
		'%{eMonShort}',
		'%{eMonFull}',
		'%{eYearShort}',
		'%{eYearFull}'
	);

	private static function format_character_replacements($start, $end)
	{
		return array(
			self::i18n_date('%a', $start), // sWeekDayShort
			self::i18n_date('%A', $start), // sWeekDayFull
			date ('j', $start), // sDayNumFull
			date ('d', $start), //sDayNumShort
			date ('S', $start), // sDaySuffix
			date ('n', $start), // sMonNumShort
			date ('m', $start), // sMonNumFull
			self::i18n_date('%b', $start), // sMonShort
			self::i18n_date('%B', $start), // sMonFull
			date ('y', $start), // sYearShort
			date ('Y', $start), // sYearFull

			self::i18n_date('%a', $end), // eWeekDayShort
			self::i18n_date('%A', $end), // eWeekDayFull
			date ('d', $end), // eDayNumFull
			date ('j', $end), // eDayNumFull			
			date ('S', $end), // eDaySuffix
			date ('n', $end), // eMonNumShort
			date ('m', $end), // eMonNumFull
			self::i18n_date('%b', $end), // eMonShort
			self::i18n_date('%B', $end), // eMonFull
			date ('y', $end), // eYearShort
			date ('Y', $end), // eYearFull
		);	
	}
	
	public static function i18n_date($char, $ts)
	{
		// Need to figure out how we're handling non- UTF-8 users.
		//return utf8_encode(strftime($char, $ts));
		return strftime($char,$ts);
	}
	
	public static function localize($start, $end, $key)
	{
		global $customDateTemplates;
		global $lang;
		if(is_array($customDateTemplates) && isset($customDateTemplates[$key]))
			$template = $customDateTemplates[$key];
		else {
			$template = _t("Calendar.$key",$lang['en_US']['Calendar'][$key]); 
		}
			
		return str_replace(self::$format_character_placeholders, self::format_character_replacements($start,$end), $template);		
	}	
	
	
	public static function getMonthsMap($key = '%b')
	{
    return array (
  		'01' => self::i18n_date($key,strtotime('2000-01-01')),
  		'02' => self::i18n_date($key,strtotime('2000-02-01')),
  		'03' => self::i18n_date($key,strtotime('2000-03-01')),
  		'04' => self::i18n_date($key,strtotime('2000-04-01')),
  		'05' => self::i18n_date($key,strtotime('2000-05-01')),
  		'06' => self::i18n_date($key,strtotime('2000-06-01')),
  		'07' => self::i18n_date($key,strtotime('2000-07-01')),
  		'08' => self::i18n_date($key,strtotime('2000-08-01')),
  		'09' => self::i18n_date($key,strtotime('2000-09-01')),
  		'10' => self::i18n_date($key,strtotime('2000-10-01')),
  		'11' => self::i18n_date($key,strtotime('2000-11-01')),
  		'12' => self::i18n_date($key,strtotime('2000-12-01'))
	   );	
	}
	
	public static function getDaysMap()
	{
		$days = array();
		for($i = 1; $i <= 31; $i++) {
			$day = $i < 10 ? '0' . $i : $i;
			$days[$day] = $day;
		}
		return $days;

	}
	
	public static function getYearsMap()
	{
		$years = array();
		for($i = (date('Y') - 5); $i <= (date('Y') + 5); $i++)	$years[$i] = $i;
		return $years;
	}
	
	public static function getDateFromString($str)
	{
		$str = str_replace('-','',$str);
		if(is_numeric($str)) {
			$missing = (8 - strlen($str));
			if($missing > 0) {
				while($missing > 0) {$str .= "01";$missing-=2;}
			}
			return substr($str,0,4) . "-" . substr($str,4,2) . "-" . substr($str,6,2);
		}
		else {
			return date('Y-m-d');
		}
	}
	
	public static function date_info_from_ics($dtstart, $dtend)
	{
		$start_date = null;
		$end_date = null;
		$start_time = null;
		$end_time = null;
		
		$start = explode("T",$dtstart);
		$start_date = CalendarUtil::getDateFromString($start[0]);
		if(isset($start[1]))
			$start_time = substr($start[1],0,2) . ":" . substr($start[1],2,2) . ":" . "00";


		$end = explode("T",$dtend);
		$end_date = CalendarUtil::getDateFromString($end[0]);
		if(isset($end[1]))
			$end_time = substr($end[1],0,2) . ":" . substr($end[1],2,2) . ":" . "00";

		return array($start_date, $end_date, $start_time, $end_time);	
	}
	
	public static function getDateFromURL()
	{
		$params = Controller::curr()->urlParams;
		if(isset($_REQUEST['d'])) {
			return CalendarUtil::getDateFromString($_REQUEST['d']);
		}
		if(isset($params['ID'])) {
			return CalendarUtil::getDateFromString($params['ID']);
		}
		else
			return false;
	}
	
	public static function get_calendar_for($url_segment)
  {
     return $url_segment === null ? DataObject::get_one("Calendar"): DataObject::get_one("Calendar","URLSegment = '$url_segment'");
  }


 	public static function CollapseDatesAndTimes($dates)
  	{
		// Stupid thing doesn't work.
		return $dates;
		
		/*$date_list = new DataObjectSet();
		$dates = $dates->toArray();
		for($i=0; $i < sizeof($dates); $i++) {
			$original_date = $dates[$i];
			$original_date->Times = new DataObjectSet();
			$current_date = $original_date;
			$j = 0;
			while(($current_date->StartDate == $original_date->StartDate) && ($current_date->EventID == $original_date->EventID)) {
				$j++;
				$original_date->Times->push($current_date);
				if(isset($dates[$i+$j]))
					$current_date = $dates[$i+$j];
				else
					break;
			}
			$i += $j;
			$date_list->push($original_date);
		}
		return $date_list; */
  	}
  	
	public static function Microformat($date, $time, $offset = true)
	{
		if(!$date)
			return "";
			
		$ts = strtotime($date . " " . $time);

		if($ts < 1)
			return "";
			
		$ret = date('Ymd', $ts) . "T" . date('His',$ts);
		return $offset ? $ret . CalendarDateTime::$offset : $ret;
	}
	
	/**
	 * This function is used to write a date range in the common
	 * human-readable format on the front end. If the date range spans
	 * days within the month, we do not rewrte the month name. If it spans
	 * two different months, we write both month names, both days, but 
	 * do not rewrite the year, etc. Though it appears to break the MVC
	 * guidelines, it is a lot cleaner than conducting all of this logic
	 * on the frontend.
	 */
	
	static function getDateString($start_date,$end_date)
	{
		$strStartDate = null;
		$strEndDate = null;
		
		$start = strtotime($start_date);
		$end = strtotime($end_date);
		
		$start_year = date("Y", $start);
		$start_month = date("m", $start);
		
		$end_year = date("Y", $end);
		$end_month = date("m", $end);
		
		// Invalid date. Get me out of here!
		if($start < 1)
			return;
		
		// Only one day long!
		else if($start == $end || !$end || $end < 1)
			$key = self::ONE_DAY;
		
		else {
			if($start_year == $end_year)
				$key = ($start_month == $end_month) ? self::SAME_MONTH_SAME_YEAR : self::DIFF_MONTH_SAME_YEAR;
			else
				$key = self::DIFF_MONTH_DIFF_YEAR;
		}
		$date_string = self::localize($start, $end, $key);
		$break = strpos($date_string, "%{e");		
		if($break !== FALSE) {
			$strStartDate = substr($date_string, 0, $break);
			$strEndDate = substr($date_string, $break+1, strlen($date_string) - strlen($strStartDate));
			return array($strStartDate, $strEndDate);
		}

		return array($date_string, "");
	}	

	static function differenceInMonths($dateObj1,$dateObj2)
	{
		return (($dateObj1->format('Y') * 12) + $dateObj1->format('n')) - (($dateObj2->format('Y') * 12) + $dateObj2->format('n'));
	}
	
	
	function date_sort(&$data) {
			uasort($data, array("CalendarUtil","date_sort_callback"));
	}
	
	/**
	 * Callback used by column_sort
	 */
	function date_sort_callback($a, $b) {
		if($a->StartDate == $b->StartDate) {
			if($a->StartTime == $b->StartTime)
				return 0;
			else if(strtotime($a->StartTime) > strtotime($b->StartTime))
				return 1;
			else 
				return -1;
		}
		else if(strtotime($a->StartDate) > strtotime($b->StartDate))
			return 1;
		else 
			return -1;
		
	}
		
	
	

}


?>
