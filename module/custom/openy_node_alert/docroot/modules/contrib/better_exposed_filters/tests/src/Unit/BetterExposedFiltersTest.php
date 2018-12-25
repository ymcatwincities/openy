<?php

/**
 * @file
 * Contains \Drupal\better_exposed_filters\Tests\BetterExposedFiltersTest.
 */

namespace Drupal\better_exposed_filters\Tests;

use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters
 * @group bef
 */
class BetterExposedFiltersTest extends UnitTestCase {

  public function providerTestRewriteOptions() {
    $data = [];

    // Super basic rewrite.
    $data[] = [
      ['foo' => 'bar'],
      "bar|baz",
      ['foo' => 'baz'],
    ];

    // Removes an option.
    $data[] = [
      ['foo' => 'bar'],
      "bar|",
      [],
    ];

    // An option in the middle is removed -- preserves order.
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      "2|",
      ['foo' => '1', 'baz' => '3'],
    ];

    // Ensure order is preserved.
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      "2|Two",
      ['foo' => '1', 'bar' => 'Two', 'baz' => '3'],
    ];

    // No options are replaced.
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      "4|Two",
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
    ];

    return $data;
  }

  /**
   * Tests options are rewritten correctly.
   *
   * @dataProvider providerTestRewriteOptions
   * @covers ::rewriteOptions
   */
  public function testRewriteOptions($options, $rewriteSettings, $expected) {
    $bef = new TestBEF([], 'default', []);
    $actual = $bef->testRewriteOptions($options, $rewriteSettings);
    $this->assertEquals($expected, $actual);
  }

}

// Allows access to otherwise protected methods in BEF.
class TestBEF extends BetterExposedFilters {

  public function testRewriteOptions($options, $rewriteSettings) {
    return $this->rewriteOptions($options, $rewriteSettings);
  }

}
