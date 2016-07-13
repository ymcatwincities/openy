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

@todo:

1. Rename field_pmc_data to field_pmc_personify_data
2. Rename field_pmc_mindbody_data field_pmc_client_data
3. Add logic for field_pmc_mindbody_order 
