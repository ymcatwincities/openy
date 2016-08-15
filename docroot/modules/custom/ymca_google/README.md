# Gcal Groupex Syncer

Syncs Groupex schedules with Google calendar.

### How to run

```
ymca_sync_run("ymca_sync.groupex_gcal", "proceed");
```

### How to rebuild the schedule

```
\Drupal::state()->delete('ymca_google_syncer_schedule');
```

### How to update the schedule

```
$state = \Drupal::state();
$schedule = $state->get('ymca_google_syncer_schedule');
$schedule['current'] = 179;
$state->set('ymca_google_syncer_schedule', $schedule);
```