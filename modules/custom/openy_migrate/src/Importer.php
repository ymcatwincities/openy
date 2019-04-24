<?php

namespace Drupal\openy_migrate;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate_tools\MigrateExecutable;

/**
 * Class Importer.
 *
 * @package Drupal\openy
 */
class Importer implements ImporterInterface {

  /**
   * Migration manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationManager;

  /**
   * Importer constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $migrationManager
   *   Migration manager.
   */
  public function __construct(MigrationPluginManager $migrationManager) {
    $this->migrationManager = $migrationManager;
  }

  /**
   * Import single migration with dependencies.
   *
   * @param \Drupal\migrate\Plugin\Migration $migration
   *   Migration.
   */
  protected function importMigration(Migration $migration) {
    // Run dependencies first.
    $dependencies = $migration->getMigrationDependencies();
    $required_ids = $dependencies['required'];
    if ($required_ids) {
      $required_migrations = $this->migrationManager->createInstances($required_ids);
      array_walk($required_migrations, [$this, 'importMigration']);
    }

    $message = new MigrateMessage();
    $executable = new MigrateExecutable($migration, $message);
    $executable->import();
  }

  /**
   * {@inheritdoc}
   */
  public function import($migration_id) {
    $migration = $this->migrationManager->createInstance($migration_id);
    $this->importMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function importByTag($migration_tag) {
    $migrations = $this->migrationManager->createInstancesByTag($migration_tag);
    foreach ($migrations as $migration) {
      $this->importMigration($migration);
    }
  }

}
