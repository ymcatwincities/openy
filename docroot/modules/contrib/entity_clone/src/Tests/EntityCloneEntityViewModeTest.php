<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneEntityViewModeTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test an entity view mode clone.
 *
 * @group entity_clone
 */
class EntityCloneEntityViewModeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone entity_view_mode entity'
  ];

  /**
   * An administrative user with permission to configure entity view modes settings.
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

  public function testEntityViewModeEntityClone() {
    $entity_view_modes = \Drupal::entityTypeManager()
      ->getStorage('entity_view_mode')
      ->loadByProperties([
        'id' => 'user.full',
      ]);
    $entity_view_mode = reset($entity_view_modes);

    $edit = [
      'label' => 'User full cloned view mode',
      'id' => 'register_clone',
    ];
    $this->drupalPostForm('entity_clone/entity_view_mode/' . $entity_view_mode->id(), $edit, t('Clone'));

    $entity_view_modes = \Drupal::entityTypeManager()
      ->getStorage('entity_view_mode')
      ->loadByProperties([
        'id' => 'user.' . $edit['id'],
      ]);
    $entity_view_mode = reset($entity_view_modes);
    $this->assertTrue($entity_view_mode, 'Test entity view mode cloned found in database.');
  }

}

