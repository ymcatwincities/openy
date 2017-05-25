<?php

/**
 * @file
 * Contains hook_post_update_NAME() implementations.
 */

/**
 * Set tags_style field default value in existing paragraphs.
 */
function openy_prgf_loc_finder_post_update_set_tags_style_default_value(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('paragraph')
      ->condition('type', 'prgf_location_finder_filters')
      ->count()
      ->execute();
  }

  $paragraph_ids = \Drupal::entityQuery('paragraph')
    ->condition('type', 'prgf_location_finder_filters')
    ->condition('id', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('id')
    ->execute();

  $paragraphs = \Drupal::entityTypeManager()->getStorage('paragraph')->loadMultiple($paragraph_ids);

  foreach ($paragraphs as $paragraph) {
    $paragraph->set('field_prgf_lf_tags_style', 'checkboxes');
    $paragraph->save();
    $sandbox['progress']++;
    $sandbox['current'] = $paragraph->id();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  return t('Fields data were migrated for @count entities', ['@count' => $sandbox['max']]);
}
