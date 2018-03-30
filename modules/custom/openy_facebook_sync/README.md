# OpenY Facebook Sync

Synchronizes data with Facebook.

### How to run syncer

1. Make sure that syncer is switched on: `admin/config/system/openy-sync`
2. Run the next code: `openy_facebook_sync_run();`

### Usage example

Get Page events. Replace `<page_id>` with your Page ID.
```
$facebook = \Drupal::service('openy_facebook_sync.factory')->getFacebook();
$result = $facebook->sendRequest('GET', "<page_id>/events");
$body = $result->getDecodedBody();
```

### Config files for facebook sync

Configuration stored in openy_facebook_sync.settings.yml and openy_facebook_sync.locations_map.yml

openy_facebook_sync.settings.yml stores main config for facebook sync process
app_id - list of facebook page ids of branch/camps that should be synced. 


openy_facebook_sync.locations_map - stores mapping between location facebook page id 
and uuid of location page on site. 
In case facebook page is mapped with several location pages'
put it to config file as array, for example
```
  114531621415:
    - 0a0d6cbe-3bc7-11e1-952a-12313b0f39a2
    - 0a17d62c-3bc7-11e1-952a-12313b0f39a2
```
