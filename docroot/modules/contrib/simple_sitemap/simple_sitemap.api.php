<?php

/**
 * @file
 * Hooks provided by the Simple XML sitemap module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the generated link data before the sitemap is saved.
 * This hook gets invoked for every sitemap chunk generated.
 *
 * @param array &$links
 *   Array containing multilingual links generated for each path to be indexed.
 */
function hook_simple_sitemap_links_alter(&$links) {

  // Remove German URL for a certain path in the hreflang sitemap.
  foreach ($links as &$link) {
    if ($link['path'] == 'node/1') {
      // Remove 'loc' URL if it points to a german site.
      if ($link['langcode'] == 'de') {
        unset($link);
      }
      // If this 'loc' URL points to a non-german site, make sure to remove
      // its german alternate URL.
      else {
        unset($link['alternate_urls']['de']);
      }
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
