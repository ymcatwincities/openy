<?php

namespace Drupal\openy;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\Migration;
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
   */
  public function __construct() {
    $this->map = $this->getMap();

    // Service container are not available on the first steps of profile installation.
    $this->migrationManager = \Drupal::service('plugin.manager.config_entity_migration');
  }

  /**
   * {@inheritdoc}
   */
  public function getMap() {
    return [
      'default' => [
        'openy_demo_block_content_footer',
      ],
      'landing' => [
        'openy_demo_node_landing',
      ],
      'branches' => [
        'openy_demo_node_branch',
      ],
      'blog' => [
        'openy_demo_node_blog',
      ],
      'programs' => [
        'openy_demo_node_program',
        'openy_demo_node_program_subcategory',
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
  public function import($item) {
    $dependencies = $this->map[$item];
    foreach ($dependencies as $migration_id) {
      $migration = $this->migrationManager->createInstance($migration_id);
      $this->importMigration($migration);
    }
  }

}
