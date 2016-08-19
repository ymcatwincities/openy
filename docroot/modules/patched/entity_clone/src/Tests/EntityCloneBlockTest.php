<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneBlockTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create an block and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneBlockTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'block'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer blocks',
    'clone block entity'
  ];

  /**
   * An administrative user with permission to configure blocks settings.
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

  public function testBlockEntityClone() {
    $edit = [
      'settings[label]' => 'Test block for clone',
      'id' => 'test_block_for_clone',
    ];
    $this->drupalPostForm("admin/structure/block/add/local_actions_block/classy", $edit, t('Save block'));

    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $block = reset($blocks);

    $edit = [
      'id' => 'test_block_cloned',
    ];
    $this->drupalPostForm('entity_clone/block/' . $block->id(), $edit, t('Clone'));

    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $block = reset($blocks);
    $this->assertTrue($block, 'Test block cloned found in database.');
  }

}

