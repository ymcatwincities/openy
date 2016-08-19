<?php

/**
 * @file
 * Contains groupex_google_cache.page.inc.
 *
 * Page callback for Groupex Google Cache entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Groupex Google Cache templates.
 *
 * Default template: groupex_google_cache.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_groupex_google_cache(array &$variables) {
  // Fetch GroupexGoogleCache Entity Object.
  $groupex_google_cache = $variables['elements']['#groupex_google_cache'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
