<?php

/**
 * @file
 * Taxonomy views hooks.
 */

/**
 * Implements hook_views_data_alter()
 */
function openy_taxonomy_views_data_alter(&$data) {
  $data['taxonomy_index']['tid'] = [
    'group' => t('Open Y Content'),
    'title' => t('Has taxonomy term ID. AND query support.'),
    'help' => t('Display content if it has the selected taxonomy terms. AND query support'),
    'argument' => [
      'id' => 'taxonomy_index_tid',
      'name table' => 'taxonomy_term_field_data',
      'name field' => 'name',
      'empty field name' => t('Uncategorized'),
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_field_data',
    ],
    'filter' => [
      'title' => t('Has taxonomy terms'),
      'id' => 'better_taxonomy_index_tid',
      'hierarchy table' => 'taxonomy_term_hierarchy',
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_field_data',
      'allow empty' => TRUE,
    ],
  ];
}
