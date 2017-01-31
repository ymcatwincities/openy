<?php

namespace Drupal\openy;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager;
use Drupal\migrate_tools\MigrateExecutable;

/**
 * Class ContentImporter.
 *
 * @package Drupal\openy
 */
class ContentImporter implements ContentImporterInterface {

  /**
   * Migration manager.
   *
   * @var \Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager
   */
  protected $migrationManager;

  /**
   * Map of content items and migrations.
   *
   * @var array
   */
  protected $map;

  /**
   * ContentImporter constructor.
   *
   * @param \Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager $migrationManager
   *   Migration manager.
   */
  public function __construct(MigrationConfigEntityPluginManager $migrationManager) {
    $this->map = $this->getMap();

    $this->migrationManager = $migrationManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getMap() {
    // @todo Move the map into config.
    return [
      'blog' => [
        'openy_demo_node_blog',
      ],
    ];
  }

  /**
   * Import single migration with dependencies.
   *
   * @param \Drupal\migrate\Plugin\Migration $migration
   *   Migration.
   */
  protected function importMigration(Migration $migration) {
    // Run dependencies first.
    $required_ids = $migration->get('requirements');
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
  public function import($item) {
    $dependencies = $this->map[$item];
    foreach ($dependencies as $migration_id) {
      $migration = $this->migrationManager->createInstance($migration_id);
      $this->importMigration($migration);
    }
  }

}
