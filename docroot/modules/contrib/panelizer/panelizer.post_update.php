<?php

/**
 * @file
 * Post update functions for Panelizer.
 */

/**
 * @addtogroup updates-8.3.0
 * @{
 */

/**
 * Implementations of hook_post_update_NAME().
 */

/**
 * Rename layout machine names in content entities to match layout discovery's
 * default layouts.
 */
function panelizer_post_update_rename_layout_machine_names(&$sandbox) {
  module_load_install('panels');

  /** @var \Drupal\panelizer\Plugin\PanelizerEntityManager $panelizer_manager */
  $panelizer_manager = \Drupal::service('plugin.manager.panelizer_entity');

  $results = [];
  // Update overridden panelizer entities.
  foreach ($panelizer_manager->getDefinitions() as $entity_type => $definition) {
    if (db_table_exists($entity_type . '__panelizer')) {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $ids = $storage->getQuery()
        ->condition('panelizer', serialize([]), '<>')
        ->execute();

      foreach ($storage->loadMultiple($ids) as $entity_id => $entity) {
        $results[] = $entity;
      }
    }
  }

  // Use the sandbox to store the information needed to track progression.
  if (!isset($sandbox['current'])) {
    // The count of entities visited so far.
    $sandbox['current'] = 0;
    // Total entities that must be visited.
    $sandbox['max'] = count($results);
    // A place to store messages during the run.
  }

  // Process entities by groups of 20.
  // When a group is processed, the batch update engine determines whether it
  // should continue processing in the same request or provide progress
  // feedback to the user and wait for the next request.
  $limit = 5;
  $result = array_slice($results, $sandbox['current'], $limit);

  foreach ($result as $entity) {
    if ($entity->hasField('panelizer') && $entity->panelizer->first()) {
      foreach ($entity->panelizer as $item) {
        $panels_display_config = $item->get('panels_display')->getValue();

        // If our field has custom panelizer display config data.
        if (!empty($panels_display_config) && is_array($panels_display_config)) {
          $layout_id = $panels_display_config['layout'];
          if ($new_layout_id = panels_convert_plugin_ids_to_layout_discovery($layout_id)) {
            $panels_display_config['layout'] = $new_layout_id;
            $item->set('panels_display', $panels_display_config);
          }
        }
      }
      $entity->search_api_skip_tracking = TRUE;
      $entity->save();
    }
    // Update our progress information.
    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current'] / $sandbox['max']);

  if ($sandbox['#finished'] >= 1) {
    return t('Panelized custom layouts have been updated.');
  }
}
