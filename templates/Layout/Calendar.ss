<div id="calendar-main">
	 <div id="topHeading" class="clearfix">
		<span class="feed"><a href="$Link(rss)"><% _t('SUBSCRIBE','Subscribe to the Calendar') %></a></span>
		<h2>$Title</h2>
		$Content
	</div>
<div id="dateHeader">
		<% if DateHeader %>
			<h3>$DateHeader</h3>
		<% end_if %>
</div>

$CalendarWidget


$MonthJumper

<% include QuickNav %>


<% if Events %>
<div id="events">
	<% include EventList %>
</div>
<% else %>
	<% _t('NOEVENTS','There are no events') %>.
<% end_if %>
</div>