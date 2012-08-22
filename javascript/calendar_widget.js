(function($){

  	var CalendarWidget = function() {
  		this.settings = {};
  		if($.CalendarWidget) {
    		this.settings = $.CalendarWidget.settings;
    	}    	
    }

    $.extend(CalendarWidget.prototype, {

    	attachTo: function(element) {
    		this.element = element;
    	},


    	getContainer: function() {
    		return this.element;
    	},


		setMonth: function(month, year) {
    		this.month = month;
    		this.year = year;
			this.firstDay = new Date(this.year, this.month, 1);
			this.startingDay = this.firstDay.getDay();
			if($.CalendarWidget.settings.startOnMonday) {
				this.startingDay--;
			}			
			this.monthLength = this.settings.calDaysInMonth[this.month];
			// compensate for leap year
			if (this.month == 1) { // February only!
				if((this.year % 4 == 0 && this.year % 100 != 0) || this.year % 400 == 0){
					this.monthLength = 29;
				}
			}
			
			html = this.generateHTML();
			$(this.element).html(html);			

			var calendar = this;

			$('.prev', this.element).click(function() {
				calendar.previousMonth();
			});


			$('.next', this.element).click(function() {
				calendar.nextMonth();
			});


			$('.calendar-day',this.element).click(function() {
				calendar.showDay(this);
			});


			$('.show-week', this.element).click(function() {
				calendar.showWeek(this);
			});


			$('.show-month',this.element).click(function() {
				calendar.showMonth(this);
			});
		},



		getPaddedMonth: function() {
			return this.pad(this.month+1);
		},



		setOptions: function(options) {
			$.extend(this.settings, options);
		},



    	getNextMonthYear: function() {
			var m = this.month+1;
			var y = this.year;
			if(m > 11) {
				m = 0;
				y++;
			}
			return [m, y];    		
    	},



    	getPrevMonthYear: function() {
			var m = this.month-1;
			var y = this.year;
			if(m < 0) {
				m = 11;
				y--;
			}
			return [m, y];    		
    	},



    	previousMonth: function() {
    		result = this.getPrevMonthYear();
			this.setMonth(result[0], result[1]);
			this.settings.onMonthChange(result[0]+1, result[1], this);
		},



    	nextMonth: function() {    		
    		result = this.getNextMonthYear();
			this.setMonth(result[0], result[1]);
			this.settings.onMonthChange(result[0]+1, result[1], this);
		},



		showDay: function(element) {
			this.settings.onShowDay($(element).data('date'), this);
		},



		showWeek: function(element) {
			var start = $(element).closest('tr').find('td').eq(0);
			var end = $(element).closest('tr').find('td').eq(6);
			this.settings.onShowWeek(start.data('date'), end.data('date'), this);			
		},



		showMonth: function(element) {
			month = this.getPaddedMonth();
			start = this.year + '-' + month + '-01';
			end = this.year + '-' + month + '-' + this.monthLength;
			this.settings.onShowMonth(start, end, this);
		},


		generateHTML: function() {
			// do the header
			var monthName = this.settings.calMonthsLabels[this.month]			
			var html = '<table class="calendar-widget-table">';
			html += '<thead>';
			html += '<tr><th colspan="8"><a class="prev" href="javascript:void(0);"> &laquo; </a>';
			html +=  '<a href="javascript:void(0);" class="show-month">'+monthName + "&nbsp;" + this.year + '</a>';
			html += '<a class="next" href="javascript:void(0);"> &raquo; </a></th></tr>';
			html += '</thead>';
			html += '<tbody>';
			html += '<tr class="calendar-header">';
			for(var i = 0; i <= 6; i++ ){
				html += '<td class="calendar-header-day">';
				html += this.settings.calDaysLabels[i];
				html += '</td>';
			}
			html += "<td>&nbsp;</td>"
			html += '</tr><tr>';

			var cell = 1;
			var empties = 0;
			var row_count = Math.ceil(this.monthLength/7);
			for (var i = 0; i < row_count; i++) {
				for (var j = 0; j <= 6; j++) {
					date = "";
					diff = cell-this.startingDay;
					out_of_month = false;
					if(diff < 1) {
						out_of_month = true;
						r = this.getPrevMonthYear();
						m = r[0];
						y = r[1];
						days = this.settings.calDaysInMonth[m];
						day = days + diff;					
						empties++;
					}
					else {
						day = cell-empties;
						m = this.month;
						y = this.year;
					}
					if(!out_of_month && day > this.monthLength) {
						out_of_month = true;					
						r = this.getNextMonthYear();						
						m = r[0];
						y = r[1];
						days = this.settings.calDaysInMonth[m];
						day = cell-empties-this.monthLength;
					}
					today = new Date();
					if(today.getFullYear() == this.year && today.getMonth() == m && today.getDate() == day) {
						klass = "today";
					}
					else {
						klass = out_of_month ? "out-of-month" : "in-month";
					}
					d = this.pad(day);
					m = this.pad(m+1);
					date = y+'-'+m+'-'+d;
					html += '<td data-date="'+date+'" class="calendar-day '+klass+'">'+day+'</td>';
					cell++;
					
				}
				html += "<td class='show-week'>&laquo;</td>";
				html += '</tr><tr>';
			}
			html += '</tr></tbody></table>';
			return html;
		},

		pad: function(num) {
			str = new String(num);
			return (str.length == 1) ? "0"+str : str;
		}


	});



  	$.fn.extend({
  		CalendarWidget: function(options) {
	    	return this.each(function() {
	    		var c = new CalendarWidget();
	    		c.setOptions(options);
	    		c.attachTo(this);
				var currentDate = new Date();
		    	var year = options.year ? options.year : currentDate.getFullYear();
		    	var month = options.month ? options.month : currentDate.getMonth();
		    	c.setMonth(month, year);
		    	c.settings.onInit(c);
    		});
  		}
    });

  	// Create the singleton for global settings
    $.CalendarWidget = new CalendarWidget();
    $.CalendarWidget.setOptions({
		onShowDay: function(date) {},
		onShowWeek: function(start, end) {},
		onShowMonth: function(start, end) {},
		onMonthChange: function(month, year) {},
		onInit: function(calendar) {},
		startOnMonday: false,
		calDaysInMonth: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
		calDaysLabels: [],
		calMonthsLabels: []
    });

})( jQuery );
