(function($) {
$(function() {

var calendar_url = $('.calendar-widget').data('url');
var loaded_months = {};

function loadMonthJson(month, year) {
	$.ajax({
		url: calendar_url+"monthjson/"+year+month,
		async: false,
		dataType: 'json',
		success: function(data) {
			json = data;
			loaded_months[year+month] = data;			
		}
	});	
}


function applyMonthJson(month, year) {	
	json = loaded_months[year+month];
	for(date in json) {
		if(json[date].events.length) {
			$('[data-date="'+date+'"]').addClass('hasEvent').attr('title', json[date].events.join("\n"));

		}
	}			
}


function setSelection(calendar) {
	var $e = $(calendar.getContainer());
	if($e.data('start') && $e.data('end')) {
		var starts = $e.data('start').split("-");
		var ends = $e.data('end').split("-");
		var startDate = new Date(starts[0], Number(starts[1])-1, Number(starts[2]));
		var endDate = new Date(ends[0], Number(ends[1])-1, Number(ends[2]));
		var startTime = startDate.getTime();
		var endTime = endDate.getTime()+86400000;
		
		for(loopTime = startTime; loopTime < endTime; loopTime += 86400000) {
		    var loopDay=new Date(loopTime)
		    var stamp = loopDay.getFullYear()+"-"+calendar.pad(loopDay.getMonth()+1)+"-"+calendar.pad(loopDay.getDate());		    
		    $('.calendar-day[data-date="'+stamp+'"]').addClass('selected');
		    
		}		
	}

}

$('.calendar-widget').each(function() {
	var opts = {
		onShowDay: function(date) {
			window.location = calendar_url+"show/"+date;		
		},
		onShowWeek: function(start, end) {
			window.location = calendar_url+"show/"+start+"/"+end;
		},
		onShowMonth: function(start, end) {
			window.location = calendar_url+"show/"+start+"/"+end;		
		},

		onMonthChange: function(month, year, calendar) {
			var json;
			m = calendar.pad(month);		
			if(!loaded_months[year+m]) {
				loadMonthJson(m, year);
			}
			json = loaded_months[year+m];
			applyMonthJson(m, year);
			setSelection(calendar);
		},

		onInit: function(calendar) {
			previous = calendar.getPrevMonthYear();		
			next = calendar.getNextMonthYear();

			this_month = calendar.getPaddedMonth();
			this_year = calendar.year;
			prev_month = calendar.pad(previous[0]+1);
			next_month = calendar.pad(next[0]+1);
			prev_year = previous[1];
			next_year = next[1];

			loadMonthJson(
				this_month,
				this_year
			);

			loadMonthJson(
				prev_month,
				prev_year
			);

			loadMonthJson(
				next_month,
				next_year
			);
			applyMonthJson(this_month, this_year);
			applyMonthJson(prev_month, prev_year);
			applyMonthJson(next_month, next_year);

			setSelection(calendar);
			
		}

	};	

	if($(this).data('start')) {		
		var parts = $(this).data('start').split('-');				
		opts.month = Number(parts[1])-1;
		opts.year = parts[0];

		
	}
	$(this).CalendarWidget(opts);
	
})

});
})(jQuery);