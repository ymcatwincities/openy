<?php

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\TestTrait;
use Drupal\simpletest\WebTestBase as RealWebTestBase;

/**
 * Thin and generic WTB for purge tests.
 *
 * @see \Drupal\simpletest\WebTestBase
 * @see \Drupal\purge\Tests\TestTrait
 */
abstract class WebTestBase extends RealWebTestBase {
  use TestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge'];

  /**
   * Set up the test object.
   *
   * @param bool $switch_to_memory_queue
   *   Whether to switch the default queue to the memory backend or not.
   *
   */
  public function setUp($switch_to_memory_queue = TRUE) {
    parent::setUp();

    // The default 'database' queue backend gives issues, switch to 'memory'.
    if ($switch_to_memory_queue) {
      $this->setMemoryQueue();
    }
  }

}
