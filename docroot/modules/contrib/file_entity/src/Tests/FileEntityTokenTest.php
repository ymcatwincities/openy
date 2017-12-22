<?php

namespace Drupal\file_entity\Tests;

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Tests the file entity tokens.
 *
 * @group file_entity
 */
class FileEntityTokenTest extends FileEntityTestBase {

  function setUp() {
    parent::setUp();
    $this->setUpFiles();
  }

  function testFileEntityTokens() {
    $tokens = array(
      'type' => 'Document',
      'type:name' => 'Document',
      'type:machine-name' => 'document',
      'type:count' => count($this->files['text']),
    );
    $this->assertTokens('file', array('file' => $this->files['text'][0]), $tokens);

    $tokens = array(
      'type' => 'Image',
      'type:name' => 'Image',
      'type:machine-name' => 'image',
      'type:count' => count($this->files['image']),
    );
    $this->assertTokens('file', array('file' => $this->files['image'][0]), $tokens);
  }

  function assertTokens($type, array $data, array $tokens, array $options = array()) {
    $token_input = array_combine(array_keys($tokens), array_keys($tokens));
    $bubbleable_metadata = new BubbleableMetadata();
    $values = \Drupal::token()->generate($type, $token_input, $data, $options, $bubbleable_metadata);
    foreach ($tokens as $token => $expected) {
      if (!isset($expected)) {
        $this->assertTrue(!isset($values[$token]), t("Token value for [@type:@token] was not generated.", array('@type' => $type, '@token' => $token)));
      }
      elseif (!isset($values[$token])) {
        $this->fail(t("Token value for [@type:@token] was not generated.", array('@type' => $type, '@token' => $token)));
      }
      elseif (!empty($options['regex'])) {
        $this->assertTrue(preg_match('/^' . $expected . '$/', $values[$token]), t("Token value for [@type:@token] was '@actual', matching regular expression pattern '@expected'.", array('@type' => $type, '@token' => $token, '@actual' => $values[$token], '@expected' => $expected)));
      }
      else {
        $this->assertIdentical($values[$token], $expected, t("Token value for [@type:@token] was '@actual', expected value '@expected'.", array('@type' => $type, '@token' => $token, '@actual' => $values[$token], '@expected' => $expected)));
      }
    }

    return $values;
  }
}
