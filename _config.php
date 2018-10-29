<?php

global $customDateTemplates;
$customDateTemplates = array(
/*
 You can modify the date display by assigning new date templates to any of the following
   date scenarios. Use the above date format keys.
   
'OneDay' 			=> '$StartMonthNameShort. $StartDayNumberShort, $StartYearLong'
'SameMonthSameYear' => '$StartMonthNameShort. $StartDayNumberShort - $EndDatNumberShort, $EndYearLong'
'DiffMonthSameYear' => '$StartMonthNameShort. $StartDayNumberShort - $EndMonthNameShort. $EndDayNumberShort, $EndYearLong'
'DiffMonthDiffYear' => '$StartMonthNameShort. $StartDayNumberShort, $StartYearLong - $EndMonthNameShort $EndDayNumberShort, $EndYearLong'

'OneDayHeader' 			=> '$StartMonthNameLong $StartDayNumberShort$StartDaySuffix, $StartYearLong'
'MonthHeader' 			=> '$StartMonthNameLong, $StartYearLong'
'YearHeader' 				=> '$StartYearLong'

*/
);

// Ensure compatibility with PHP 7.2 ("object" is a reserved word),
// with SilverStripe 3.6 (using Object) and SilverStripe 3.7 (using SS_Object)
if (!class_exists('SS_Object')) {
    class_alias('Object', 'SS_Object');
}
