<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneUserTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create a user and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneUserTest extends WebTestBase {

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
    'clone user entity'
  ];

  /**
   * An administrative user with permission to configure users settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->permissions, 'test_user');
    $this->drupalLogin($this->adminUser);
  }

  public function testUserEntityClone() {
    $this->drupalPostForm('entity_clone/user/' . $this->adminUser->id(), [], t('Clone'));

    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
        'name' => 'test_user_cloned',
      ]);
    $user = reset($users);
    $this->assertTrue($user, 'Test user cloned found in database.');
  }

}

