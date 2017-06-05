<?php
/**
 * @file
 * Drush integration.
 */

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\config_import\Drush\Command;

/**
 * Implements hook_drush_command().
 */
function config_import_drush_command() {
  /* @var Command[] $commands */
  $commands = [];

  // Cannot continue without loaded container or module installed.
  if (!Drupal::hasContainer() || !Drupal::moduleHandler()->moduleExists('config_import')) {
    return $commands;
  }

  $commands['config-import-types'] = new Command('conftypes', [
    'description' => dt('Output a list of available configuration types.'),
    'examples' => [
      '' => dt('Show all available configuration types.'),
    ],
  ]);

  $commands['config-import-names'] = new Command('confnames', [
    'description' => dt('Output a list of available configurations for a specific type.'),
    'options' => [
      'type' => TRUE,
    ],
    'examples' => [
      '--type=menu' => dt('Show all available configurations for specific type.'),
    ],
  ]);

  $commands['config-import-export'] = new Command('confexport', [
    'description' => dt('Output a configuration on the screen or into the file.'),
    'options' => [
      'type' => TRUE,
      'name' => TRUE,
      'destination' => FALSE,
    ],
    'examples' => [
      '--type=field_config --name=user.user.field_first_name' => dt('Output config on the screen. You can use output redirection operator to save contents into custom file.'),
      '--type=entity_browser --name=browse_files > example-config.yml' => dt('Save configuration into a custom file using output redirection operator.'),
      '--type=editor --name=full_html --destination=sites/default/config/staging' => dt('Save configuration into the file using fully qualified path to directory.'),
      '--type=menu --name=main --destination=staging' => dt('Save configuration into the file using the name of directory with configurations.'),
    ],
  ]);

  $commands['config-import-import'] = new Command('confimport', [
    'description' => dt('Import configuration from a file.'),
    'options' => [
      'type' => TRUE,
      'name' => TRUE,
      'destination' => TRUE,
    ],
    'examples' => [
      '--type=editor --destination=sites/default/config/staging' => dt('Import all configurations of specified type using fully qualified path to directory.'),
      '--type=menu --name=main --destination=staging' => dt('Import specific configuration from the file using the name of directory with configurations.'),
    ],
  ]);

  foreach ($commands as $command => $instance) {
    $commands[$command] = $instance->getDefinition();
  }

  return $commands;
}

/**
 * Implements drush_COMMAND().
 */
function drush_config_import_types() {
  $rows = [
    ['system.simple', dt('Simple configuration')],
  ];

  foreach (Drupal::entityTypeManager()->getDefinitions() as $entity_type => $definition) {
    if ($definition->isSubclassOf(ConfigEntityInterface::class)) {
      $rows[] = [$entity_type, $definition->getLabel()];
    }
  }

  _config_import_print_table($rows, ['Type', 'Label']);
}

/**
 * Implements drush_COMMAND().
 */
function drush_config_import_names() {
  try {
    $entity_manager = Drupal::entityTypeManager();
    $options = _drush_config_import_get_options();
    $rows = [];

    // Handle simple configuration.
    if ('system.simple' === $options['type']) {
      $config_prefixes = [];
      $headers = ['Name'];

      foreach ($entity_manager->getDefinitions() as $entity_type => $definition) {
        if ($definition instanceof ConfigEntityTypeInterface) {
          $config_prefixes[] = $definition->getConfigPrefix() . '.';
        }
      }

      foreach (Drupal::service('config.storage')->listAll() as $config_name) {
        foreach ($config_prefixes as $config_prefix) {
          if (strpos($config_name, $config_prefix) !== FALSE) {
            $rows[] = [$config_name];
          }
        }
      }
    }
    // For a given entity type, load all entities.
    else {
      $headers = ['Name', 'Label'];

      foreach ($entity_manager->getStorage($options['type'])->loadMultiple() as $entity_id => $entity) {
        $rows[] = [$entity_id, $entity->label() ?: $entity_id];
      }
    }

    _config_import_print_table($rows, $headers);
  }
  catch (Exception $e) {
    drush_log($e->getMessage(), 'error');
  }
}

/**
 * Implements drush_COMMAND().
 */
function drush_config_import_export() {
  try {
    // Do not forget:
    // - the "name" option will be prefixed by configuration prefix;
    $options = _drush_config_import_get_options();
    $config = Drupal::service('config.storage')->read($options['name']);

    if (FALSE === $config) {
      throw new RuntimeException(dt('No configuration with "@name" name!', [
        '@name' => $options['name'],
      ]));
    }

    $content = Yaml::encode($config);

    if (NULL === $options['destination']) {
      drush_print($content);
    }
    else {
      $export_confirmed = TRUE;

      // Ask before override the file.
      if (file_exists($options['destination'])) {
        $export_confirmed = drush_confirm(dt('Configuration already exists in the "@destination" file. Do you want to override it?', [
          '@destination' => $options['destination'],
        ]));
      }

      if ($export_confirmed) {
        if (!file_unmanaged_save_data($content, $options['destination'], FILE_EXISTS_REPLACE)) {
          throw new RuntimeException(dt('Configuration cannot be saved into the "@destination" file! Check system log.', [
            '@destination' => $options['destination'],
          ]));
        }

        $message = dt('Configuration has been saved into the "@destination" file.', [
          '@destination' => $options['destination'],
        ]);
      }
      else {
        $message = dt('Export of the "@name" configuration has been canceled.', [
          '@name' => $options['name'],
        ]);
      }

      drush_log($message, 'status');
    }
  }
  catch (Exception $e) {
    drush_log($e->getMessage(), 'error');
  }
}

/**
 * Implements drush_COMMAND().
 */
function drush_config_import_import() {
  try {
    // Do not forget:
    // - the "destination" option will contain fully qualified path to file;
    // - the "name" option will be prefixed by configuration prefix;
    $options = _drush_config_import_get_options();

    if (!file_exists($options['destination'])) {
      throw new RuntimeException(dt('The "@destination" file with configuration does not exists!', [
        '@destination' => $options['destination'],
      ]));
    }

    $import_confirmed = drush_confirm(dt('Do you confirm an import of configuration from the "@destination" file?', [
      '@destination' => $options['destination'],
    ]));

    if ($import_confirmed) {
      Drupal::service('config_import.importer')->importConfigs([$options['destination']]);

      $message = dt('The "@name" configuration has been successfully imported.', [
        '@name' => $options['name'],
      ]);
    }
    else {
      $message = dt('Import of the "@name" configuration has been canceled.', [
        '@name' => $options['name'],
      ]);
    }

    drush_log($message, 'status');
  }
  catch (Exception $e) {
    drush_log($e->getMessage(), 'error');
  }
}

/**
 * Output table into command line.
 *
 * @param array[] $rows
 *   An array of associative arrays - table rows.
 * @param string[] $headers
 *   An associative array of table headers.
 *
 * @internal
 */
function _config_import_print_table(array $rows, array $headers) {
  $maxlength = [];

  // Compute the maximum length for every column.
  foreach ($rows as $i => $row) {
    foreach ($row as $key => $value) {
      $maxlength += [$key => 0];
      $length = strlen($value);

      if ($maxlength[$key] < $length) {
        $maxlength[$key] = $length;
      }
    }
  }

  // Forming a separator for header and rows.
  foreach ($maxlength as $key => $value) {
    $maxlength[$key] = implode(array_fill(0, $value, '-'));
  }

  // Append separator as first row.
  array_unshift($rows, $maxlength);

  drush_print(drush_format($rows, [
    'format' => 'table',
    'field-labels' => array_map('dt', $headers),
    'include-field-labels' => TRUE,
  ]));
}

/**
 * Get values for specified options.
 *
 * @return string[]
 *   An associative array where keys - are option names and values - values.
 *
 * @internal
 */
function _drush_config_import_get_options() {
  $command = drush_get_command();
  $options = [];

  foreach ($command['options'] as $option => $specification) {
    $options[$option] = drush_get_option($option);
  }

  // @see Command::processOptions()
  return call_user_func([$command['class'], 'processOptions'], $options);
}
