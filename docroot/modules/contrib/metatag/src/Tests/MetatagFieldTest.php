<?php
/**
 * @file
 * Contains \Drupal\metatag\Tests\MetatagFieldTest.
 */

namespace Drupal\metatag\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensures that the Metatag field works correctly.
 *
 * @group Metatag
 */
class MetatagFieldTest extends WebTestBase {

  /**
   * Profile to use.
   */
  protected $profile = 'testing';

  /**
   * Admin user
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_ui',
    'metatag',
    'entity_test',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'view test entity',
    'administer entity_test fields',
    'administer entity_test content',
    'administer meta tags',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);

    // Add a metatag field to the entity type test_entity.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $edit = [
      'label' => 'Metatag',
      'field_name' => 'metatag',
      'new_storage_type' => 'metatag',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
    $this->container->get('entity.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Tests adding and editing values using metatag.
   */
  public function testMetatag() {
    // Create a test entity.
    $edit = [
      'name[0][value]' => 'Barfoo',
      'user_id[0][target_id]' => 'foo (' . $this->adminUser->id() . ')',
    ];

    $this->drupalPostForm('entity_test/add', $edit, t('Save'));
    $entities = entity_load_multiple_by_properties('entity_test', [
      'name' => 'Barfoo',
    ]);
    $this->assertEqual(1, count($entities), 'Entity was saved');
    $entity = reset($entities);

    // Update the Global defaults and test them.
    $values = array(
      'keywords' => 'Purple monkey dishwasher',
    );
    $this->drupalPostForm('admin/structure/metatag_defaults/global', $values, 'Save');
    $this->assertText('Saved the Global Metatag defaults.');
    $this->drupalGet('entity_test/' . $entity->id());
    $elements = $this->cssSelect('meta[name=keywords]');
    $this->assertTrue(count($elements) === 1, 'Found keywords metatag from defaults');
    $this->assertEqual((string) $elements[0]['content'], $values['keywords'], 'Default keywords applied');

    // Tests metatags with urls work.
    $edit = [
      'name[0][value]' => 'UrlTags',
      'user_id[0][target_id]' => 'foo (' . $this->adminUser->id() . ')',
      'field_metatag[0][open_graph][og_url]' => 'http://example.com/foo.html',
    ];
    $this->drupalPostForm('entity_test/add', $edit, t('Save'));
    $entities = entity_load_multiple_by_properties('entity_test', [
      'name' => 'UrlTags',
    ]);
    $this->assertEqual(1, count($entities), 'Entity was saved');
    $entity = reset($entities);
    $this->drupalGet('entity_test/' . $entity->id());
    $elements = $this->cssSelect("meta[property='og:url']");
    $this->assertTrue(count($elements) === 1, 'Found keywords metatag from defaults');
    $this->assertEqual((string) $elements[0]['content'], $edit['field_metatag[0][open_graph][og_url]']);
  }

  /**
   * Tests inheritance in default metatags.
   *
   * When the bundle does not define a default value, global or entity defaults
   * are used instead.
   */
  public function testDefaultInheritance() {
    // First we set global defaults.
    $global_values = array(
      'description' => 'Global description',
    );
    $this->drupalPostForm('admin/structure/metatag_defaults/global', $global_values, 'Save');
    $this->assertText('Saved the Global Metatag defaults.');

    // Now when we create an entity, global defaults are used to fill the form fields.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName('field_metatag[0][basic][description]', $global_values['description'], t('Description field has the global default as the field default does not define it.'));
  }

}
