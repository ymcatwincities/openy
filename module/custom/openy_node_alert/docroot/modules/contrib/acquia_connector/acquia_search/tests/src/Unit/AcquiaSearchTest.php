<?php

/**
 * @file
 * Contains Drupal\Tests\acquia_search\Unit\AcquiaSearchTest.
 */

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\EventSubscriber\SearchSubscriber;
use Drupal\Tests\UnitTestCase;
use Drupal\acquia_connector\CryptConnector;

if (!defined('REQUEST_TIME')) {
  define('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);
}

/**
 * @coversDefaultClass \Drupal\acquia_search\EventSubscriber\SearchSubscriber
 *
 * @group Acquia search
 */
class AcquiaSearchTest extends UnitTestCase {
  protected $id;
  protected $key;
  protected $salt;
  protected $derivedKey;
  protected $searchSubscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Generate and store a random set of credentials.
    // Make them as close to the production values as possible
    // Something like AAAA-1234.
    $this->id = $this->randomMachineName(10);
    // Most of the keys and salts have a 32char length.
    $this->key = $this->randomMachineName(32);
    $this->salt = $this->randomMachineName(32);

    // Include Solarium autoloader.
    $dirs = drupal_phpunit_contrib_extension_directory_roots();
    $extensions = [];
    foreach ($dirs as $path) {
      $extensions += drupal_phpunit_find_extension_directories($path);
    }

    unset($extensions);

    $this->searchSubscriber = new SearchSubscriber();
    $this->derivedKey = CryptConnector::createDerivedKey($this->salt, $this->id, $this->key);
  }

  /**
   * Check createDerivedKey.
   */
  public function testCreateDerivedKey() {
    // Mimic the hashing code in the API function.
    $derivation_string = $this->id . 'solr' . $this->salt;
    // str_pad extends the string with the same string in this case
    // until it has filled 80 chars.
    $derived_key = hash_hmac('sha1', str_pad($derivation_string, 80, $derivation_string), $this->key);
    // $this->derivedKey is generated from the API function.
    // @see setUp()
    $this->assertEquals($derived_key, $this->derivedKey);
  }

  /**
   * Covers calculateAuthCookie.
   *
   * @covers ::calculateAuthCookie
   */
  public function testCalculateAuthCookie() {
    // Generate the expected hash.
    $time = REQUEST_TIME;
    $nonce = $this->randomMachineName(32);
    $string = $time . $nonce . $this->randomMachineName();
    $hmac = hash_hmac('sha1', $time . $nonce . $string, $this->derivedKey);

    $calculateAuthCookie = $this->getMockBuilder('Drupal\acquia_search\EventSubscriber\SearchSubscriber')
      ->setMethods(['getDerivedKey'])
      ->getMock();
    $calculateAuthCookie->expects($this->any())
          ->method('getDerivedKey')
          ->willReturn($this->derivedKey);

    $authenticator = $calculateAuthCookie->calculateAuthCookie($string, $nonce, $this->derivedKey, $time);
    preg_match('/acquia_solr_hmac=([a-zA-Z0-9]{40});/', $authenticator, $matches);
    $this->assertEquals($hmac, $matches[1], 'HMAC API function generates the expected hmac hash.');
    preg_match('/acquia_solr_time=([0-9]{10});/', $authenticator, $matches);
    $this->assertNotNull($matches, 'HMAC API function generates a timestamp.', 'Acquia Search');
    preg_match('/acquia_solr_nonce=([a-zA-Z0-9]{32});/', $authenticator, $matches);
    $this->assertEquals($nonce, $matches[1], 'HMAC API function generates the expected nonce.');
  }

  /**
   * Covers validateResponse.
   *
   * @covers ::validateResponse
   */
  public function testValidResponse() {
    // Generate the expected hash.
    $nonce = $this->randomMachineName(32);
    $string = $this->randomMachineName(32);
    $hmac = hash_hmac('sha1', $nonce . $string, $this->derivedKey);

    // Pass the expected hmac digest, API function should return TRUE.
    $valid = $this->searchSubscriber->validateResponse($hmac, $nonce, $string, $this->derivedKey);
    $this->assertTrue($valid, 'Response flagged as valid when the expected hash is passed.');

    // Invalidate the hmac digest, API function should return FALSE.
    $bad_hmac = $hmac . 'invalidateHash';
    $invalid_hmac = $this->searchSubscriber->validateResponse($bad_hmac, $nonce, $string, $this->derivedKey);
    $this->assertFalse($invalid_hmac, 'Response flagged as invalid when a malformed hash is passed.');

    // Invalidate the nonce, API function should return FALSE.
    $bad_nonce = $nonce . 'invalidateString';
    $invalid_nonce = $this->searchSubscriber->validateResponse($hmac, $bad_nonce, $string, $this->derivedKey);
    $this->assertFalse($invalid_nonce, 'Response flagged as invalid when a malformed nonce is passed.');

    // Invalidate the string, API function should return FALSE.
    $bad_string = $string . 'invalidateString';
    $invalid_string = $this->searchSubscriber->validateResponse($hmac, $nonce, $bad_string, $this->derivedKey);
    $this->assertFalse($invalid_string, 'Response flagged as invalid when a malformed string is passed.');

    // Invalidate the derived key, API function should return FALSE.
    $bad_key = $this->derivedKey . 'invalidateKey';
    $invalid_key = $this->searchSubscriber->validateResponse($hmac, $nonce, $string, $bad_key);
    $this->assertFalse($invalid_key, 'Response flagged as invalid when a malformed derived key is passed.');
  }

  /**
   * Covers extractHmac.
   *
   * @covers ::extractHmac
   */
  public function testExtractHmacHeader() {
    // Generate the expected hash.
    $nonce = $this->randomMachineName(32);
    $string = $this->randomMachineName(32);
    $hmac = hash_hmac('sha1', $nonce . $string, $this->derivedKey);

    // Pass header with an expected pragma.
    $headers = array('pragma/hmac_digest=' . $hmac . ';');
    $extracted = $this->searchSubscriber->extractHmac($headers);
    $this->assertEquals($hmac, $extracted, 'The HMAC digest was extracted from the response header.');

    // Pass header with a bad pragma.
    $bad_headers1 = array('pragma/' . $this->randomMachineName());
    $bad_extracted1 = $this->searchSubscriber->extractHmac($bad_headers1);
    $this->assertEquals('', $bad_extracted1, 'Empty string returned by HMAC extraction function when an invalid pragma is passed.');

    // Pass in junk as the header.
    $bad_extracted2 = $this->searchSubscriber->extractHmac($this->randomMachineName());
    $this->assertEquals('', $bad_extracted2, 'Empty string returned by HMAC extraction function when an invalid header is passed.');
  }

}
