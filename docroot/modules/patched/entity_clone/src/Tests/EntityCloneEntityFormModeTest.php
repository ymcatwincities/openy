<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneEntityFormModeTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test an entity form mode clone.
 *
 * @group entity_clone
 */
class EntityCloneEntityFormModeTest extends WebTestBase {

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
    'clone entity_form_mode entity'
  ];

  /**
   * An administrative user with permission to configure entity form modes settings.
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

  public function testEntityFormModeEntityClone() {
    $entity_form_modes = \Drupal::entityTypeManager()
      ->getStorage('entity_form_mode')
      ->loadByProperties([
        'id' => 'user.register',
      ]);
    $entity_form_mode = reset($entity_form_modes);

    $edit = [
      'label' => 'User register cloned form mode',
      'id' => 'register_clone',
    ];
    $this->drupalPostForm('entity_clone/entity_form_mode/' . $entity_form_mode->id(), $edit, t('Clone'));

    $entity_form_modes = \Drupal::entityTypeManager()
      ->getStorage('entity_form_mode')
      ->loadByProperties([
        'id' => 'user.' . $edit['id'],
      ]);
    $entity_form_mode = reset($entity_form_modes);
    $this->assertTrue($entity_form_mode, 'Test entity form mode cloned found in database.');
  }

}

