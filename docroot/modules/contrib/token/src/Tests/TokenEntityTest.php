<?php

/**
 * @file
 * Contains \Drupal\token\Tests\TokenEntityTest.
 */

namespace Drupal\token\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Tests entity tokens.
 *
 * @group token
 */
class TokenEntityTest extends TokenKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('path', 'token', 'token_test', 'node', 'taxonomy', 'text');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the default tags vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();

    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->vocab = $vocabulary;
  }

  function testEntityMapping() {
    $this->assertIdentical(token_get_entity_mapping('token', 'node'), 'node');
    $this->assertIdentical(token_get_entity_mapping('token', 'term'), 'taxonomy_term');
    $this->assertIdentical(token_get_entity_mapping('token', 'vocabulary'), 'taxonomy_vocabulary');
    $this->assertIdentical(token_get_entity_mapping('token', 'invalid'), FALSE);
    $this->assertIdentical(token_get_entity_mapping('token', 'invalid', TRUE), 'invalid');
    $this->assertIdentical(token_get_entity_mapping('entity', 'node'), 'node');
    $this->assertIdentical(token_get_entity_mapping('entity', 'taxonomy_term'), 'term');
    $this->assertIdentical(token_get_entity_mapping('entity', 'taxonomy_vocabulary'), 'vocabulary');
    $this->assertIdentical(token_get_entity_mapping('entity', 'invalid'), FALSE);
    $this->assertIdentical(token_get_entity_mapping('entity', 'invalid', TRUE), 'invalid');

    // Test that when we send the mis-matched entity type into token_replace()
    // that we still get the tokens replaced.
    $vocabulary = entity_load('taxonomy_vocabulary', 'tags');
    $term = $this->addTerm($vocabulary);
    $this->assertIdentical(\Drupal::token()->replace('[vocabulary:name]', array('taxonomy_vocabulary' => $vocabulary)), $vocabulary->label());
    $this->assertIdentical(\Drupal::token()->replace('[term:name][term:vocabulary:name]', array('taxonomy_term' => $term)), $term->label() . $vocabulary->label());
  }

  function addTerm(VocabularyInterface $vocabulary, array $term = array()) {
    $term += array(
      'name' => Unicode::strtolower($this->randomMachineName(5)),
      'vid' => $vocabulary->id(),
    );
    $term = entity_create('taxonomy_term', $term);
    $term->save();
    return $term;
  }

  /**
   * Test the [entity:original:*] tokens.
   */
  function testEntityOriginal() {
    $node = Node::create(['type' => 'page', 'title' => 'Original title']);
    $node->save();

    $tokens = array(
      'nid' => $node->id(),
      'title' => 'Original title',
      'original' => NULL,
      'original:nid' => NULL,
    );
    $this->assertTokens('node', array('node' => $node), $tokens);

    // Emulate the original entity property that would be available from
    // node_save() and change the title for the node.
    $node->original = entity_load_unchanged('node', $node->id());
    $node->title = 'New title';

    $tokens = array(
      'nid' => $node->id(),
      'title' => 'New title',
      'original' => 'Original title',
      'original:nid' => $node->id(),
    );
    $this->assertTokens('node', array('node' => $node), $tokens);
  }
}
