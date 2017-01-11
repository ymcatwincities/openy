<?php

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\ServiceTestTrait;

/**
 * Thin and generic KTB for testing services.yml exposed classes.
 *
 * @see \Drupal\purge\Tests\KernelTestBase
 * @see \Drupal\purge\Tests\ServiceTestTrait
 */
abstract class KernelServiceTestBase extends KernelTestBase {
  use ServiceTestTrait;

}
