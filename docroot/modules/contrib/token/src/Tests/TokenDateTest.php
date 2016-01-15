<?php

/**
 * @file
 * Contains \Drupal\token\Tests\TokenDateTest.
 */

namespace Drupal\token\Tests;

/**
 * Tests date tokens.
 *
 * @group token
 */
class TokenDateTest extends TokenKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system', 'token_test']);
  }

  function testDateTokens() {
    $tokens = array(
      'token_test' => '1984',
      'invalid_format' => NULL,
    );

    $this->assertTokens('date', array('date' => 453859200), $tokens);
  }
}
