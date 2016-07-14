# Personify Mindbody Sync

The module have 2 syncers: fast and slow.

  * Fast proceeds withing last 24 hours.
  * Slow proceeds with all failed items.

To run the process use the next code:

  * With PHP:
  `ymca_sync_run("personify_mindbody_sync.syncer_fast", "proceed");`
  `ymca_sync_run("personify_mindbody_sync.syncer_slow", "proceed");`
  
  * With Drush:
  `drush ev 'ymca_sync_run("personify_mindbody_sync.syncer", "proceed");'`

## Help methods

### Clear cached entities

  `drush ev '\Drupal::service("personify_mindbody_sync.proxy")->clearCache();'`

## TODO

1. Get rid of Pusher::getEntityByClientId, and use Wraper::FindOrder (We can get multiple orders by single Client)
2. Add different payment method in the Pusher for stage/prod
3. Combine Lite items into single requests. 
