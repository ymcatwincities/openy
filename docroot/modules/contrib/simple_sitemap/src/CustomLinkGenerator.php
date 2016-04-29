<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\LinkGenerators\CustomLinkGenerator.
 *
 * Generates custom sitemap paths provided by the user.
 */

namespace Drupal\simple_sitemap;

/**
 * CustomLinkGenerator class.
 */
class CustomLinkGenerator {

  /**
   * Returns an array of all urls of the custom paths.
   *
   * @param array $custom_paths
   *
   * @return array $urls
   *
   */
  public function getCustomPaths($custom_paths) {
    $paths = array();
    foreach($custom_paths as $i => $custom_path) {
      $paths[$i]['path'] = $custom_path['path'];
      $paths[$i]['priority'] = isset($custom_path['priority']) ? $custom_path['priority'] : NULL;
      $paths[$i]['lastmod'] = NULL; //todo: implement lastmod
    }
    return $paths;
  }
}
