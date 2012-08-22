<ul class="event-calendar-quick-nav">
  <li><a href="$Link(today)"<% if CurrentAction(today) %> class="current"<% end_if %>><% _t('Calendar.QUICKNAVTODAY','Today') %></a></li>
  <li><a href="$Link(week)"<% if CurrentAction(week) %> class="current"<% end_if %>><% _t('Calendar.QUICKNAVWEEK','This week') %></a></li>
  <li><a href="$Link(month)"<% if CurrentAction(month) %> class="current"<% end_if %>><% _t('Calendar.QUICKNAVMONTH','This month') %></a></li>
  <li><a href="$Link(weekend)"<% if CurrentAction(weekend) %> class="current"<% end_if %>><% _t('Calendar.QUICKNAVWEEKEND','This weekend') %></a></li>
</ul>

<div class="event-calendar-next-prev">
  <% if IsSegment(today) %>
  <a href="$PreviousDayLink"><% _t('Calendar.PREVIOUSDAY','Previous day') %></a> | <a href="$NextDayLink"><% _t('Calendar.NEXTDAY','Next day') %></a>
  <% else_if IsSegment(week) %>
  <a href="$PreviousWeekLink"><% _t('Calendar.PREVIOUSWEEK','Previous week') %></a> | <a href="$NextWeekLink"><% _t('Calendar.NEXTWEEK','Next week') %></a>
  <% else_if IsSegment(month) %>
  <a href="$PreviousMonthLink"><% _t('Calendar.PREVIOUSMONTH','Previous month') %></a> | <a href="$NextMonthLink"><% _t('Calendar.NEXTMONTH','Next month') %></a>
  <% else_if IsSegment(weekend) %>
  <a href="$PreviousWeekendLink"><% _t('Calendar.PREVIOUSWEEKEND','Previous weekend') %></a> | <a href="$NextWeekendLink"><% _t('Calendar.NEXTWEEKEND','Next weekend') %></a>
  <% end_if %>
</div>