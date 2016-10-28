# Description of the Syncing Process (API 2.0)

The program (Syncer) is a set of scripts which are periodically invoked. Each time the program is invoked
it tries to sync source data into its database. Then, after doing some calculations it pushes, updates or deletes events
in Google Calendar.

## Data Sources

There are 2 data sources for getting GroupEx schedules. Each source has its own data which is partially used for
creating, updating and deleting events in Google Calendar.

### ICS Data Source

ICS data source returns a list of objects in the next format:

```
  {
    "id": "43997",
    "category": "Strength",
    "location": "Blaisdell ",
    "title": "BodyPumpÂ®",
    "description": "\r\n\tBodyPump&trade; is the revolutionary barbell workout. .&nbsp;&nbsp;\r\n",
    "post_date": "2012-05-25 14:04:21",
    "start_date": "2011-01-07 16:00:00",
    "end_date": "2011-01-07 17:00:00",
    "recurring": "weekly",
    "parent_id": null,
    "instructor": "Sarah Masten"
  },
```

The program treats that source as the most generic and canonical data source. All items are saved in the database of
the program. Periodically new items appear on the list and The program saves them. Each time the program synchronises
this source with the database it compares the number of items in the database and in the source. Extra items in the
database are treated as deleted events. The program deletes them from the database.

The most important data from ICS is `recurring` field. The program uses it to set recurrence while creating new Google
events.

### Schedules Data Source

This data source gives the program actual schedules with actual data. Each item in the list is treated as specific
class on specific date.

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

The program maps Schedules Data and ICS data using class IDs. The ICS items are treated as `parent` entities and
Schedules items are treated as `child` entities.

When the program gets in the database `parent` item and at least one `child` item a new Google event is created (pushed)
to Google Calendar.

Each time the program gets a new `child` item it tries to push it as Google event instance. Instance is a Google event
with specific date (it's applicable only for recurring events) and possibly data different data.

## Google Calendar Events Handling

Here is a description of the process which creates new events, updates and deletes them.

### Crete new event

When the program gets new ICS item (`parent`) and at list one Schedule item (`child`) it pushes new Google event to the
calendar. If the ICS event has recurrence field it is used for setting recurrence. If there is no recurrence field the
program creates a single day event.

### Update event

The program iterates over each day in the next month and gets new Schedules items. When a new schedule item is saved
to the database the program tries to get appropriate instance for it from Google Calendar and updates its data with
fetched Schedule item (`child`) data.

### Delete event (with all instances)

Currently the program handles only deleting of `parent` events (with all its children). When ICS event disappears from
ICS data source the program treat it as deleted event and deletes it. Then the program deletes Google Event with all its
instances. All `child` items (Schedules items) are also deleted from the database. **Note**, before deleting the event
the program double checks whether the event description in ICS data source is absent.

### Delete single instance of recurring event

@todo The old (API 1.0 code) should be refactored and deployed.

### Exceptions

Unfortunately, the program periodically catches some exceptions. For some of them there are workaround. For some of them
there is still no solution.

#### Instance not found exception (partially handled)

Sometimes the program gets a schedules items (`children`) for `parent` entities which has no recurrence field.
The program treats this events as single day events. So, when the program tries to get the instance from Google API it
receives exception `Instance not found`. In this case the program tries to find `linked (parent)` ICS item
(by using `parent_id` on ICS data source). If the `linked (parent)` item has recurrence and it's pushed to Google
the program uses its Google event to get appropriate instance and update its data.

##### Parent ICS entity not found (not handled)

Sometimes the program can't find parent ICS entity using `parent_id` field. In this situation the program can't update
received schedule item.

##### Parent ICS entity still not pushed (not handled)

As described before, each Google event is created when the program get ICS item (`parent`) and corresponding Schedule
item (`child`). Sometimes, when the program tries to find parent ICS item to get recurrence it finds that the item is 
not pushed. So, the instances can't be fetched. In this situation the program can't update received schedule item.
