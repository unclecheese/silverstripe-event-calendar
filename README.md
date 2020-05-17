# Silverstripe Event Calendar

## Introduction

This is an event calendar module for Silverstripe CMS, with the following features:

* **Calendar** - A page type used to hold/present events and announcements.
* **Calendar event** - A page type which represents an event, with one or more DateTimes (an instance of an event).
* **Recurring events** - Calendar events can be set up reoccur automatically.
* **Calendar announcements** - Entries in a calendar which don't have an event page associated.
* **ICS feeds** - Add external ICS feeds to a calendar to display these events.
* **ICS output** - Download an ics file for easy importing into calendar apps.
* **RSS feed** - RSS feed of calendar events.
* **Calendar widget** - Display a calendar view in a widget, so website users can select to view events by year/month/week/day periods.
* **Caching**

## Requirements

Silverstripe CMS 4.4 or greater

Carbon ( version 1 - https://github.com/briannesbitt/carbon )

## Configuration Options

Enable jQuery (that is, do not request a local copy)

```yaml
UncleCheese\EventCalendar\Pages\Calendar:
    jquery_included: true
```

Caching options

```yaml
UncleCheese\EventCalendar\Pages\Calendar:
    caching_enabled: true
    cache_future_years: 2
```

Set default time zone and language for ICS output

```yaml
UncleCheese\EventCalendar\Pages\Calendar:
    timezone: America/New_York
    language: EN
```


