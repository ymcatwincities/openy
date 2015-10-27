<?php
/**
 * @file
 * Code for CSVTest.php.
 */

namespace Drupal\Tests\migrate_source_csv\Unit\Plugin\migrate\source;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;
use Drupal\Tests\migrate_source_csv\Unit\CSVUnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate_source_csv\Plugin\migrate\source\CSV
 *
 * @group migrate_source_csv
 */
class CSVTest extends CSVUnitTestCase {

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
   * The mock migration plugin.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->pluginId = 'test csv migration';
    $this->pluginDefinition = array();
    $this->plugin = $this->getMock('\Drupal\migrate\Entity\MigrationInterface');
  }

  /**
   * Tests the construction of CSV.
   *
   * @test
   *
   * @covers ::__construct
   */
  public function create() {
    $configuration = array(
      'path' => $this->happyPath,
      'identifiers' => array('id'),
      'header_row_count' => 1,
    );

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->plugin);

    $this->assertInstanceOf('\Drupal\migrate_source_csv\Plugin\migrate\source\CSV', $csv);
  }

  /**
   * Tests that a missing path will throw an exception.
   *
   * @test
   *
   * @expectedException \Drupal\migrate\MigrateException
   *
   * @expectedExceptionMessage You must declare the "path" to the source CSV file in your source settings.
   */
  public function migrateExceptionPathMissing() {
    new CSV(array(), $this->pluginId, $this->pluginDefinition, $this->plugin);
  }

  /**
   * Tests that missing identifiers will throw an exception.
   *
   * @test
   *
   * @expectedException \Drupal\migrate\MigrateException
   *
   * @expectedExceptionMessage You must declare "identifiers" as a unique array of fields in your source settings.
   */
  public function migrateExceptionIdentifiersMissing() {
    $configuration = array(
      'path' => $this->happyPath,
    );

    new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->plugin);
  }

  /**
   * Tests that toString functions as expected.
   *
   * @test
   *
   * @covers ::__toString
   */
  public function toString() {
    $configuration = array(
      'path' => $this->happyPath,
      'identifiers' => array('id'),
      'header_row_count' => 1,
    );

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->plugin);

    $this->assertEquals($configuration['path'], (string) $csv);
  }

  /**
   * Tests initialization of the iterator.
   *
   * @test
   *
   * @covers ::initializeIterator
   */
  public function initializeIterator() {
    $configuration = array(
      'path' => $this->happyPath,
      'identifiers' => array('id'),
      'header_row_count' => 1,
    );

    $config_common = array(
      'path' => $this->sad,
      'identifiers' => array('id'),
    );
    $config_delimiter = array('delimiter' => '|');
    $config_enclosure = array('enclosure' => '%');
    $config_escape = array('escape' => '`');

    $csv = new CSV($config_common + $config_delimiter, $this->pluginId, $this->pluginDefinition, $this->plugin);
    $this->assertEquals(current($config_delimiter), $csv->initializeIterator()
      ->getCsvControl()[0]);
    $this->assertEquals('"', $csv->initializeIterator()->getCsvControl()[1]);

    $csv = new CSV($config_common + $config_enclosure, $this->pluginId, $this->pluginDefinition, $this->plugin);
    $this->assertEquals(',', $csv->initializeIterator()->getCsvControl()[0]);
    $this->assertEquals(current($config_enclosure), $csv->initializeIterator()
      ->getCsvControl()[1]);

    $csv = new CSV($config_common + $config_delimiter + $config_enclosure + $config_escape, $this->pluginId, $this->pluginDefinition, $this->plugin);
    $csv_file_object = $csv->getIterator();
    $row = array(
      '1',
      'Justin',
      'Dean',
      'jdean0@example.com',
      'Indonesia',
      '60.242.130.40',
    );
    $csv_file_object->rewind();
    $current = $csv_file_object->current();
    $this->assertArrayEquals($row, $current);

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->plugin);
    $csv_file_object = $csv->getIterator();
    $row = array(
      'id' => '1',
      'first_name' => 'Justin',
      'last_name' => 'Dean',
      'email' => 'jdean0@example.com',
      'country' => 'Indonesia',
      'ip_address' => '60.242.130.40',
    );
    $second_row = array(
      'id' => '2',
      'first_name' => 'Joan',
      'last_name' => 'Jordan',
      'email' => 'jjordan1@example.com',
      'country' => 'Thailand',
      'ip_address' => '137.230.209.171',
    );

    $csv_file_object->rewind();
    $current = $csv_file_object->current();
    $this->assertArrayEquals($row, $current);
    $csv_file_object->next();
    $next = $csv_file_object->current();
    $this->assertArrayEquals($second_row, $next);

    $column_names = array(
      'column_names' => array(
        0 => array('id' => 'identifier'),
        2 => array('last_name' => 'User last name'),
      ),
    );
    $csv = new CSV($configuration + $column_names, $this->pluginId, $this->pluginDefinition, $this->plugin);
    $csv_file_object = $csv->getIterator();
    $row = array(
      'id' => '1',
      'last_name' => 'Dean',
    );
    $second_row = array(
      'id' => '2',
      'last_name' => 'Jordan',
    );

    $csv_file_object->rewind();
    $current = $csv_file_object->current();
    $this->assertArrayEquals($row, $current);
    $csv_file_object->next();
    $next = $csv_file_object->current();
    $this->assertArrayEquals($second_row, $next);
  }

  /**
   * Tests that the identifier or key is properly identified.
   *
   * @test
   *
   * @covers ::getIds
   */
  public function getIds() {
    $configuration = array(
      'path' => $this->happyPath,
      'identifiers' => array('id'),
      'header_row_count' => 1,
    );

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->plugin);

    $expected = array('id' => array('type' => 'string'));
    $this->assertArrayEquals($expected, $csv->getIds());
  }

  /**
   * Tests that fields have a machine name and description.
   *
   * @test
   *
   * @covers ::fields
   */
  public function fields() {
    $configuration = array(
      'path' => $this->happyPath,
      'identifiers' => array('id'),
      'header_row_count' => 1,
    );
    $fields = array(
      'id' => 'identifier',
      'first_name' => 'User first name',
    );

    $expected = $fields + array(
      'last_name' => 'last_name',
      'email' => 'email',
      'country' => 'country',
      'ip_address' => 'ip_address',
    );

    $csv = new CSV($configuration, $this->pluginId, $this->pluginDefinition, $this->plugin);
    $csv = new CSV($configuration + array('fields' => $fields), $this->pluginId, $this->pluginDefinition, $this->plugin);
    $this->assertArrayEquals($expected, $csv->fields());

    $column_names = array(
      0 => array('id' => 'identifier'),
      2 => array('first_name' => 'User first name'),
    );
    $csv = new CSV($configuration + array('fields' => $fields, 'column_names' => $column_names), $this->pluginId, $this->pluginDefinition, $this->plugin);
    $this->assertArrayEquals($fields, $csv->fields());
  }

}
