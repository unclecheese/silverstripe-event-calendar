<ul>
<% loop Events %>
<li class="vevent clearfix">
  <h3 class="summary"><% if Announcement %>$Event.Title<% else %><a class="url" href="$Link">$Event.Title</a><% end_if %></h3>
  <p class="dates">$DateRange <% if AllDay %><% _t('ALLDAY','All Day') %><% else %><% if StartTime %>$TimeRange<% end_if %><% end_if %></p>
  <p><a href="$ICSLink"><% _t('ADD','Add this to Calendar') %></a></p>
  <% if Announcement %>
  $Content
  <% else %>
  <% with Event %>$Content.LimitWordCount(60)<% end_with %> <a href="$Link"><% _t('MORE','Read more&hellip;') %></a>
  <% end_if %>
  <% if OtherDates %>
  <div class="event-calendar-other-dates">
    <h4><% _t('Calendar.ADDITIONALDATES','Additional Dates for this Event') %>:</h4>
    <ul>
      <% loop OtherDates %>
      <li><a href="$Link" title="$Event.Title">$DateRange <% if StartTime %> $TimeRange<% end_if %></a></li>
      <% end_loop %>
    </ul>
  </div>
  <% end_if %>
</li>
<% end_loop %>
</ul>
<% if MoreEvents %>
<a href="$MoreLink" class="calendar-view-more"><% _t('Calendar.VIEWMOREEVENTS','View more events...') %></a>
<% end_if %>