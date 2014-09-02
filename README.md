# SilverStripe Event Calendar Module

The Event Calendar module for SilverStripe

## Features

 * *Calendar Page*
 * *Calendar Event Pages*
 * *Recurring events* - (and exceptions)
 * *Calendar Announcements* - calendar entries with no associated page.
 * *Add ICS feeds* to calendar
 * *ICS output* - download an ics file for easy importing into calendar apps.
 * *RSS feed of Events*
 * *Calendar Widget*
 * *Caching*

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
