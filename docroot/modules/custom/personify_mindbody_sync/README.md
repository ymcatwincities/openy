# Personify Mindbody Sync

To run the process use the next code:

  * With PHP:
  `ymca_sync_run("personify_mindbody_sync.syncer", "proceed");`
  
  * With Drush:
  `drush ev 'ymca_sync_run("personify_mindbody_sync.syncer", "proceed");'`

## Help methods

### Clear cached entities

  `drush ev '\Drupal::service("personify_mindbody_sync.proxy")->clearCache();'`
