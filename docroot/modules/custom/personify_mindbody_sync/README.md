# Personify Mindbody Sync

The module have 2 syncers: fast and slow.

  * Fast proceeds withing last hour.
  * Slow proceeds with all failed items.
 
By default the Syncer is not in production mode. In order to run it in production
you have to enable production mode.

To enable production mode:

  `drush cset personify_mindbody_sync.settings is_production 1`

To run the process use the next code:

  * With PHP:
  `ymca_sync_run("personify_mindbody_sync.syncer_fast", "proceed");`
  `ymca_sync_run("personify_mindbody_sync.syncer_slow", "proceed");`
  
  * With Drush:
  `drush ev 'ymca_sync_run("personify_mindbody_sync.syncer", "proceed");'`
  
## Fast Syncer configuration

By default, the fast syncer fetches Personify data for the last hour.
This interval can be changed via settings.

    `drush cset personify_mindbody_sync.settings personify_date_offset P1D`
    
Please, look the documentation how to provide the right intervals:
http://php.net/manual/en/class.dateinterval.php

## Help methods

### Clear cached entities

  `drush ev '\Drupal::service("personify_mindbody_sync.proxy")->clearCache();'`

### How to run tester

Just replace `testSendNotification()` with your method.

  * With Drush:
  `drush ev '\Drupal::service("personify_mindbody_sync.tester")->testSendNotification();'`

  * With PHP:
  `\Drupal::service("personify_mindbody_sync.tester")->testSendNotification();`
 
### How to debug:

**Be careful**

Personify & Mindbody is in PROD mode. Every time running the syncer locally you affect real data.

@see `docroot/modules/custom/personify_mindbody_sync/src/PersonifyMindbodySyncFetcherBase.php:114`

## TODO

  * Phone validation, Birthday validation
  * Slow & fast pushers (slow should push clients one by one). 
