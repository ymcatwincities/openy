<?php

namespace Drupal\Tests\migrate_source_csv\Unit\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_source_csv\CSVFileObject;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;
use Drupal\Tests\migrate_source_csv\Unit\CSVUnitBase;

/**
 * @coversDefaultClass \Drupal\migrate_source_csv\Plugin\migrate\source\CSV
 *
 * @group migrate_source_csv
 */
class CSVUnitTest extends CSVUnitBase {

  /**
   * The plugin id.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * The migration plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->pluginId = 'test csv migration';
    $this->pluginDefinition = [];
    $migration = $this->prophesize(MigrationInterface::class);
    $migration->getIdMap()
      ->willReturn(NULL);

    $this->migration = $migration->reveal();
  }

  /**
   * Tests the construction of CSV.
   *
   * @covers ::__construct
   */
  public function testCreate() {
    $configuration = [
      'path' => $this->happyPath,
      'keys' => ['id'],
      'header_row_count' => 1,
    ];

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->migration);

    $this->assertInstanceOf(CSV::class, $csv);
  }

  /**
   * Tests that a missing path will throw an exception.
   *
   * @expectedException \Drupal\migrate\MigrateException
   *
   * @expectedExceptionMessage You must declare the "path" to the source CSV file in your source settings.
   */
  public function testMigrateExceptionPathMissing() {
    new CSV([], $this->pluginId, $this->pluginDefinition, $this->migration);
  }

  /**
   * Tests that missing keys will throw an exception.
   *
   * @expectedException \Drupal\migrate\MigrateException
   *
   * @expectedExceptionMessage You must declare "keys" as a unique array of fields in your source settings.
   */
  public function testMigrateExceptionKeysMissing() {
    $configuration = [
      'path' => $this->happyPath,
    ];

    new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->migration);
  }

  /**
   * Tests that toString functions as expected.
   *
   * @covers ::__toString
   */
  public function testToString() {
    $configuration = [
      'path' => $this->happyPath,
      'keys' => ['id'],
      'header_row_count' => 1,
    ];

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->migration);

    $this->assertEquals($configuration['path'], (string) $csv);
  }

  /**
   * Tests initialization of the iterator.
   *
   * @covers ::initializeIterator
   */
  public function testInitializeIterator() {
    $configuration = [
      'path' => $this->happyPath,
      'keys' => ['id'],
      'header_row_count' => 1,
    ];

    $config_common = [
      'path' => $this->sad,
      'keys' => ['id'],
    ];
    $config_delimiter = ['delimiter' => '|'];
    $config_enclosure = ['enclosure' => '%'];
    $config_escape = ['escape' => '`'];

    $csv = new CSV($config_common + $config_delimiter, $this->pluginId, $this->pluginDefinition, $this->migration);
    $this->assertEquals(current($config_delimiter), $csv->initializeIterator()
      ->getCsvControl()[0]);
    $this->assertEquals('"', $csv->initializeIterator()->getCsvControl()[1]);

    $csv = new CSV($config_common + $config_enclosure, $this->pluginId, $this->pluginDefinition, $this->migration);
    $this->assertEquals(',', $csv->initializeIterator()->getCsvControl()[0]);
    $this->assertEquals(current($config_enclosure), $csv->initializeIterator()
      ->getCsvControl()[1]);

    $csv = new CSV($config_common + $config_delimiter + $config_enclosure + $config_escape, $this->pluginId, $this->pluginDefinition, $this->migration);
    $csv_file_object = $csv->initializeIterator();
    $row = [
      '1',
      'Justin',
      'Dean',
      'jdean0@example.com',
      'Indonesia',
      '60.242.130.40',
    ];
    $csv_file_object->rewind();
    $current = $csv_file_object->current();
    $this->assertArrayEquals($row, $current);

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->migration);
    $csv_file_object = $csv->initializeIterator();
    $row = [
      'id' => '1',
      'first_name' => 'Justin',
      'last_name' => 'Dean',
      'email' => 'jdean0@example.com',
      'country' => 'Indonesia',
      'ip_address' => '60.242.130.40',
    ];
    $second_row = [
      'id' => '2',
      'first_name' => 'Joan',
      'last_name' => 'Jordan',
      'email' => 'jjordan1@example.com',
      'country' => 'Thailand',
      'ip_address' => '137.230.209.171',
    ];

    $csv_file_object->rewind();
    $current = $csv_file_object->current();
    $this->assertArrayEquals($row, $current);
    $csv_file_object->next();
    $next = $csv_file_object->current();
    $this->assertArrayEquals($second_row, $next);

    $column_names = [
      'column_names' => [
        0 => ['id' => 'identifier'],
        2 => ['last_name' => 'User last name'],
      ],
    ];
    $csv = new CSV($configuration + $column_names, $this->pluginId, $this->pluginDefinition, $this->migration);
    $csv_file_object = $csv->initializeIterator();
    $row = [
      'id' => '1',
      'last_name' => 'Dean',
    ];
    $second_row = [
      'id' => '2',
      'last_name' => 'Jordan',
    ];

    $csv_file_object->rewind();
    $current = $csv_file_object->current();
    $this->assertArrayEquals($row, $current);
    $csv_file_object->next();
    $next = $csv_file_object->current();
    $this->assertArrayEquals($second_row, $next);
  }

  /**
   * Tests that the key is properly identified.
   *
   * @covers ::getIds
   */
  public function testGetIds() {
    $configuration = [
      'path' => $this->happyPath,
      'keys' => ['id'],
      'header_row_count' => 1,
    ];

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->migration);

    $expected = ['id' => ['type' => 'string']];
    $this->assertArrayEquals($expected, $csv->getIds());
  }

  /**
   * Tests that the key is properly identified.
   *
   * @covers ::getIds
   */
  public function testGetIdsComplex() {
    $configuration = [
      'path' => $this->happyPath,
      'keys' => [
        'id',
        'paragraph' => [
          'type' => 'text',
          'size' => 'big',
        ],
      ],
      'header_row_count' => 1,
    ];

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->migration);

    $expected = [
      'id' => [
        'type' => 'string',
      ],
      'paragraph' => [
        'type' => 'text',
        'size' => 'big',
      ],
    ];
    $this->assertArrayEquals($expected, $csv->getIds());
  }

  /**
   * Tests that fields have a machine name and description.
   *
   * @covers ::fields
   */
  public function testFields() {
    $configuration = [
      'path' => $this->happyPath,
      'keys' => ['id'],
      'header_row_count' => 1,
    ];
    $fields = [
      'id' => 'identifier',
      'first_name' => 'User first name',
    ];

    $expected = $fields + [
        'last_name' => 'last_name',
        'email' => 'email',
        'country' => 'country',
        'ip_address' => 'ip_address',
      ];

    $csv = new CSV($configuration + ['fields' => $fields], $this->pluginId, $this->pluginDefinition, $this->migration);
    $this->assertArrayEquals($expected, $csv->fields());

    $column_names = [
      0 => ['id' => 'identifier'],
      2 => ['first_name' => 'User first name'],
    ];
    $csv = new CSV($configuration + [
        'fields' => $fields,
        'column_names' => $column_names,
      ], $this->pluginId, $this->pluginDefinition, $this->migration);
    $this->assertArrayEquals($fields, $csv->fields());
  }

  /**
   * Tests configurable CSV file object.
   *
   * @covers ::__construct
   */
  public function testConfigurableCSVFileObject() {
    $configuration = [
      'path' => $this->happyPath,
      'keys' => ['id'],
      'header_row_count' => 1,
      'file_class' => FooCSVFileObject::class ,
    ];

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->migration);
    $csv->initializeIterator();
    $fileObject = $this->readAttribute($csv, 'file');

    $this->assertInstanceOf(FooCSVFileObject::class, $fileObject);
  }

}

/**
 * Class FooCSVFileObject
 *
 * Test file object class.
 *
 * @package Drupal\Tests\migrate_source_csv\Unit\Plugin\migrate\source
 */
class FooCSVFileObject extends CSVFileObject { }
