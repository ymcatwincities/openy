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

  $items['openy-tools-fix-entity-embed-block-content'] = [
    'description' => 'Fix entity_embed markup for block content.',
    'callback' => 'openy_tools_fix_entity_embed_block_content',
    'drupal dependencies' => ['entity_embed'],
    'aliases' => ['ot-fee-bc']
  ];

  $items['openy-tools-fix-double-quotes'] = [
    'description' => 'Fix double quotes.',
    'callback' => 'openy_tools_fix_double_quotes',
    'drupal dependencies' => ['entity_embed'],
    'aliases' => ['ot-fdq']
  ];

  $items['openy-tools-fix-display-mode'] = [
    'description' => 'Fix display mode.',
    'callback' => 'openy_tools_fix_display_mode',
    'drupal dependencies' => ['entity_embed'],
    'aliases' => ['ot-fdm']
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

/**
 * Callback for "ot-fee-bc" command.
 */
function openy_tools_fix_entity_embed_block_content() {
  \Drupal::service('openy_tools.fix_entity_embed')->fixBlockContent();
}

/**
 * Callback for "ot-fdq" command.
 */
function openy_tools_fix_double_quotes() {
  \Drupal::service('openy_tools.fix_double_quotes')->fixDoubleQuotes();
}

/**
 * Callback for "ot-fdm" command.
 */
function openy_tools_fix_display_mode() {
  \Drupal::service('openy_tools.fix_display_mode')->fixDisplay();
}
