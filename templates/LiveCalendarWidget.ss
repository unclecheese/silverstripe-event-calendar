<% require javascript(event_calendar/javascript/jquery-1.2.6.min.js) %>
<% require javascript(event_calendar/javascript/live_calendar_widget.js) %>
<% require css(event_calendar/css/live_calendar_widget.css) %>
<% if Ajax %><% else %><div id="live-calendar-widget"><% end_if %>
<div id="live-calendar-widget-wrap">
<table class="calendar" cellspacing="0" cellpadding="0" border="0">
	<thead>
		<tr class="calendarHeader">
			<td colspan="7" class="calendarMonthName">
				<a class="month-nav" href="$PrevMonthLink">&laquo;</a>
				&nbsp;
				<select id="live-calendar-widget-navigator">		
				  <optgroup label="<% _t('JUMPTOMONTH','Jump to...') %>" />		    
          <% control NavigationOptions %>
            <option value="$Link" $Selected>$Month</option>
          <% end_control %>
 				  <optgroup label="<% _t('QUICKLINKS','Quick Links') %>" />
 				  <option value="$QuickMonthLink">This month</option>
 				  <option value="$QuickWeekLink">This week</option>
 				  <option value="$QuickWeekendLink">This weekend</option>
				</select>
				&nbsp;
				<a class="month-nav" href="$NextMonthLink">&raquo;</a>
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="calendarDayName">$Sun</td>
			<td class="calendarDayName">$Mon</td>
			<td class="calendarDayName">$Tue</td>
			<td class="calendarDayName">$Wed</td>
			<td class="calendarDayName">$Thu</td>
			<td class="calendarDayName">$Fri</td>
			<td class="calendarDayName">$Sat</td>
			<td>&nbsp;</td>
		</tr>
	</thead>
	<tbody>
  <% control Weeks %>
		<tr>
		<% control Days %>
			<td class="$Today $OutOfMonth $CurrentDay $HasEvent">
			 <a href="$ShowDayLink">$Number</a>
			</td>
    <% end_control %>
			<td class="showWeek">
        <a title="<% _t('SHOWWEEK','Show week') %>" href="$ShowWeekLink">&laquo;</a>
			</td>
		</tr>
  <% end_control %>
	</tbody>
	<tfoot>
	 <tr>
	   <td colspan="7"><a href="$CurrentMonthLink">Show month</a></td>
	   <td>&nbsp;</td>
	 </tr>
	</tfoot>
</table>
</div>
<% if Ajax %><% else %></div><% end_if %>