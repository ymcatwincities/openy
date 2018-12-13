<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneShortcutSetTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create a shortcut set and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneShortcutSetTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'shortcut'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone shortcut_set entity'
  ];

  /**
   * An administrative user with permission to configure shortcuts settings.
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

  public function testShortcutSetEntityClone() {
    $edit = [
      'id' => 'test_shortcut_set_cloned',
      'label' => 'Test shortcut set cloned',
    ];
    $this->drupalPostForm('entity_clone/shortcut_set/default', $edit, t('Clone'));

    $shortcut_sets = \Drupal::entityTypeManager()
      ->getStorage('shortcut_set')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $shortcut_set = reset($shortcut_sets);
    $this->assertTrue($shortcut_set, 'Test default shortcut set cloned found in database.');
  }

}

