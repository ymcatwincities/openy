<?php

/**
 * @file
 * Contains \Drupal\entity_reference_exposed_filters\entity_reference_exposed_filters.views.inc.
 */

/**
 * Implements hook_views_data_alter().
 */
function entity_reference_exposed_filters_views_data_alter(array &$data) {
  $data['node_field_data']['eref_node_titles'] = [
    'title' => t('Entity Reference Exposed Filters Node Titles'),
    'filter' => [
      'title' => t('Entity Reference Exposed Filters Node Titles'),
      'help' => t('Show Enity Reference Node Titles as a list.'),
      'field' => 'nid',
      'id' => 'eref_node_titles',
    ],
  ];
}
