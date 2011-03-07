<% require css(event_calendar/css/calendar.css) %>
<% require javascript(event_calendar/javascript/calendar_core.js) %>

<div id="primaryContent" class="clearfix">
    <div class="innerpad">
		<div id="calendar-sidebar">
			<h3><% _t('BROWSECALENDAR','Browse the Calendar') %></h3>
			<div id="monthNav">
				<p><% _t('USECALENDAR','Use the calendar below to navigate dates') %></p>
				$CalendarWidget
				<h4><% _t('FILTERCALENDAR','Fitler calendar') %>:</h4>
				$CalendarFilterForm
			</div>
		</div>
		<div id="calendar-main">
			<div id="topHeading" class="clearfix">
				<span class="back"><a href="$CalendarBackLink"><% _t('BACKTO','Back to') %> $Parent.Title</a></span>
				<span class="feed"><a href="$RSSLink"><% _t('SUBSCRIBE','Subscribe to the Calendar') %></a></span>
				<h2>$Parent.Title</h2>
			</div>
			
			<% if Level(2) %>
				<% include BreadCrumbs %>
			<% end_if %>
			<div class="vevent">
				<% if OtherDates %>
				<div id="additionalDates">
					<h4><% _t('ADDITIONALDATES','Additional Dates') %></h4>
					<dl class="date clearfix">
					<% control OtherDates %>
						<dt><a href="$Link" title="$Event.Title">$_Dates</a></dt>
							
					<% end_control %> 
					</dl>
				</div>
				<% end_if %>
				<h3 class="summary">$Title</h3>
				
				<% control CurrentDate %>
				<h4><a href="$ICSLink" title="Add to Calendar">$_Dates</a></h4>
				
				<% if StartTime %>
				<ul id="times">
					<li>$_Times</li>	
				</ul>
				<% end_if %>		
				<% end_control %>
				
				$Content
				$Form
				$PageComments
			</div>
		</div>
	</div>
</div>