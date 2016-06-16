<?php

/**
 * @file
 * Contains \Drupal\ctools_block\Tests\EntityFieldBlockTest.
 */

namespace Drupal\ctools_block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity field block.
 *
 * @group ctools_block
 */
class EntityFieldBlockTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'ctools_block', 'ctools_block_field_test'];

  /**
   * Tests using the node body field in a block.
   */
  public function testBodyField() {
    $block = $this->drupalPlaceBlock('entity_field:node:body', [
      'formatter' => [
        'type' => 'text_default',
      ],
      'context_mapping' => [
        'entity' => '@node.node_route_context:node',
      ],
    ]);
    $node = $this->drupalCreateNode(['type' => 'ctools_block_field_test']);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($block->label());
    $this->assertText($node->body->value);
  }

  /**
   * Tests using the node uid base field in a block.
   */
  public function testNodeBaseFields() {
    $block = $this->drupalPlaceBlock('entity_field:node:title', [
      'formatter' => [
        'type' => 'string',
      ],
      'context_mapping' => [
        'entity' => '@node.node_route_context:node',
      ],
    ]);
    $node = $this->drupalCreateNode(['type' => 'ctools_block_field_test', 'uid' => 1]);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($block->label());
    $this->assertText($node->getTitle());
  }

  /**
   * Tests that we are setting the render cache metadata correctly.
   */
  public function testRenderCache() {
    $this->drupalPlaceBlock('entity_field:node:body', [
      'formatter' => [
        'type' => 'text_default',
      ],
      'context_mapping' => [
        'entity' => '@node.node_route_context:node',
      ],
    ]);
    $a = $this->drupalCreateNode(['type' => 'ctools_block_field_test']);
    $b = $this->drupalCreateNode(['type' => 'ctools_block_field_test']);

    $this->drupalGet('node/' . $a->id());
    $this->assertText($a->body->value);
    $this->drupalGet('node/' . $b->id());
    $this->assertNoText($a->body->value);
    $this->assertText($b->body->value);

    $text = 'This is my text. Are you not entertained?';
    $a->body->value = $text;
    $a->save();
    $this->drupalGet('node/' . $a->id());
    $this->assertText($text);
  }

}
