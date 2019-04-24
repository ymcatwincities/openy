<?php

/**
 * @file
 * Contains groupex_form_cache.page.inc.
 *
 * Page callback for GroupEx Pro Form Cache entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for GroupEx Pro Form Cache templates.
 *
 * Default template: groupex_form_cache.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_groupex_form_cache(array &$variables) {
  // Fetch GroupexFormCache Entity Object.
  $groupex_form_cache = $variables['elements']['#groupex_form_cache'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
