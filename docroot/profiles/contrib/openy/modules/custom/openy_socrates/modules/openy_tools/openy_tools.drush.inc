<?php

/**
 * @file
 * Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function openy_tools_drush_command() {
  $items['openy-tools-fix-entity-embed-menu-link'] = [
    'description' => 'Fix entity_embed markup for menu_link.',
    'callback' => 'openy_tools_fix_entity_embed_menu_link',
    'drupal dependencies' => ['entity_embed'],
    'aliases' => ['ot-fee-ml']
  ];

  $items['openy-tools-fix-entity-embed-file'] = [
    'description' => 'Fix entity_embed markup for file.',
    'callback' => 'openy_tools_fix_entity_embed_file',
    'drupal dependencies' => ['entity_embed'],
    'aliases' => ['ot-fee-f']
  ];

  return $items;
}

/**
 * Callback for "ot-fee-ml" command.
 */
function openy_tools_fix_entity_embed_menu_link() {
  \Drupal::service('openy_tools.fix_entity_embed')->fixMenuLink();
}

/**
 * Callback for "ot-fee-f" command.
 */
function openy_tools_fix_entity_embed_file() {
  \Drupal::service('openy_tools.fix_entity_embed')->fixFile();
}
