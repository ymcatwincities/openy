<?php
// @todo Write this

namespace Drupal\metatag\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensures that meta tag values can be output using JSON.
 *
 * @group metatag
 */
class MetatagJsonOutputTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'metatag',
  ];

  /**
   * @todo
   */
  public function testJson() {
  }

}
