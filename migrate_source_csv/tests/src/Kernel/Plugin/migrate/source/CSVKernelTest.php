<?php

namespace Drupal\Tests\migrate_source_csv\Unit\Plugin\migrate\source;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;

/**
 * @coversDefaultClass \Drupal\migrate_source_csv\Plugin\migrate\source\CSV
 *
 * @group migrate_source_csv
 */
class CSVTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate', 'migrate_source_csv'];

  /**
   * Tests the construction of CSV.
   *
   * @covers ::__construct
   */
  public function testCreate() {
    /** @var MigratePluginManagerInterface $migrationSourceManager */
    $migrationSourceManager = $this->container->get('plugin.manager.migrate.source');
    $this->assertTrue($migrationSourceManager->hasDefinition('csv'));
  }

}
