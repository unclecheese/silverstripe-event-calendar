jQuery(function()
{
	jQuery('#calendar-widget')
		.datePicker({inline:true, startDate : '01/01/1990', endDate: '01/01/2999', month : month_view, year : year_view, rangeStart : start_date, rangeEnd: end_date})
		.bind(
			'dateSelected',
			function(e, selectedDate, $td)
			{
				navigateToDate($td.attr('title'));
			}
		).bind(
			'weekSelected',
			function(e, selectedDate, $td)
			{
				navigateToDate($td.attr('title'));
				
			}
		).bind(
			'monthSelected',
			function(cal, obj) {navigateToDate(obj.displayedYear.toString() + zeroPad(obj.displayedMonth+1).toString())}
		);
		
}
);