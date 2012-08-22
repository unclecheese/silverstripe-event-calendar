$Events.count
	<% loop Events %>
		<div class="vevent clearfix">
			<div class="dates">$DateRange</div>
			<div class="details">
				<h3 class="summary"><% if Announcement %>$Event.Title<% else %><a href="$Link">$Event.Title</a><% end_if %></h3>
				<dl>
				<% if AllDay %>
					<dt><% _t('ALLDAY','All Day') %></dt>
				<% else %>
					<% if StartTime %>
					<dt><% _t('TIME','Time') %>:&nbsp;</dt>
							<dd>$TimeRange</dd>
					<% end_if %>
				<% end_if %>
				</dl>
					<div class="description">
							<% if Announcement %>
								$Content
							<% else %>
								<% with Event %>$Content.LimitWordCount(60)<% end_with %> <a href="$Link"><% _t('MORE','more...') %></a>
							<% end_if %>
							<% if OtherDates %>
							<h4><% _t('SEEALSO','See also') %>:</h4>
							<ul>
							<% loop OtherDates %>
						 		<li><a href="$Link" title="$Event.Title">$DateRange</a>
									<% if StartTime %>
										<ul>
											<li>$TimeRange</li>
										</ul>
									<% end_if %>
								</li>
							<% end_loop %>
							</ul>
							<% end_if %>
					</div>
			</div>
			<ul class="utility">
				<li><a class="btn add" href="$ICSLink"><% _t('ADD','Add to Calendar') %></a></li>
			</ul>
		</div>
	<% end_loop %>
	<% if MoreEvents %>
		<a href="$MoreLink" class="calendar-view-more"><% _t('Calendar.VIEWMOREEVENTS','View more...') %></a>
	<% end_if %>
