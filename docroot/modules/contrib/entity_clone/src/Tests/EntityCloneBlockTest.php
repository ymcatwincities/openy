<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneBlockTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\block\Entity\Block;
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
    $config = \Drupal::configFactory();
    $block = Block::create([
      'plugin' => 'test_block',
      'region' => 'sidebar_first',
      'id' => 'test_block',
      'theme' => $config->get('system.theme')->get('default'),
      'label' => $this->randomMachineName(8),
      'visibility' => [],
      'weight' => 0,
    ]);
    $block->save();

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

