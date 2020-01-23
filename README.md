# Silverstripe event calendar module

An event calendar module for Silverstripe

## Features

* **Calendar** - Page type used to hold/present events and announcements.
* **Calendar Event** - Page type which represents an event, with one or more DateTimes.
* **Recurring Events** - Calendar Events can also be set up reoccur automatically.
* **Calendar Announcements** - Entries in a calendar which don't have an event page associated.
* **ICS feeds** - Add multiple ICS feeds to a calendar to display these events in the feed.
* **ICS output** - Download an ics file for easy importing into calendar apps.
* **RSS feed** - RSS feed of calendar events.
* **Calendar Widget** - Display a calendar view in a widget.
* **Caching**

## Configuration Options

Enable jQuery

```yaml
UncleCheese\EventCalendar\Pages\Calendar:
    jquery_included: true
```

Enable caching, and years worth of data to cache

```yaml
UncleCheese\EventCalendar\Pages\Calendar:
    caching_enabled: true
    cache_future_years: 2
```

Set default time zone and language for ICS output:

```yaml
UncleCheese\EventCalendar\Pages\Calendar:
    timezone: America/New_York
    language: EN
```
