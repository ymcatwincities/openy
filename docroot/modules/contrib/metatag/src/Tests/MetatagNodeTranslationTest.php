<?php

namespace Drupal\metatag\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures that meta tag values are translated correctly on nodes.
 *
 * @group metatag
 */
class MetatagNodeTranslationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'content_translation',
    'field_ui',
    'metatag',
    'node',
  ];

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

    $admin_permissions = [
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
    ];

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
    $this->drupalCreateContentType(['type' => 'metatag_node', 'name' => $name]);

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
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertResponse(200);
    $edit = [
      'entity_types[node]' => 1,
      'settings[node][metatag_node][translatable]' => 1,
      'settings[node][metatag_node][translatable]' => 1,
      'settings[node][metatag_node][fields][field_metatag_field]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $this->drupalGet('admin/structure/types/manage/metatag_node');
    $this->assertResponse(200);

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.

    // Create a node.
    $this->drupalGet("node/add/metatag_node");
    $edit = [
      'title[0][value]' => 'Node Français',
      'body[0][value]' => 'French summary.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertEqual(count($xpath), 1, 'Exactly one description meta tag found.');
    $value = (string) $xpath[0]['content'];
    $this->assertEqual($value, 'French summary.');

    $this->drupalGet('node/1/translations/add/en/es');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => 'Node Español',
      'body[0][value]' => 'Spanish summary.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and keep published (this translation)'));
    $this->drupalGet('es/node/1');
    $this->assertResponse(200);
    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertEqual(count($xpath), 1, 'Exactly one description meta tag found.');
    $value = (string) $xpath[0]['content'];
    $this->assertEqual($value, 'Spanish summary.');
    $this->assertNotEqual($value, 'French summary.');

    // Set explicit values on the description metatag instead using the
    // defaults.
    $this->drupalGet('node/1/edit');
    $this->assertResponse(200);
    $edit = [
      'field_metatag_field[0][basic][description]' => 'Overridden French description.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and keep published (this translation)'));
    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertEqual(count($xpath), 1, 'Exactly one description meta tag found.');
    $value = (string) $xpath[0]['content'];
    $this->assertEqual($value, 'Overridden French description.');
    $this->assertNotEqual($value, 'Spanish summary.');
    $this->assertNotEqual($value, 'French summary.');

    $this->drupalGet('es/node/1/edit');
    $this->assertResponse(200);
    $edit = [
      'field_metatag_field[0][basic][description]' => 'Overridden Spanish description.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and keep published (this translation)'));
    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertEqual(count($xpath), 1, 'Exactly one description meta tag found.');
    $value = (string) $xpath[0]['content'];
    $this->assertEqual($value, 'Overridden Spanish description.');
    $this->assertNotEqual($value, 'Spanish summary.');
    $this->assertNotEqual($value, 'French summary.');
  }
}
