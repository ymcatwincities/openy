<?php

/**
 * @file
 * Contains personify_mindbody_cache.page.inc.
 *
 * Page callback for Personify MindBody Cache entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Personify MindBody Cache templates.
 *
 * Default template: personify_mindbody_cache.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_personify_mindbody_cache(array &$variables) {
  // Fetch PersonifyMindbodyCache Entity Object.
  $personify_mindbody_cache = $variables['elements']['#personify_mindbody_cache'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
