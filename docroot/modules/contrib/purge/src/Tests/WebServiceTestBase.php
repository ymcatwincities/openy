<?php

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\WebTestBase;
use Drupal\purge\Tests\ServiceTestTrait;

/**
 * Thin and generic WTB for testing services.yml exposed classes.
 *
 * @see \Drupal\purge\Tests\WebTestBase
 * @see \Drupal\purge\Tests\ServiceTestTrait
 */
abstract class WebServiceTestBase extends WebTestBase {
  use ServiceTestTrait;

}
