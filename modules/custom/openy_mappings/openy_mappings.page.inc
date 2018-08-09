<?php

/**
 * @file
 * Contains openy_mappings.page.inc.
 *
 * Page callback for Mapping entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Mapping templates.
 *
 * Default template: mapping.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_mapping(array &$variables) {
  // Fetch Mapping Entity Object.
  $mapping = $variables['elements']['#mapping'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for a custom entity type creation list templates.
 *
 * Default template: mapping-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of mapping-types.
 *
 * @see block_content_add_page()
 */
function template_preprocess_mapping_content_add_list(array &$variables) {
  $variables['types'] = array();
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = [
      'link' => Link::fromTextAndUrl(
        $type->label(),
        new Url(
          'entity.mapping.add_form',
          ['mapping_type' => $type->id()],
          ['query' => $query]
        )
      ),
      'description' => ['#markup' => $type->label()],
      'title' => $type->label(),
      'localized_options' => ['query' => $query],
    ];
  }
}
