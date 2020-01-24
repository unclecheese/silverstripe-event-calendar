<ul class="event-calendar-quick-nav">
    <li><a href="$Link(today)"<% if $CurrentAction('today') %> class="current"<% end_if %>><% _t('UncleCheese\EventCalendar\Pages\Calendar.QUICKNAVTODAY','Today') %></a></li>
    <li><a href="$Link(week)"<% if $CurrentAction('week') %> class="current"<% end_if %>><% _t('UncleCheese\EventCalendar\Pages\Calendar.QUICKNAVWEEK','This week') %></a></li>
    <li><a href="$Link(month)"<% if $CurrentAction('month') %> class="current"<% end_if %>><% _t('UncleCheese\EventCalendar\Pages\Calendar.QUICKNAVMONTH','This month') %></a></li>
    <li><a href="$Link(weekend)"<% if $CurrentAction('weekend') %> class="current"<% end_if %>><% _t('UncleCheese\EventCalendar\Pages\Calendar.QUICKNAVWEEKEND','This weekend') %></a></li>
</ul>

<div class="event-calendar-next-prev">
    <% if $IsSegment('today') %>
        <a href="$PreviousDayLink"><% _t('UncleCheese\EventCalendar\Pages\Calendar.PREVIOUSDAY','Previous day') %></a> | <a href="$NextDayLink"><% _t('UncleCheese\EventCalendar\Pages\Calendar.NEXTDAY','Next day') %></a>
    <% else_if $IsSegment('week') %>
        <a href="$PreviousWeekLink"><% _t('UncleCheese\EventCalendar\Pages\Calendar.PREVIOUSWEEK','Previous week') %></a> | <a href="$NextWeekLink"><% _t('UncleCheese\EventCalendar\Pages\Calendar.NEXTWEEK','Next week') %></a>
    <% else_if $IsSegment('month') %>
        <a href="$PreviousMonthLink"><% _t('UncleCheese\EventCalendar\Pages\Calendar.PREVIOUSMONTH','Previous month') %></a> | <a href="$NextMonthLink"><% _t('UncleCheese\EventCalendar\Pages\Calendar.NEXTMONTH','Next month') %></a>
    <% else_if $IsSegment('weekend') %>
        <a href="$PreviousWeekendLink"><% _t('UncleCheese\EventCalendar\Pages\Calendar.PREVIOUSWEEKEND','Previous weekend') %></a> | <a href="$NextWeekendLink"><% _t('UncleCheese\EventCalendar\Pages\Calendar.NEXTWEEKEND','Next weekend') %></a>
    <% end_if %>
</div>