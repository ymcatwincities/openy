<?php

/**
 * @file
 * Hooks specific to the openy_search module.
 */

/**
 * Alter the default Open Y themes search form parameters.
 *
 * Search parameters for theme originally stored in theme_name.settings file.
 *
 * @param array $search_config
 *   The associative array of search form parameters.
 *
 * @see \Drupal\openy_search\Config\OpenySearchOverrides::loadOverrides()
 */
function hook_openy_search_theme_configuration_alter(&$search_config) {
  $search_config = [
    'search_query_key' => 'q',
    'search_page_alias' => 'search',
    'display_search_form' => 1,
  ];
}
