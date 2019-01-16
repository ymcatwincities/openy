<?php

/**
 * @file
 * Post update hooks for Open Y Repeat Schedules paragraph type.
 */

/**
 * Set default value for the new filter field.
 */
function openy_prgf_repeat_schedules_post_update_populate_filter_field(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('paragraph')
      ->condition('type', 'repeat_schedules')
      ->count()
      ->execute();
  }

  $paragraph_ids = \Drupal::entityQuery('paragraph')
    ->condition('type', 'repeat_schedules')
    ->condition('id', $sandbox['current'], '>')
    ->range(0, 5)
    ->sort('id')
    ->execute();

  $entity_type_manager = \Drupal::entityTypeManager()->getStorage('paragraph');
  $paragraphs = $entity_type_manager->loadMultiple($paragraph_ids);
  foreach ($paragraphs as $paragraph) {
    $paragraph->field_prgf_repeat_schedule_filt->set(0, 'category');
    $paragraph->save();
    $sandbox['progress']++;
    $sandbox['current'] = $paragraph->id();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
}
