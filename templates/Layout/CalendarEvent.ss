<div id="calendar-main">
	 <div id="topHeading" class="clearfix">
		<span class="back"><a href="$Parent.Link"><% _t('CalendarEvent.BACKTO','Back to') %> $Parent.Title</a></span>
		<span class="feed"><a href="$Parent.Link(rss)"><% _t('CalendarEvent.SUBSCRIBE','Subscribe to the Calendar') %></a></span>
		<h2>$Parent.Title</h2>
	</div>
	<div id="dateHeader">			
				<h3>$Title</h3>
	</div>
	<% if Level(2) %>
		<% include BreadCrumbs %>
	<% end_if %>
	<div class="vevent">
		<% if OtherDates %>
		<div id="additionalDates">
			<h4><% _t('CalendarEvent.ADDITIONALDATES','Additional Dates') %></h4>
			<dl class="date clearfix">
			<% loop OtherDates %>
				<dt><a href="$Link" title="$Event.Title">$DateRange</a></dt>							
			<% end_loop %> 
			</dl>
		</div>
		<% end_if %>
		<h3 class="summary">$Title</h3>
		
		<% with CurrentDate %>				
		<h4>add: <a href="$ICSLink" title="<% _t('CalendarEvent.ADD','Add to Calendar') %>">$DateRange</a></h4>
		<% if StartTime %>
		<ul id="times">
			<li>$TimeRange</li>	
		</ul>
		<% end_if %>		
		<% end_with %>
		
		$Content
		$Form
		$PageComments
	</div>
</div>
