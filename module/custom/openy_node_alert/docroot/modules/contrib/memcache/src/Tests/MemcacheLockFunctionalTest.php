<?php

/**
 * @file
 * Contains \Drupal\memcache\Tests\MemcacheLockFunctionalTest.
 */

namespace Drupal\memcache\Tests;

use Drupal\Tests\system\Functional\Lock\LockFunctionalTest;

/**
 * Confirm locking works between two separate requests.
 *
 * @group memcache
 */
class MemcacheLockFunctionalTest extends LockFunctionalTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system_test', 'memcache', 'memcache_test'];

}

