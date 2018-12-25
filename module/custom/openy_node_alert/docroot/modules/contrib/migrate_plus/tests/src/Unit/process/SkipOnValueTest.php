<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_plus\Unit\process\SkipOnValueTest.
 */

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\migrate_plus\Plugin\migrate\process\SkipOnValue;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the skip on value process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\SkipOnValue
 */
class SkipOnValueTest extends MigrateProcessTestCase {

  /**
   * @covers ::process
   * @expectedException \Drupal\migrate\MigrateSkipProcessException
   */
  public function testProcessSkipsOnValue() {
    $configuration['method'] = 'process';
    $configuration['value'] = 86;
    (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('86', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::process
   * @expectedException \Drupal\migrate\MigrateSkipProcessException
   */
  public function testProcessSkipsOnMultipleValue() {
    $configuration['method'] = 'process';
    $configuration['value'] = [1, 1, 2, 3, 5, 8];
    (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('5', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::process
   */
  public function testProcessBypassesOnNonValue() {
    $configuration['method'] = 'process';
    $configuration['value'] = 'sourcevalue';
    $configuration['not_equals'] = TRUE;
    $value = (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('sourcevalue', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($value, 'sourcevalue');
    $configuration['value'] = 86;
    $value = (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('86', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($value, '86');
  }

  /**
   * @covers ::process
   */
  public function testProcessSkipsOnMultipleNonValue() {
    $configuration['method'] = 'process';
    $configuration['value'] = [1, 1, 2, 3, 5, 8];
    $value = (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform(4, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($value, '4');
  }

  /**
   * @covers ::row
   * @expectedException \Drupal\migrate\MigrateSkipRowException
   */
  public function testRowSkipsOnValue() {
    $configuration['method'] = 'row';
    $configuration['value'] = 86;
    (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('86', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::row
   */
  public function testRowBypassesOnNonValue() {
    $configuration['method'] = 'row';
    $configuration['value'] = 'sourcevalue';
    $configuration['not_equals'] = TRUE;
    $value = (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('sourcevalue', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($value, 'sourcevalue');
    $configuration['value'] = 86;
    $value = (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('86', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($value, 86);
  }

  /**
   * @covers ::row
   * @expectedException \Drupal\migrate\MigrateException
   */
  public function testRequiredRowConfiguration() {
    $configuration['method'] = 'row';
    (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('sourcevalue', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::process
   * @expectedException \Drupal\migrate\MigrateException
   */
  public function testRequiredProcessConfiguration() {
    $configuration['method'] = 'process';
    (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('sourcevalue', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
