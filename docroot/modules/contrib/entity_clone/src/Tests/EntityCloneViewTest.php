<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneViewTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create a view and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneViewTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'views'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone view entity'
  ];

  /**
   * An administrative user with permission to configure views settings.
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

  public function testViewEntityClone() {
    $edit = [
      'id' => 'test_view_cloned',
      'label' => 'Test view cloned',
    ];
    $this->drupalPostForm('entity_clone/view/who_s_new', $edit, t('Clone'));

    $views = \Drupal::entityTypeManager()
      ->getStorage('view')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $view = reset($views);
    $this->assertTrue($view, 'Test default view cloned found in database.');
  }

}

