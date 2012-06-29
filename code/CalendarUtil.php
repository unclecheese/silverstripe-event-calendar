<?php

class CalendarUtil {

	const ONE_DAY = "OneDay";


	const SAME_MONTH_SAME_YEAR = "SameMonthSameYear";


	const DIFF_MONTH_SAME_YEAR = "DiffMonthSameYear";


	const DIFF_MONTH_DIFF_YEAR = "DiffMonthDiffYear";
	

	const ONE_DAY_HEADER = "OneDayHeader";


	const MONTH_HEADER = "MonthHeader";


	const YEAR_HEADER = "YearHeader";



	static $format_character_placeholders = array(
		'$StartDayNameShort',
		'$StartDayNameLong',
		'$StartDayNumberShort',
		'$StartDayNumberLong',
		'$StartDaySuffix',
		'$StartMonthNumberShort',
		'$StartMonthNumberLong',
		'$StartMonthNameShort',
		'$StartMonthNameLong',
		'$StartYearShort',
		'$StartYearLong',
		'$EndDayNameShort',
		'$EndDayNameLong',
		'$EndDayNumberShort',
		'$EndDayNumberLong',
		'$EndDaySuffix',
		'$EndMonthNumberShort',
		'$EndMonthNumberLong',
		'$EndMonthNameShort',
		'$EndMonthNameLong',
		'$EndYearShort',
		'$EndYearLong'
	);



	public static function format_character_replacements($start, $end) {
		return array(
			strftime('%a', $start),
			strftime('%A', $start),
			date ('j', $start),
			date ('d', $start),
			date ('S', $start),
			date ('n', $start),
			date ('m', $start),
			strftime('%b', $start), 
			strftime('%B', $start), 
			date ('y', $start), 
			date ('Y', $start),

			strftime('%a', $end),
			strftime('%A', $end),
			date ('j', $end),
			date ('d', $end),
			date ('S', $end),
			date ('n', $end),
			date ('m', $end),
			strftime('%b', $end), 
			strftime('%B', $end), 
			date ('y', $end), 
			date ('Y', $end),

		);	
	}



	public static function localize($start, $end, $key) {
		global $customDateTemplates;
		if(is_array($customDateTemplates) && isset($customDateTemplates[$key]))
			$template = $customDateTemplates[$key];
		else {
			$template = _t("Calendar.$key"); 
		}
		
		return str_replace(self::$format_character_placeholders, self::format_character_replacements($start,$end), $template);		
	}	



	public static function get_date_from_string($str) {
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



	static function get_date_string($start_date,$end_date) {
		$strStartDate = null;
		$strEndDate = null;
		
		$start = strtotime($start_date);
		$end = strtotime($end_date);
		
		$start_year = date("Y", $start);
		$start_month = date("m", $start);
		
		$end_year = date("Y", $end);
		$end_month = date("m", $end);
		
		// Invalid date. Get me out of here!
		if($start < 1)	return;

		// Only one day long!
		else if($start == $end || !$end || $end < 1) {
			$key = self::ONE_DAY;
		}
		
		else {
			if($start_year == $end_year) {
				$key = ($start_month == $end_month) ? self::SAME_MONTH_SAME_YEAR : self::DIFF_MONTH_SAME_YEAR;
			}
			else {
				$key = self::DIFF_MONTH_DIFF_YEAR;
			}
		}
		$date_string = self::localize($start, $end, $key);		
		$break = strpos($date_string, '$End');		
		if($break !== FALSE) {
			$strStartDate = substr($date_string, 0, $break);
			$strEndDate = substr($date_string, $break+1, strlen($date_string) - strlen($strStartDate));
			return array($strStartDate, $strEndDate);
		}

		return array($date_string, "");
	}



	public static function microformat($date, $time, $offset = true) {
		if(!$date)
			return "";
			
		$ts = strtotime($date . " " . $time);

		if($ts < 1)
			return "";
			
		$ret = date('Ymd', $ts) . "T" . date('His',$ts);
		return $offset ? $ret . $offset : $ret;
	}



	public static function get_months_map($key = '%b') {
    	return array (
	  		'01' => strftime($key,strtotime('2000-01-01')),
	  		'02' => strftime($key,strtotime('2000-02-01')),
	  		'03' => strftime($key,strtotime('2000-03-01')),
	  		'04' => strftime($key,strtotime('2000-04-01')),
	  		'05' => strftime($key,strtotime('2000-05-01')),
	  		'06' => strftime($key,strtotime('2000-06-01')),
	  		'07' => strftime($key,strtotime('2000-07-01')),
	  		'08' => strftime($key,strtotime('2000-08-01')),
	  		'09' => strftime($key,strtotime('2000-09-01')),
	  		'10' => strftime($key,strtotime('2000-10-01')),
	  		'11' => strftime($key,strtotime('2000-11-01')),
	  		'12' => strftime($key,strtotime('2000-12-01'))
	   );	
	}


	public static function get_date_format() {
		if(CalendarDateTime::$date_format_override) {
			return CalendarDateTime::$date_format_override;
		}
		return _t('CalendarDateTime.DATEFORMAT','mdy');
	}



	public static function get_time_format() {
		if(CalendarDateTime::$time_format_override) {
			return CalendarDateTime::$time_format_override;
		}
		return _t('CalendarDateTime.TIMEFORMAT','24');
	}



	public static function get_first_day_of_week() {
		$result = strtolower(_t('CalendarDateTime.FIRSTDAYOFWEEK','monday'));
		return ($result == "monday") ? sfTime::MONDAY : sfTime::SUNDAY;
	}



	public static function date_sort(&$data) {
			uasort($data, array("CalendarUtil","date_sort_callback"));
	}
	
	/**
	 * Callback used by column_sort
	 */
	public static function date_sort_callback($a, $b) {
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
