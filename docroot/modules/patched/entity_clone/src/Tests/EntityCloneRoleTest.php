<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneRoleTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create a role and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneRoleTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'user'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer permissions',
    'clone user_role entity'
  ];

  /**
   * An administrative user with permission to configure roles settings.
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

  public function testRoleEntityClone() {
    $edit = [
      'label' => 'Test role for clone',
      'id' => 'test_role_for_clone',
    ];
    $this->drupalPostForm("/admin/people/roles/add", $edit, t('Save'));

    $roles = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $role = reset($roles);

    $edit = [
      'id' => 'test_role_cloned',
      'label' => 'Test role cloned',
    ];
    $this->drupalPostForm('entity_clone/user_role/' . $role->id(), $edit, t('Clone'));

    $roles = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $role = reset($roles);
    $this->assertTrue($role, 'Test role cloned found in database.');
  }

}

