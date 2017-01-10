<?php

/**
 * @file
 * Post update functions for Address.
 */

/**
 * @addtogroup updates-8.x-1.0-rc1
 * @{
 */

/**
 * Re-save all entities with address data to update names and subdivisions.
 */
function address_post_update_convert_names_subdivisions(&$sandbox = NULL) {
  if (!isset($sandbox['fields'])) {
    $sandbox['fields'] = \Drupal::state()->get('address_8101_processed');
    $sandbox['count'] = count($sandbox['fields']);
    // No fields were updated.
    if (empty($sandbox['fields'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $field = array_pop($sandbox['fields']);
  $entity_type_id = $field[0];
  $field_name = $field[1];
  $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
  $query = $storage->getQuery()->exists($field_name . '.given_name');
  $entities = $storage->loadMultiple($query->execute());
  foreach ($entities as $entity) {
    _address_update_entity($entity, $field_name);
    $entity->save();
  }

  $sandbox['#finished'] = empty($sandbox['fields']) ? 1 : ($sandbox['count'] - count($sandbox['fields'])) / $sandbox['count'];
  return t('Updated the names and subdivisions of each address.');
}

/**
 * @} End of "addtogroup updates-8.x-1.0-rc1".
 */

