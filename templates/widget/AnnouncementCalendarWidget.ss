<div class="widget_upcomming_announcements">
	<% loop $Events %> 
		<ul>
			<li>
				<span>$DateRange</span><br/> 
				<a href="$Calendar.Link"><% if Announcement %>$Title<% else %>$Event.Title<% end_if %></a>
				<hr/>
			</li>
		</ul>
	<% end_loop %>
	<div class="more">
		<a href="$Calendar.Link">meer...</a>
	</div>
</div>
