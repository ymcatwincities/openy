# YGH Programs Search

Provides configurable programs search paragraph.

## How to warm the cache

  - Warm the cache using Drush
  
```
drush ev '\Drupal::service("ygh_programs_search.data_storage")->warmCache();'
```
  - Warm the cache using PHP
  
```
\Drupal::service("ygh_programs_search.data_storage")->warmCache();
```
