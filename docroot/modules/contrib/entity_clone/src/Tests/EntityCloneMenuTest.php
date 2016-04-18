<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneMenuTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create a menu and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneMenuTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'menu_ui'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone menu entity',
    'administer menu'
  ];

  /**
   * An administrative user with permission to configure menus settings.
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

  public function testMenuEntityClone() {

    $menus = \Drupal::entityTypeManager()
      ->getStorage('menu')
      ->loadByProperties([
        'id' => 'account',
      ]);
    $menu = reset($menus);

    $edit = [
      'label' => 'Test menu cloned',
      'id' => 'test_menu_cloned',
    ];
    $this->drupalPostForm('entity_clone/menu/' . $menu->id(), $edit, t('Clone'));

    $menus = \Drupal::entityTypeManager()
      ->getStorage('menu')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $menu = reset($menus);
    $this->assertTrue($menu, 'Test menu cloned found in database.');
  }

}

