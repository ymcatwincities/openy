<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneCustomBlockTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\block_content\Tests\BlockContentTestBase;

/**
 * Creat ea block and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneCustomBlockTest extends BlockContentTestBase {

  /**
   * Modules to enable.
   *
   * Enable dummy module that implements hook_block_insert() for exceptions and
   * field_ui to edit display settings.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'block', 'block_content'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = ['administer blocks', 'clone block_content entity'];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  public function testCustomBlockEntityClone() {

    $edit = array();
    $edit['info[0][value]'] = 'Test block ready to clone';
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('block/add/basic', $edit, t('Save'));

    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties([
        'info' => $edit['info[0][value]'],
      ]);
    $block = reset($blocks);
    $this->assertTrue($block, 'Test Block for clone found in database.');

    $this->drupalPostForm('entity_clone/block_content/' . $block->id(), [], t('Clone'));

    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties([
        'info' => $edit['info[0][value]'] . ' - Cloned',
        'body' => $edit['body[0][value]'],
      ]);
    $block = reset($blocks);
    $this->assertTrue($block, 'Test Block cloned found in database.');
  }

}