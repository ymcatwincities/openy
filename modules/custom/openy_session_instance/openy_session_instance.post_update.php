<?php

/**
 * @file
 * Contains hook_post_update_NAME() implementations.
 */

/**
 * Helper batch operation callback.
 *
 * Recreate session instances.
 */
function _openy_session_instance_update_all_sessions(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = Drupal::entityQuery('node')
      ->condition('type', 'session')
      ->count()
      ->execute();
  }

  // Recreate session instances in chunks of 20 sessions.
  $ids = Drupal::entityQuery('node')
    ->condition('type', 'session')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('nid')
    ->execute();
  $nodes = Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  foreach ($nodes as $node) {
    Drupal::service('session_instance.manager')
      ->recreateSessionInstances($node);
    $sandbox['progress']++;
    $sandbox['current'] = $node->id();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, return nothing.
  return t('Session instances were created for @count session nodes', ['@count' => $sandbox['max']]);
}

/**
 * Updates all session instances after the module updates.
 */
function openy_session_instance_post_update_add_actual_field_values(&$sandbox) {
  _openy_session_instance_update_all_sessions($sandbox);
}
