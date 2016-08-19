<?php

/**
 * @file
 * Contains mindbody_cache.page.inc.
 *
 * Page callback for MindBody Cache entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for MindBody Cache templates.
 *
 * Default template: mindbody_cache.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_mindbody_cache(array &$variables) {
  // Fetch MindbodyCache Entity Object.
  $mindbody_cache = $variables['elements']['#mindbody_cache'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
