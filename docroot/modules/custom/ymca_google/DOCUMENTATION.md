# Description of the Syncing Process (API 2.0)

The program (Syncer) is a set of scripts which are run with `cron` every 7-10 minutes. Each time the program is invoked it tries to sync source data into its database. Then, after doing some calculations it pushes, updates or deletes events in Google Calendar.

## Data Sources

We have 2 sources for getting GroupEx classes. Each source has its own data which is partially used for creating, updating and deleting events in Google Calendar.

### ICS Data Source

ICS source returns list of objects in the next format

```
  {
    "id": "43997",
    "category": "Strength",
    "location": "Blaisdell ",
    "title": "BodyPumpÂ®",
    "description": "\r\n\tBodyPump&trade; is the revolutionary barbell workout. Challenge all major muscle groups with squats, presses, lifts and curls as you strengthen, tone and define your entire body. Determine how hard you want to work by choosing the appropriate weights. Level: All. Free drop-in class for Members.&nbsp;&nbsp;\r\n",
    "post_date": "2012-05-25 14:04:21",
    "start_date": "2011-01-07 16:00:00",
    "end_date": "2011-01-07 17:00:00",
    "recurring": "weekly",
    "parent_id": null,
    "instructor": "Sarah Masten"
  },
```

The program treats that data as a most generic and canonical data source. All items are saved in the database. Periodically new items appear on the list. The program saves them. Each time the program synchronises this source with the database it compares the number of items in the database and in the source. Extra items in the database are treated as deleted events. The program deletes them from the database.

The most important data from ICS is `recurring` field. The program uses it to set recurrence while creating new Google events.

### Schedules Data Source

This data source gives the program actual classes with actual data. Actual Google Events are created from the data fetched from this source.

Example format:

```
  {
    "date": "Sunday, October 9, 2016",
    "time": "8:15am-9:15am",
    "title": "Group Cycle",
    "studio": "Studio 1",
    "category": "Cardio ",
    "instructor": "Laura G",
    "original_instructor": "Laura G",
    "sub_instructor": "",
    "length": "60",
    "location": "Andover",
    "id": "9722864"
  },
```

The program maps Schedules Data and ICS data by class IDs. ICS items are treated as `parent` entities and Schedules items are treated as `child` entities.

When the program gets in the database `parent` item and at least one `child` item a new Google event is created (pushed) to Google Calendar.

Each time the program gets a new `child` item it tries to push it as Google event instance. Instance - is a Google event with specific date (it's applicable only for recurring events).

## Google Calendar Events Handling

Here is a description of the process which creates new events, updates and deletes them.

### Crete new event

When the program gets new ICS item (`parent`) and at list one Schedule item (`child`) it pushes new Google event to the calendar. If the ICS event has recurrence field it is used for setting recurrence. If there is no recurrence field the program creates single day event.

### Update event

The program iterates over each day in the next month and gets new Schedules items. When a new schedule item is saved to the database the program tries to get appropriate instance for it from Google Calendar and updates its data with fetched Schedule item (`child`) data.

### Delete event

Currently the program handles only deleting of `parent` events (with all its children). When ICS event disappears from ICS data source the program treat it as deleted event and deletes it. Then the program deletes Google Event with all its instances. All `child` items (Schedules items) are also deleted from the database.

