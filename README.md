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
