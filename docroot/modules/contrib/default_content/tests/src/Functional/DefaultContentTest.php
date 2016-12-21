<?php

namespace Drupal\Tests\default_content\Functional;

use Drupal\simpletest\BrowserTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Test import of default content.
 *
 * @group default_content
 */
class DefaultContentTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

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
    $this->createContentType(array('type' => 'page'));
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
    $node = $this->getNodeByTitle('Imported node');
    $this->assertEquals($node->body->value, 'Crikey it works!');
    $this->assertEquals($node->getType(), 'page');
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple();
    $term = reset($terms);
    $this->assertTrue(!empty($term));
    $this->assertEquals($term->name->value, 'A tag');
    $term_id = $node->field_tags->target_id;
    $this->assertTrue(!empty($term_id), 'Term reference populated');
  }

}
