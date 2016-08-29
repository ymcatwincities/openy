# Groupex Form Cache

  * Implements cache for Groupex form
  * Implements cache warmer
  
## How to run the warmer

``\Drupal::service("groupex_form_cache.warmer")->warm();``

## How to clear the cache

``\Drupal::service("groupex_form_cache.manager")->resetCache();``

## How to clear the only stale cache

``\Drupal::service("groupex_form_cache.manager")->resetStaleCache();``
