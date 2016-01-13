<?php

/**
 * @file
 * Contains \Drupal\token\Tests\TokenKernelTestBase.
 */

namespace Drupal\token\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Helper test class with some added functions for testing.
 */
abstract class TokenKernelTestBase extends KernelTestBase {

  use TokenTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('path', 'token', 'token_test', 'system', 'user');
  
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router', 'url_alias']);
    \Drupal::service('router.builder')->rebuild();
    $this->installConfig(['system']);
  }

}
