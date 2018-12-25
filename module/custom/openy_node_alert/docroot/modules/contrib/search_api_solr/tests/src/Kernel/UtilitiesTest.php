<?php

namespace Drupal\Tests\search_api_solr\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api_solr\Utility\Utility;

/**
 * Provides tests for various utility functions.
 *
 * @group search_api_solr
 */
class UtilitiesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'search_api_solr',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests encoding and decoding of Solr field names.
   */
  public function testFieldNameEncoder() {
    $allowed_characters_pattern = '/[a-zA-Z\d_]/';
    $forbidden_field_name = 'forbidden$field_nameÜöÄ*:/;#last_XMas';
    $expected_encoded_field_name = 'forbidden_X24_field_name_Xc39c__Xc3b6__Xc384__X2a__X3a__X2f__X3b__X23_last_X5f58_Mas';
    $encoded_field_name = Utility::encodeSolrName($forbidden_field_name);

    $this->assertEquals($encoded_field_name, $expected_encoded_field_name);

    preg_match_all($allowed_characters_pattern, $encoded_field_name, $matches);
    $this->assertEquals(count($matches[0]), strlen($encoded_field_name), 'Solr field name consists of allowed characters.');

    $decoded_field_name = Utility::decodeSolrName($encoded_field_name);

    $this->assertEquals($decoded_field_name, $forbidden_field_name);

    $this->assertEquals('ss_field_foo', Utility::encodeSolrName('ss_field_foo'));
  }

}
