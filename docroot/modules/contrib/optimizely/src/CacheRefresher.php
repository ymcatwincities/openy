<?php

namespace Drupal\optimizely;

class CacheRefresher {


  /**
   * doRefresh()
   *
   * @param
   *   $path_array - An array of the target paths entries that the cache needs to
   *   be cleared. Each entry can also contain wildcards /* or variables "<front>".
   */
  public static function doRefresh($path_array, $original_path_array = NULL) {

    // If there are project edits that include changes to the path, clear cache on all
    // paths/tags to add or remove Optimizely javascript call.

    // The project paths also serve as cache tags, so all we need to do
    // is invalidate all of the affected paths, treating them as cache tags.

    if (isset($original_path_array)) {
      $path_array = array_merge($path_array, $original_path_array);
    }

    // Prefix each path with "optimizely:" to form its cache tag.
    // @See our implementation of hook_page_attachments()
    $cache_tags = [];
    foreach ($path_array as $path) {
      $cache_tags[] = 'optimizely:' . $path;
    }

    \Drupal::service('cache_tags.invalidator')->invalidateTags($cache_tags);

    drupal_set_message(t('"Render" cache has been cleared based on the project path settings.'), 'status');

  }

}