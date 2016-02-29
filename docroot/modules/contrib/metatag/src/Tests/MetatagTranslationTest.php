<?php

/**
 * @file
 * Contains \Drupal\metatag\Tests\MetatagTranslationTest.
 */

namespace Drupal\metatag\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures that metatag values are translated correctly.
 *
 * @group metatag
 */
class MetatagTranslationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'content_translation',
    'field_ui',
    'metatag',
    'node',
  );

  /**
   * The default language code to use in this test.
   *
   * @var array
   */
  protected $defaultLangcode = 'fr';

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $additionalLangcodes = ['es'];

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $admin_permissions = array(
      'administer content types',
      'administer content translation',
      'administer languages',
      'administer nodes',
      'administer node fields',
      'bypass node access',
      'create content translations',
      'delete content translations',
      'translate any entity',
      'update content translations',
    );

    // Create and login user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->additionalLangcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Tests the metatag value translations.
   */
  public function testMetatagValueTranslation() {
    // Set up a content type.
    $name = $this->randomString();
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(array('type' => 'metatag_node', 'name' => $name));

    // Add a metatag field to the content type.
    $this->drupalGet("admin/structure/types");
    $this->drupalGet("admin/structure/types/manage/metatag_node/fields/add-field");
    $this->assertResponse(200);
    $edit = [
      'label' => 'Metatag',
      'field_name' => 'metatag_field',
      'new_storage_type' => 'metatag',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
    $this->container->get('entity.manager')->clearCachedFieldDefinitions();

    // Enable translation for our test content type.
    $edit = array(
      'entity_types[node]' => 1,
      'settings[node][metatag_node][translatable]' => 1,
      'settings[node][metatag_node][translatable]' => 1,
      'settings[node][metatag_node][fields][field_metatag_field]' => 1,
    );
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));

    $this->drupalGet('admin/structure/types/manage/metatag_node');

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.

    // Create a node.
    $this->drupalGet("node/add/metatag_node");
    $edit = array(
      'title[0][value]' => 'Node Français',
      'body[0][value]' => 'French summary.',
    );
    $this->drupalPostForm("node/add/metatag_node", $edit, t('Save and publish'));
    $this->assertRaw('<meta name="description" content="French summary.');

    $edit = array(
      'title[0][value]' => 'Node Español',
      'body[0][value]' => 'Spanish summary.',
    );
    $this->drupalPostForm('node/1/translations/add/en/es', $edit, t('Save and keep published (this translation)'));

    $this->drupalGet('es/node/1');
    $this->assertNoRaw('<meta name="description" content="French summary.');
    $this->assertRaw('<meta name="description" content="Spanish summary.');

    // Set explicit values on the description metatag instead using the
    // defaults.
    $edit = array(
      'field_metatag_field[0][basic][description]' => 'Overridden French description',
    );
    $this->drupalPostForm("node/1/edit", $edit, t('Save and keep published (this translation)'));
    $this->assertRaw('<meta name="description" content="Overridden French description');
    $this->assertNoRaw('<meta name="description" content="French summary.');

    $edit = array(
      'field_metatag_field[0][basic][description]' => 'Overridden Spanish description',
    );
    $this->drupalPostForm("es/node/1/edit", $edit, t('Save and keep published (this translation)'));
    $this->assertRaw('<meta name="description" content="Overridden Spanish description');
    $this->assertNoRaw('<meta name="description" content="French summary.');
    $this->assertNoRaw('<meta name="description" content="Spanish summary.');
  }
}
