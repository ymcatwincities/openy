# Gcal Groupex Syncer

Syncs Groupex schedules with Google calendar.

### How to test

```
\Drupal::service('ymca_google.test_manager')->setTestMode();
```

### How to deploy

In order to prevent test data to be push to production `is_production` flag was implemented. By default is always FALSE.
In order to switch production mode on the production server please use next command:

```
drush cset ymca_google.settings is_production 1
```

### How to run

```
ymca_sync_run("ymca_google.syncer", "proceed");
```

```
drush php-eval "ymca_sync_run('ymca_google.syncer', 'proceed');"
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

### How to debug

If `is_production` flag is set to 0 then all events are pushed to calendar named `TESTING`. You can delete this calendar any time.

#### Remove all cached entities

```
\Drupal::service('ymca_google.pusher')->clearCache();
```

#### Remove schedule

```
\Drupal::service('ymca_google.groupex_wrapper')->removeSchedule();
```
