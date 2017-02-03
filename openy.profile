<?php

/**
 * @file
 * Defines the OpenY Profile install screen by modifying the install form.
 */

use Drupal\openy\Form\ContentSelectForm;

/**
 * Implements hook_install_tasks().
 */
function openy_install_tasks() {
  return [
    'openy_select_content' => [
      'display_name' => t('Import demo content'),
      'display' => TRUE,
      'type' => 'form',
      'function' => ContentSelectForm::class,
    ],
    'openy_import_content' => [
      'type' => 'batch',
    ],
  ];
}

/**
 * Create batch for content import.
 *
 * @param array $install_state
 *   Installation parameters.
 *
 * @return array
 *   Batch.
 */
function openy_import_content(array &$install_state) {
  $batch = [];

  foreach ($install_state['openy']['content'] as $item) {
    $batch['operations'][] = ['openy_import_content_item', (array) $item];
  }

  // Always add `default` migrations.
  $batch['operations'][] = ['openy_import_content_item', (array) 'default'];

  return $batch;
}

/**
 * Import single content item.
 *
 * @param string $item
 *   Content item name. Example: 'blog'.
 */
function openy_import_content_item($item) {
  $importer = \Drupal::service('openy.content_importer');
  $importer->import($item);
}
