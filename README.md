# SilverStripe Event Calendar Module

The Event Calendar module for SilverStripe

## Features

 * **Calendar** - This Page is used to hold/present events and announcments.
 * **Calendar Event** - This page represents an event, which can have many DateTimes.
 * **Recurring Events** - Calendar Events can also be set up reoccur automatically.
 * **Calendar Announcements** - Entries in a Calendar which don't have a page associated.
 * **ICS feeds** - Add multiple ICS feeds to a Calendar to display these events in the feed.
 * **ICS output** - Download an ics file for easy importing into calendar apps.
 * **RSS feed** - RSS feed of calendar events
 * **Calendar Widget** - Display a calendar view in a Widget.
 * **Caching**

## Configuration Options

Enable jquery
```yaml
Calendar:
    jquery_included: true
```

Enable caching, and years worth of data to cache
```yaml
Calendar:
    caching_enabled: true
    cache_future_years: 2
```

Set default timezone, lang for ICS output:
```yaml
Calendar:
    timezone: America/New_York
    language: EN
```
