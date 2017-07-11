<?php

/**
 * @file
 * Hooks and documentation related to paragraphs module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in
 * \Drupal\paragraphs\Annotation\ParagraphsBehavior.
 *
 * @param $paragraphs_behavior
 *   The array of paragraphs behavior plugins, keyed on the
 *   machine-readable plugin name.
 */
function hook_paragraphs_behavior_info_alter(&$paragraphs_behavior) {
  // Set a new label for the my_layout plugin instead of the one
  // provided in the annotation.
  $paragraphs_behavior['my_layout']['label'] = t('New label');
}

/**
 * @} End of "addtogroup hooks".
 */
