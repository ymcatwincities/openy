<?php

/**
 * @file
 * Contains cdn_prs_product.page.inc.
 *
 * Page callback for Camp du Nord Personify Product entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Camp du Nord Personify Product templates.
 *
 * Default template: cdn_prs_product.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_cdn_prs_product(array &$variables) {
  // Fetch CdnPrsProduct Entity Object.
  $cdn_prs_product = $variables['elements']['#cdn_prs_product'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
