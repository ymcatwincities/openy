<?php

/**
 * @file
 * Contains \Drupal\migrate_tools\MigrateManifest
 */

namespace Drupal\migrate_tools;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\Entity\Migration;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Database\Database;

class MigrateManifest {

  /**
   * The path to the manifest file.
   *
   * @var string
   */
  protected $manifestFile;

  /**
   * The list of migrations to run and their configuration.
   *
   * @var array
   */
  protected $migrationList;

  /**
   * The message log.
   *
   * @var \Drupal\migrate_tools\DrushLogMigrateMessage
   */
  protected $log;

  /**
   * Constructs a new MigrateManifest object.
   */
  public function __construct($manifest_file) {
    $this->manifestFile = $manifest_file;
    $this->migrationList = Yaml::parse($this->manifestFile);
    $this->log = new DrushLogMigrateMessage();

    if (!file_exists($this->manifestFile)) {
      throw new FileNotFoundException('The manifest file does not exist.');
    }

    if (!is_array($this->migrationList)) {
      throw new ParseException('The manifest file cannot be parsed.');
    }
  }

  /**
   * Drush execution method. Runs imports on the supplied manifest.
   */
  public function import() {
    /** @var \Drupal\migrate\MigrateTemplateStorage $template_storage */
    $template_storage = \Drupal::service('migrate.template_storage');

    $this->setupLegacyDb();
    $migration_ids = [];
    $migrations = [];

    foreach ($this->migrationList as $migration_info) {
      if (is_array($migration_info)) {
        // The migration is stored as the key in the info array.
        $migration_id = key($migration_info);
      }
      else {
        // If it wasn't an array then the info is just the migration_id.
        $migration_id = $migration_info;
      }
      $migration_ids[] = $migration_id;

      $template = $template_storage->getTemplateByName($migration_id) ?: [];
      if (is_array($migration_info)) {
        // If there is some existing global overrides then we merge them in.
        if (isset($GLOBALS['config'][$migration_id])) {
          $migration_info = NestedArray::mergeDeep($GLOBALS['config'][$migration_id], $migration_info);
        }

        $migration_info = NestedArray::mergeDeep($template, $migration_info);
      }
      else {
        $migration_info = $template;
      }

      if ($migration_info && !Migration::load($migration_id)) {
        $migration = Migration::create($migration_info);
        $migration->save();
      }
    }

    // Load all the migrations at once so they're correctly ordered.
    foreach (entity_load_multiple('migration', $migration_ids) as $migration) {
      $executable = $this->executeMigration($migration);
      // Store all the migrations for later.
      $migrations[$migration->id()] = array(
        'executable' => $executable,
        'migration' => $migration,
        'source' => $migration->get('source'),
        'destination' => $migration->get('destination'),
      );
    }

    // Warn the user if any migrations were not found.
    $nonexistent_migrations = array_diff($migration_ids, array_keys($migrations));
    if (count($nonexistent_migrations) > 0) {
      drush_log(dt('The following migrations were not found: @migrations', array(
        '@migrations' => implode(', ', $nonexistent_migrations),
      )), 'warning');
    }

    return $migrations;
  }

  /**
   * Execute a single migration.
   *
   * @param \Drupal\migrate\Entity\Migration $migration
   *   The migration to run.
   *
   * @return \Drupal\migrate_tools\MigrateExecutable
   *   The migration executable.
   */
  protected function executeMigration($migration) {
    drush_log('Running ' . $migration->id(), 'ok');
    $executable = new MigrateExecutable($migration, $this->log);
    // drush_op() provides --simulate support.
    drush_op(array($executable, 'import'));

    return $executable;
  }

  /**
   * Setup the legacy database connection to migrate from.
   */
  protected function setupLegacyDb() {
    $db_url = drush_get_option('legacy-db-url');
    $db_spec = drush_convert_db_from_db_url($db_url);
    Database::addConnectionInfo('migrate', 'default', $db_spec);
  }

}
