<?php

/**
 * @file
 * Contains \Drupal\default_content\Tests\DefaultContentTest.
 */
namespace Drupal\default_content\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Test import of default content.
 *
 * @group default_content
 */
class DefaultContentTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rest', 'taxonomy', 'hal', 'default_content');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(array('type' => 'page'));
  }

  /**
   * Test importing default content.
   */
  public function testImport() {
    // Login as admin.
    $this->drupalLogin($this->drupalCreateUser(array_keys(\Drupal::moduleHandler()->invokeAll(('permission')))));
    // Enable the module and import the content.
    \Drupal::service('module_installer')->install(array('default_content_test'), TRUE);
    $this->rebuildContainer();
    $node = $this->drupalGetNodeByTitle('Imported node');
    $this->assertEqual($node->body->value, 'Crikey it works!');
    $this->assertEqual($node->getType(), 'page');
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadMultiple();
    $term = reset($terms);
    $this->assertTrue(!empty($term));
    $this->assertEqual($term->name->value, 'A tag');
    $term_id = $node->field_tags->target_id;
    $this->assertTrue(!empty($term_id), 'Term reference populated');
  }

}
