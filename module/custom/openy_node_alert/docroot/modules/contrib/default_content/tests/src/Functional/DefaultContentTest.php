<?php

namespace Drupal\Tests\default_content\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\user\Entity\User;
use Drupal\Tests\BrowserTestBase;

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
  public static $modules = ['taxonomy', 'hal', 'default_content'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create user 2 with the correct UUID.
    User::create([
      'uid' => 2,
      'uuid' => 'ab301be5-7017-4ff8-b2d3-09dc0a30bd43',
      'name' => 'User 2',
      'mail' => 'user2@example.com',
      'status' => TRUE,
    ])->save();
    $this->createContentType(['type' => 'page']);
  }

  /**
   * Test importing default content.
   */
  public function testImport() {
    // Enable the module and import the content.
    \Drupal::service('module_installer')->install(['default_content_test'], TRUE);
    $this->rebuildContainer();

    $this->doPostInstallTests();
  }

  /**
   * Test importing default content via ConfigImporter.
   */
  public function testImportViaConfigImporter() {
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);

    // Enable the module using the ConfigImporter.
    $extensions = $sync->read('core.extension');
    $extensions['module']['default_content_test'] = 0;
    $extensions['module'] = module_config_sort($extensions['module']);
    $sync->write('core.extension', $extensions);
    // Slightly hacky but we need the config from the test module too.
    $module_storage = new FileStorage(drupal_get_path('module', 'default_content_test') . '/config/install');
    foreach ($module_storage->listAll() as $name) {
      $sync->write($name, $module_storage->read($name));
    }
    $this->configImporter()->import();

    $this->doPostInstallTests();
  }

  /**
   * Makes assertions post the install of the default_content_test module.
   */
  protected function doPostInstallTests() {
    // Login as admin.
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));

    // Ensure the content contained in the default_content_test module has been
    // created correctly.
    $node = $this->getNodeByTitle('Imported node');
    $this->assertEquals($node->body->value, 'Crikey it works!');
    $this->assertEquals($node->getType(), 'page');
    $this->assertSame('2', $node->getOwnerId(), 'The node created is owned by user 2');

    $node = $this->getNodeByTitle('Imported node with owned by user 1');
    $this->assertSame('1', $node->getOwnerId(), 'The node created is owned by user 1');

    $node = $this->getNodeByTitle('Imported node with owned by user that does not exist');
    $this->assertSame('1', $node->getOwnerId(), 'The node created is owned by user 1');

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple();
    $term = reset($terms);
    $this->assertTrue(!empty($term));
    $this->assertEquals($term->name->value, 'A tag');
    $term_id = $node->field_tags->target_id;
    $this->assertTrue(!empty($term_id), 'Term reference populated');
  }

}
