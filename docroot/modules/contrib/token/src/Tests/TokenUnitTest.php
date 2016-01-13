<?php

/**
 * @file
 * Contains \Drupal\token\Tests\TokenUnitTest.
 */
namespace Drupal\token\Tests;

/**
 * Test basic, low-level token functions.
 *
 * @group token
 */
class TokenUnitTest extends TokenKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file', 'node');

  /**
   * Test token_get_invalid_tokens() and token_get_invalid_tokens_by_context().
   */
  public function testGetInvalidTokens() {
    $tests = array();
    $tests[] = array(
      'valid tokens' => array(
        '[node:title]',
        '[node:created:short]',
        '[node:created:custom:invalid]',
        '[node:created:custom:mm-YYYY]',
        '[site:name]',
        '[site:slogan]',
        '[current-date:short]',
        '[current-user:uid]',
        '[current-user:ip-address]',
      ),
      'invalid tokens' => array(
        '[node:title:invalid]',
        '[node:created:invalid]',
        '[node:created:short:invalid]',
        '[invalid:title]',
        '[site:invalid]',
        '[user:ip-address]',
        '[user:uid]',
        '[comment:cid]',
        // Deprecated tokens
        '[node:tnid]',
        '[node:type]',
        '[node:type-name]',
        '[date:short]',
      ),
      'types' => array('node'),
    );
    $tests[] = array(
      'valid tokens' => array(
        '[node:title]',
        '[node:created:short]',
        '[node:created:custom:invalid]',
        '[node:created:custom:mm-YYYY]',
        '[site:name]',
        '[site:slogan]',
        '[user:uid]',
        '[current-date:short]',
        '[current-user:uid]',
      ),
      'invalid tokens' => array(
        '[node:title:invalid]',
        '[node:created:invalid]',
        '[node:created:short:invalid]',
        '[invalid:title]',
        '[site:invalid]',
        '[user:ip-address]',
        '[comment:cid]',
        // Deprecated tokens
        '[node:tnid]',
        '[node:type]',
        '[node:type-name]',
      ),
      'types' => array('all'),
    );

    foreach ($tests as $test) {
      $tokens = array_merge($test['valid tokens'], $test['invalid tokens']);
      shuffle($tokens);

      $invalid_tokens = token_get_invalid_tokens_by_context(implode(' ', $tokens), $test['types']);

      sort($invalid_tokens);
      sort($test['invalid tokens']);
      $this->assertEqual($invalid_tokens, $test['invalid tokens'], 'Invalid tokens detected properly: ' . implode(', ', $invalid_tokens));
    }
  }
}
