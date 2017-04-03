# OpenY Facebook Sync

Synchronizes data with Facebook.

### Usage example

Get Page events. Replace `<page_id>` with your Page ID.
```
$facebook = \Drupal::service('openy_facebook_sync.factory')->getFacebook();
$result = $facebook->sendRequest('GET', "<page_id>/events");
$body = $result->getDecodedBody();
```