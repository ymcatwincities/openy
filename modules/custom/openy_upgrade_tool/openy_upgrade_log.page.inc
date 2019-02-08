<?php

/**
 * @file
 * Contains openy_upgrade_log.page.inc.
 *
 * Page callback for Openy upgrade log entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Openy upgrade log templates.
 *
 * Default template: openy_upgrade_log.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_openy_upgrade_log(array &$variables) {
  // Fetch OpenyUpgradeLog Entity Object.
  $openy_upgrade_log = $variables['elements']['#openy_upgrade_log'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
