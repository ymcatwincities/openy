<?php

/**
 * @file
 * Contains logger_entity.page.inc.
 *
 * Page callback for Logger Entity entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Logger Entity templates.
 *
 * Default template: logger_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_logger_entity(array &$variables) {
  // Fetch LoggerEntity Entity Object.
  $logger_entity = $variables['elements']['#logger_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
