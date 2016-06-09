<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneLanguageTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create an language and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneLanguageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'language'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer languages',
    'clone configurable_language entity'
  ];

  /**
   * An administrative user with permission to configure languages settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  public function testLanguageEntityClone() {
    $edit = [
      'predefined_langcode' => 'fr'
    ];
    $this->drupalPostForm("/admin/config/regional/language/add", $edit, t('Add language'));

    $languages = \Drupal::entityTypeManager()
      ->getStorage('configurable_language')
      ->loadByProperties([
        'id' => 'fr',
      ]);
    $language = reset($languages);

    $edit = [
      'id' => 'test_language_cloned',
      'label' => 'French language cloned',
    ];
    $this->drupalPostForm('entity_clone/configurable_language/' . $language->id(), $edit, t('Clone'));

    $languages = \Drupal::entityTypeManager()
      ->getStorage('configurable_language')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $language = reset($languages);
    $this->assertTrue($language, 'Test language cloned found in database.');
  }

}

