<?php

/**
 * @file
 * Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function openy_tools_drush_command() {
  $items['openy-tools-fix-entity-embed'] = [
    'description' => 'Fix entity_embed markup.',
    'callback' => 'openy_tools_fix_entity_embed',
    'drupal dependencies' => ['entity_embed'],
    'aliases' => ['ot-fee']
  ];
  return $items;
}

/**
 * Callback function for hook_drush_command().
 *
 * Generate email reports.
 *
 * @param int $request_number
 *   Request number to MB.
 */
function openy_tools_fix_entity_embed() {
  \Drupal::service('openy_tools.fix_entity_embed')->process();
}
