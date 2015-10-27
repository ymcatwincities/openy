<?php

/**
 * @file
 * Configuration development module drush integration.
 */

use Drupal\Core\Config\InstallStorage;

/**
 * Implements hook_drush_command().
 */
function config_devel_drush_command() {
  $items = array();

  $description = '';
  $description .= "Write back configuration to module's config/install directory." . "\n";
  $description .= "List which configuration settings you want to export in the" . "\n";
  $description .= "module's info file by listing them under 'config_devel', as shown below:"  . "\n";
  $description .= "\n";
  $description .= "config_devel:"  . "\n";
  $description .= "  - entity.view_display.node.article.default"  . "\n";
  $description .= "  - entity.view_display.node.article.teaser"  . "\n";
  $description .= "  - field.instance.node.article.body"  . "\n";

  $items['config-devel-export'] = array(
    'description' => $description,
    'arguments' => array(
      'module' => 'Module machine name.',
    ),
    'options' => array(
    ),
    'required-arguments' => TRUE,
    'examples' => array(
      'drush config-devel-export MODULE_NAME' => 'Write back configuration to the specified module, based on .info file.',
    ),
    'aliases' => array('cde', 'cd-em'),
  );

  return $items;
}


/**
 * Drush command callback.
 */
function drush_config_devel_export($extension) {

  if (($module_exists = \Drupal::moduleHandler()->moduleExists($extension)) || \Drupal::service('theme_handler')->themeExists($extension)) {
    $type = $module_exists ? 'module' : 'theme';
    $filename = drupal_get_path($type, $extension) . '/' . $extension .'.info.yml';
    $info = \Drupal::service('info_parser')->parse($filename);

    if (isset($info['config_devel'])) {
      // Keep backwards compatibility for the old format.
      if (!isset($info['config_devel']['install'])) {
        $info['config_devel']['install'] = $info['config_devel'];
      }
      drush_config_devel_process_config($info['config_devel']['install'], $type, $extension, InstallStorage::CONFIG_INSTALL_DIRECTORY);

      // If we have any optional configuration, process that as well.
      if (isset($info['config_devel']['optional'])) {
        drush_config_devel_process_config($info['config_devel']['optional'], $type, $extension, InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
      }
    }
  }
}

/**
 * Exports a list of configuration entities.
 *
 * @param array $config_list
 *   An array of configuration entities.
 * @param string $type
 *   The type of extension we're exporting, one of module or theme.
 * @param string $extension
 *   The module, theme or install profile we're exporting.
 * @param string $directory
 *   The directory we're exporting to.
 */
function drush_config_devel_process_config($config_list, $type, $extension, $directory) {
  $config_path = drupal_get_path($type, $extension) . "/$directory";
  foreach ($config_list as $name) {
    $config = \Drupal::config($name);
    $file_names = array($config_path . '/' . $name . '.yml');
    \Drupal::service('config_devel.writeback_subscriber')->writeBackConfig($config, $file_names);
  }
}
