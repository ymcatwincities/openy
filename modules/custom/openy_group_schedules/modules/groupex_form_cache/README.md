# Groupex Form Cache

  * Implements cache for Groupex form
  * Implements cache warmer
  
## How to run the warmer

``groupex_form_cache_warm();``

## How to clear the cache

``\Drupal::service("groupex_form_cache.manager")->resetCache(100);``

or

``groupex_form_cache_reset_all();``

## How to clear the cache (using truncate)

``groupex_form_cache_reset_all_quick();``

## How to clear the only stale cache

``\Drupal::service("groupex_form_cache.manager")->resetStaleCache(10, 86400);``

## How to clear the stale cache with Drush

``drush ev '\Drupal::service("groupex_form_cache.manager")->resetStaleCache();'``
