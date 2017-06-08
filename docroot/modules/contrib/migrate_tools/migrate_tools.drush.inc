<?php

/**
 * @file
 * Command-line tools to aid performing and developing migrations.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\migrate_tools\DrushLogMigrateMessage;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\migrate_plus\Entity\MigrationGroup;

/**
 * Implements hook_drush_command().
 */
function migrate_tools_drush_command() {
  $items['migrate-status'] = [
    'description' => 'List all migrations with current status.',
    'options' => [
      'group' => 'A comma-separated list of migration groups to list',
      'tag' => 'Name of the migration tag to list',
      'names-only' => 'Only return names, not all the details (faster)',
    ],
    'arguments' => [
      'migration' => 'Restrict to a comma-separated list of migrations. Optional',
    ],
    'examples' => [
      'migrate-status' => 'Retrieve status for all migrations',
      'migrate-status --group=beer' => 'Retrieve status for all migrations in a given group',
      'migrate-status --tag=user' => 'Retrieve status for all migrations with a given tag',
      'migrate-status --group=beer --tag=user' => 'Retrieve status for all migrations in the beer group and with the user tag',
      'migrate-status beer_term,beer_node' => 'Retrieve status for specific migrations',
    ],
    'drupal dependencies' => ['migrate_tools'],
    'aliases' => ['ms'],
  ];

  $items['migrate-import'] = [
    'description' => 'Perform one or more migration processes.',
    'options' => [
      'all' => 'Process all migrations.',
      'group' => 'A comma-separated list of migration groups to import',
      'tag' => 'Name of the migration tag to import',
      'limit' => 'Limit on the number of items to process in each migration',
      'feedback' => 'Frequency of progress messages, in items processed',
      'idlist' => 'Comma-separated list of IDs to import',
      'update' => ' In addition to processing unprocessed items from the source, update previously-imported items with the current data',
      'force' => 'Force an operation to run, even if all dependencies are not satisfied',
      'execute-dependencies' => 'Execute all dependent migrations first.',
    ],
    'arguments' => [
      'migration' => 'ID of migration(s) to import. Delimit multiple using commas.',
    ],
    'examples' => [
      'migrate-import --all' => 'Perform all migrations',
      'migrate-import --group=beer' => 'Import all migrations in the beer group',
      'migrate-import --tag=user' => 'Import all migrations with the user tag',
      'migrate-import --group=beer --tag=user' => 'Import all migrations in the beer group and with the user tag',
      'migrate-import beer_term,beer_node' => 'Import new terms and nodes',
      'migrate-import beer_user --limit=2' => 'Import no more than 2 users',
      'migrate-import beer_user --idlist=5' => 'Import the user record with source ID 5',
    ],
    'drupal dependencies' => ['migrate_tools'],
    'aliases' => ['mi'],
  ];

  $items['migrate-rollback'] = array(
    'description' => 'Rollback one or more migrations.',
    'options' => array(
      'all' => 'Process all migrations.',
      'group' => 'A comma-separated list of migration groups to rollback',
      'tag' => 'ID of the migration tag to rollback',
      'feedback' => 'Frequency of progress messages, in items processed',
    ),
    'arguments' => array(
      'migration' => 'Name of migration(s) to rollback. Delimit multiple using commas.',
    ),
    'examples' => array(
      'migrate-rollback --all' => 'Perform all migrations',
      'migrate-rollback --group=beer' => 'Rollback all migrations in the beer group',
      'migrate-rollback --tag=user' => 'Rollback all migrations with the user tag',
      'migrate-rollback --group=beer --tag=user' => 'Rollback all migrations in the beer group and with the user tag',
      'migrate-rollback beer_term,beer_node' => 'Rollback imported terms and nodes',
    ),
    'drupal dependencies' => array('migrate_tools'),
    'aliases' => array('mr'),
  );

  $items['migrate-stop'] = [
    'description' => 'Stop an active migration operation.',
    'arguments' => [
      'migration' => 'ID of migration to stop',
    ],
    'drupal dependencies' => ['migrate_tools'],
    'aliases' => ['mst'],
  ];

  $items['migrate-reset-status'] = [
    'description' => 'Reset a active migration\'s status to idle.',
    'arguments' => [
      'migration' => 'ID of migration to reset',
    ],
    'drupal dependencies' => ['migrate_tools'],
    'aliases' => ['mrs'],
  ];

  $items['migrate-messages'] = [
    'description' => 'View any messages associated with a migration.',
    'arguments' => [
      'migration' => 'ID of the migration',
    ],
    'options' => [
      'csv' => 'Export messages as a CSV'
    ],
    'examples' => [
      'migrate-messages MyNode' => 'Show all messages for the MyNode migration',
    ],
    'drupal dependencies' => ['migrate_tools'],
    'aliases' => ['mmsg'],
  ];

  $items['migrate-fields-source'] = [
    'description' => 'List the fields available for mapping in a source.',
    'arguments' => [
      'migration' => 'ID of the migration',
    ],
    'examples' => [
      'migrate-fields-source my_node' => 'List fields for the source in the my_node migration',
    ],
    'drupal dependencies' => ['migrate_tools'],
    'aliases' => ['mfs'],
  ];

  return $items;
}

/**
 * @param string $migration_names
 */
function drush_migrate_tools_migrate_status($migration_names = '') {
  $names_only = drush_get_option('names-only');

  $migrations = drush_migrate_tools_migration_list($migration_names);

  $table = [];
  // Take it one group at a time, listing the migrations within each group.
  foreach ($migrations as $group_id => $migration_list) {
    $group = MigrationGroup::load($group_id);
    $group_name = !empty($group) ? "{$group->label()} ({$group->id()})" : $group_id;
    if ($names_only) {
      $table[] = [
        dt('Group: @name', array('@name' => $group_name))
      ];
    }
    else {
      $table[] = [
        dt('Group: @name', array('@name' => $group_name)),
        dt('Status'),
        dt('Total'),
        dt('Imported'),
        dt('Unprocessed'),
        dt('Last imported'),
      ];
    }
    foreach ($migration_list as $migration_id => $migration) {
      try {
        $map = $migration->getIdMap();
        $imported = $map->importedCount();
        $source_plugin = $migration->getSourcePlugin();
      }
      catch (Exception $e) {
        drush_log(dt('Failure retrieving information on @migration: @message',
          ['@migration' => $migration_id, '@message' => $e->getMessage()]));
        continue;
      }
      try {
        $source_rows = $source_plugin->count();
        // -1 indicates uncountable sources.
        if ($source_rows == -1) {
          $source_rows = dt('N/A');
          $unprocessed = dt('N/A');
        }
        else {
          $unprocessed = $source_rows - $map->processedCount();
        }
      }
      catch (Exception $e) {
        drush_print($e->getMessage());
        drush_log(dt('Could not retrieve source count from @migration: @message',
          ['@migration' => $migration_id, '@message' => $e->getMessage()]));
        $source_rows = dt('N/A');
        $unprocessed = dt('N/A');
      }

      if ($names_only) {
        $table[] = [$migration_id];
      }
      else {
        $status = $migration->getStatusLabel();
        $migrate_last_imported_store = \Drupal::keyValue('migrate_last_imported');
        $last_imported = $migrate_last_imported_store->get($migration->id(), FALSE);
        if ($last_imported) {
          /** @var DateFormatter $date_formatter */
          $date_formatter = \Drupal::service('date.formatter');
          $last_imported = $date_formatter->format($last_imported / 1000,
            'custom', 'Y-m-d H:i:s');
        }
        else {
          $last_imported = '';
        }
        $table[] = [$migration_id, $status, $source_rows, $imported, $unprocessed, $last_imported];
      }
    }
  }
  drush_print_table($table);
}

/**
 * @param string $migration_names
 */
function drush_migrate_tools_migrate_import($migration_names = '') {
  $group_names = drush_get_option('group');
  $tag_names = drush_get_option('tag');
  $all = drush_get_option('all');
  $options = [];
  if (!$all && !$group_names && !$migration_names && !$tag_names) {
    drush_set_error('MIGRATE_ERROR', dt('You must specify --all, --group, --tag or one or more migration names separated by commas'));
    return;
  }

  foreach (['limit', 'feedback', 'idlist', 'update', 'force'] as $option) {
    if (drush_get_option($option)) {
      $options[$option] = drush_get_option($option);
    }
  }

  $migrations = drush_migrate_tools_migration_list($migration_names);
  if (empty($migrations)) {
    drush_log(dt('No migrations found.'), 'error');
  }

  // Take it one group at a time, importing the migrations within each group.
  foreach ($migrations as $group_id => $migration_list) {
    array_walk($migration_list, '_drush_migrate_tools_execute_migration', $options);
  }
}

/**
 * Executes a single migration. If the --execute-dependencies option was given,
 * the migration's dependencies will also be executed first.
 *
 * @param \Drupal\migrate\Plugin\MigrationInterface $migration
 *  The migration to execute.
 * @param string $migration_id
 *  The migration ID (not used, just an artifact of array_walk()).
 * @param array $options
 *  Additional options for the migration.
 */
function _drush_migrate_tools_execute_migration(MigrationInterface $migration, $migration_id,  array $options = []) {
  $log = new DrushLogMigrateMessage();

  if (drush_get_option('execute-dependencies')) {
    if ($required_IDS = $migration->get('requirements')) {
      $manager = \Drupal::service('plugin.manager.config_entity_migration');
      $required_migrations = $manager->createInstances($required_IDS);
      $dependency_options = array_merge($options, ['is_dependency' => TRUE]);
      array_walk($required_migrations, __FUNCTION__, $dependency_options);
    }
  }
  if (!empty($options['force'])) {
    $migration->set('requirements', []);
  }
  if (!empty($options['update'])) {
    $migration->getIdMap()->prepareUpdate();
  }
  $executable = new MigrateExecutable($migration, $log, $options);
  // drush_op() provides --simulate support
  drush_op(array($executable, 'import'));
}

/**
 * @param string $migration_names
 */
function drush_migrate_tools_migrate_rollback($migration_names = '') {
  $group_names = drush_get_option('group');
  $tag_names = drush_get_option('tag');
  $all = drush_get_option('all');
  $options = [];
  if (!$all && !$group_names && !$migration_names && !$tag_names) {
    drush_set_error('MIGRATE_ERROR', dt('You must specify --all, --group, --tag, or one or more migration names separated by commas'));
    return;
  }

  if (drush_get_option('feedback')) {
    $options['feedback'] = drush_get_option('feedback');
  }

  $log = new DrushLogMigrateMessage();

  $migrations = drush_migrate_tools_migration_list($migration_names);
  if (empty($migrations)) {
    drush_log(dt('No migrations found.'), 'error');
  }

  // Take it one group at a time, rolling back the migrations within each group.
  foreach ($migrations as $group_id => $migration_list) {
    // Roll back in reverse order.
    $migration_list = array_reverse($migration_list);
    foreach ($migration_list as $migration_id => $migration) {
      $executable = new MigrateExecutable($migration, $log, $options);
      // drush_op() provides --simulate support.
      drush_op(array($executable, 'rollback'));
    }
  }
}

/**
 * @param string $migration_id
 */
function drush_migrate_tools_migrate_stop($migration_id = '') {
  /** @var MigrationInterface $migration */
  $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_id);
  if ($migration) {
    $status = $migration->getStatus();
    switch ($status) {
      case MigrationInterface::STATUS_IDLE:
        drush_log(dt('Migration @id is idle', ['@id' => $migration_id]), 'warning');
        break;
      case MigrationInterface::STATUS_DISABLED:
        drush_log(dt('Migration @id is disabled', ['@id' => $migration_id]), 'warning');
        break;
      case MigrationInterface::STATUS_STOPPING:
        drush_log(dt('Migration @id is already stopping', ['@id' => $migration_id]), 'warning');
        break;
      default:
        $migration->interruptMigration(MigrationInterface::RESULT_STOPPED);
        drush_log(dt('Migration @id requested to stop', ['@id' => $migration_id]), 'success');
        break;
    }
  }
  else {
    drush_log(dt('Migration @id does not exist', ['@id' => $migration_id]), 'error');
  }
}

/**
 * @param string $migration_id
 */
function drush_migrate_tools_migrate_reset_status($migration_id = '') {
  /** @var MigrationInterface $migration */
  $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_id);
  if ($migration) {
    $status = $migration->getStatus();
    if ($status == MigrationInterface::STATUS_IDLE) {
      drush_log(dt('Migration @id is already Idle', ['@id' => $migration_id]), 'warning');
    }
    else {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
      drush_log(dt('Migration @id reset to Idle', ['@id' => $migration_id]), 'status');
    }
  }
  else {
    drush_log(dt('Migration @id does not exist', ['@id' => $migration_id]), 'error');
  }
}

/**
 * @param string $migration_id
 */
function drush_migrate_tools_migrate_messages($migration_id) {
  /** @var MigrationInterface $migration */
  $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_id);
  if ($migration) {
    $map = $migration->getIdMap();
    $first = TRUE;
    $table = [];
    foreach ($map->getMessageIterator() as $row) {
      unset($row->msgid);
      if ($first) {
        // @todo: Ideally, replace sourceid* with source key names. Or, should
        // getMessageIterator() do that?
        foreach ($row as $column => $value) {
          $table[0][] = $column;
        }
        $first = FALSE;
      }
      $table[] = (array)$row;
    }
    if (empty($table)) {
      drush_log(dt('No messages for this migration'), 'status');
    }
    else {
      if (drush_get_option('csv')) {
        foreach ($table as $row) {
          fputcsv(STDOUT, $row);
        }
      }
      else {
        $widths = [];
        foreach ($table[0] as $header) {
          $widths[] = strlen($header) + 1;
        }
        drush_print_table($table, TRUE, $widths);
      }
    }
  }
  else {
    drush_log(dt('Migration @id does not exist', ['@id' => $migration_id]), 'error');
  }
}

/**
 * @param string $migration_id
 */
function drush_migrate_tools_migrate_fields_source($migration_id) {
  /** @var MigrationInterface $migration */
  $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_id);
  if ($migration) {
    $source = $migration->getSourcePlugin();
    $table = [];
    foreach ($source->fields() as $machine_name => $description) {
      $table[] = [strip_tags($description), $machine_name];
    }
    drush_print_table($table);
  }
  else {
    drush_log(dt('Migration @id does not exist', ['@id' => $migration_id]), 'error');
  }
}

/**
 * Retrieve a list of active migrations.
 *
 * @param string $migration_ids
 *  Comma-separated list of migrations - if present, return only these migrations.
 *
 * @return MigrationInterface[][]
 *   An array keyed by migration group, each value containing an array of
 *   migrations or an empty array if no migrations match the input criteria.
 */
function drush_migrate_tools_migration_list($migration_ids = '') {
  // Filter keys must match the migration configuration property name.
  $filter['migration_group'] = drush_get_option('group') ? explode(',', drush_get_option('group')) : [];
  $filter['migration_tags'] = drush_get_option('tag') ? explode(',', drush_get_option('tag')) : [];

  $manager = \Drupal::service('plugin.manager.config_entity_migration');
  $plugins = $manager->createInstances([]);
  $matched_migrations = [];

  // Get the set of migrations that may be filtered.
  if (empty($migration_ids)) {
    $matched_migrations  = $plugins;
  }
  else {
    // Get the requested migrations.
    $migration_ids = explode(',', Unicode::strtolower($migration_ids));
    foreach ($plugins as $id => $migration) {
      if (in_array(Unicode::strtolower($id), $migration_ids)) {
        $matched_migrations [$id] = $migration;
      }
    }
  }

  // Filters the matched migrations if a group or a tag has been input.
  if (!empty($filter['migration_group']) || !empty($filter['migration_tags'])) {
    // Get migrations in any of the specified groups and with any of the
    // specified tags.
    foreach ($filter as $property => $values) {
      if (!empty($values)) {
        $filtered_migrations = [];
        foreach ($values as $search_value) {
          foreach ($matched_migrations as $id => $migration) {
            // Cast to array because migration_tags can be an array.
            $configured_values = (array) $migration->get($property);
            $configured_id = (in_array($search_value, $configured_values)) ? $search_value : 'default';
            if (empty($search_value) || $search_value == $configured_id) {
              if (empty($migration_ids) || in_array(Unicode::strtolower($id), $migration_ids)) {
                $filtered_migrations[$id] = $migration;
              }
            }
          }
        }
        $matched_migrations = $filtered_migrations;
      }
    }
  }

  // Sort the matched migrations by group.
  if (!empty($matched_migrations)) {
    foreach ($matched_migrations as $id => $migration) {
      $configured_group_id = empty($migration->get('migration_group')) ? 'default' : $migration->get('migration_group');
      $migrations[$configured_group_id][$id] = $migration;
    }
  }
  return isset($migrations) ? $migrations : [];
}
