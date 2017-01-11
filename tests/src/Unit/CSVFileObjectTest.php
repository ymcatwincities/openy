<?php

namespace Drupal\Tests\migrate_source_csv\Unit;

use Drupal\migrate_source_csv\CSVFileObject;

/**
 * @coversDefaultClass \Drupal\migrate_source_csv\CSVFileObject
 *
 * @group migrate_source_csv
 */
class CSVFileObjectTest extends CSVUnitBase {

  /**
   * The CSV file object.
   *
   * @var \Drupal\migrate_source_csv\CSVFileObject
   */
  protected $csvFileObject;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->csvFileObject = new CSVFileObject($this->happyPath);
  }

  /**
   * Tests that the construction appropriately creates a CSVFileObject.
   *
   * @covers ::__construct
   */
  public function testCreate() {
    $this->assertInstanceOf(CSVFileObject::class, $this->csvFileObject);
    $flags = CSVFileObject::READ_CSV | CSVFileObject::READ_AHEAD | CSVFileObject::DROP_NEW_LINE | CSVFileObject::SKIP_EMPTY;
    $this->assertSame($flags, $this->csvFileObject->getFlags());
  }

  /**
   * Tests that the header row count is correctly returned.
   *
   * @covers ::setHeaderRowCount
   * @covers ::getHeaderRowCount
   */
  public function testGetHeaderRowCount() {
    $expected = 2;
    $this->csvFileObject->setHeaderRowCount($expected);
    $actual = $this->csvFileObject->getHeaderRowCount();
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests that line count is correct.
   *
   * @covers ::count
   */
  public function testCountLines() {
    $expected = 15;
    $this->csvFileObject->setHeaderRowCount(1);
    $actual = $this->csvFileObject->count();

    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests that the current row is correctly returned.
   *
   * @covers ::current
   * @covers ::rewind
   * @covers ::getColumnNames
   * @covers ::setColumnNames
   */
  public function testCurrent() {
    $column_names = [
      ['id' => 'Identifier'],
      ['first_name' => 'First Name'],
      ['last_name' => 'Last Name'],
      ['email' => 'Email'],
      ['country' => 'Country'],
      ['ip_address' => 'IP Address'],
    ];
    $columns = [];
    foreach ($column_names as $values) {
      $columns[] = key($values);
    }
    $row = [
      '1',
      'Justin',
      'Dean',
      'jdean0@example.com',
      'Indonesia',
      '60.242.130.40',
    ];

    $csv_file_object = $this->csvFileObject;
    $csv_file_object->rewind();
    $current = $csv_file_object->current();
    $this->assertArrayEquals($columns, $current);

    $csv_file_object->setHeaderRowCount(1);
    $csv_file_object->rewind();
    $current = $csv_file_object->current();
    $this->assertArrayEquals($row, $current);

    $csv_file_object->setColumnNames($column_names);
    $csv_file_object->rewind();
    $current = $csv_file_object->current();
    $this->assertArrayEquals($columns, array_keys($current));
    $this->assertArrayEquals($row, array_values($current));
    $this->assertArrayEquals($column_names, $csv_file_object->getColumnNames());
  }

}
