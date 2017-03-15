<?php

/**
 * @file
 * Config Importer API.
 */

/**
 * Prevent configurations from import.
 *
 * @param string[] $configs
 *   A list of configurations restricted for importing.
 *
 * @see \Drupal\config_import\ConfigImporterService::filter()
 */
function hook_config_import_configs_alter(array &$configs) {
  $configs[] = 'action.settings';
}
