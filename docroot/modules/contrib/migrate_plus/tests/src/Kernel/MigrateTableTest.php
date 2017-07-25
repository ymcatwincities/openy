<?php

namespace Drupal\Tests\migrate_plus\Kernel;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests migration destination table.
 *
 * @group migrate
 */
class MigrateTableTest extends MigrateTestBase {

  const TABLE_NAME = 'migrate_test_destination_table';

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  public static $modules = ['migrate_plus'];

  protected function setUp() {
    parent::setUp();

    $this->connection = Database::getConnection();

    $this->connection->schema()->createTable(static::TABLE_NAME, [
      'description' => 'Test table',
      'fields' => [
        'data' => [
          'type' => 'varchar',
          'length' => '32',
          'not null' => TRUE,
        ],
        'data2' => [
          'type' => 'varchar',
          'length' => '32',
          'not null' => TRUE,
        ],
        'data3' => [
          'type' => 'varchar',
          'length' => '32',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['data'],
    ]);
  }

  protected function tearDown() {
    $this->connection->schema()->dropTable(static::TABLE_NAME);
    parent::tearDown();
  }

  protected function getTableDestinationMigration() {
    // Create a minimally valid migration with some source data.
    $definition = [
      'id' => 'migration_table_test',
      'migration_tags' => ['Testing'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          ['data' => 'dummy value', 'data2' => 'dummy2 value', 'data3' => 'dummy3 value'],
          ['data' => 'dummy value2', 'data2' => 'dummy2 value2', 'data3' => 'dummy3 value2'],
          ['data' => 'dummy value3', 'data2' => 'dummy2 value3', 'data3' => 'dummy3 value3'],
        ],
        'ids' => [
          'data' => ['type' => 'string'],
        ],
      ],
      'destination' => [
        'plugin' => 'table',
        'table_name' => static::TABLE_NAME,
        'id_fields' => ['data' => ['type' => 'string']],
      ],
      'process' => [
        'data' => 'data',
        'data2' => 'data2',
        'data3' => 'data3',
      ],
    ];
    return $definition;
  }

  /**
   * Tests table destination.
   */
  public function testTableDestination() {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($this->getTableDestinationMigration());

    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $values = $this->connection->select(static::TABLE_NAME)
      ->fields(static::TABLE_NAME)
      ->execute()
      ->fetchAllAssoc('data');

    $this->assertEquals('dummy value', $values['dummy value']->data);
    $this->assertEquals('dummy2 value', $values['dummy value']->data2);
    $this->assertEquals('dummy2 value2', $values['dummy value2']->data2);
    $this->assertEquals('dummy3 value3', $values['dummy value3']->data3);
    $this->assertEquals(3, count($values));
  }

  public function testTableRollback() {
    $this->testTableDestination();

    /** @var MigrationInterface $migration */
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($this->getTableDestinationMigration());
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $values = $this->connection->select(static::TABLE_NAME)
      ->fields(static::TABLE_NAME)
      ->execute()
      ->fetchAllAssoc('data');

    $this->assertEquals('dummy value', $values['dummy value']->data);
    $this->assertEquals(3, count($values));

    // Now rollback.
    $executable->rollback();
    $values = $this->connection->select(static::TABLE_NAME)
      ->fields(static::TABLE_NAME)
      ->execute()
      ->fetchAllAssoc('data');

    $this->assertEquals(0, count($values));
  }

}
