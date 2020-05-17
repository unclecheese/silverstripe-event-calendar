
<h2>$Title</h2>

<p class="event-calendar-feed"><a href="$Link(rss)"><% _t('UncleCheese\EventCalendar\Pages\Calendar.SUBSCRIBE', 'Calendar RSS Feed') %></a></p>

$Content

<div class="event-calendar-controls">
	$CalendarWidget
	<% include UncleCheese\EventCalendar\Includes\MonthJumper %>
	<% include UncleCheese\EventCalendar\Includes\QuickNav %>
</div>

<h2 class="event-calendar-dateheader">$DateHeader</h2>
<% if $Events %>
	<div id="event-calendar-events" class="event-calendar-events-list">
		<% include UncleCheese\EventCalendar\Includes\EventList %>
	</div>
<% else %>
	<p><% _t('UncleCheese\EventCalendar\Pages\Calendar.NOEVENTS','There are no events') %>.</p>
<% end_if %>