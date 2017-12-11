# YMCA Camp du Nord

## Sync

### How to run sync?
    drush ev 'ymca_sync_run("ymca_cdn_sync.syncer", "proceed");'

Synchronization is performed by `ymca_cdn_sync` module.

### How to check sync errors?
    drush wd-show --type=ymca_cdn_sync