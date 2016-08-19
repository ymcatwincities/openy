<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneContentTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\node\Tests\NodeTestBase;

/**
 * Create a content and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneContentTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'block', 'node', 'datetime'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'bypass node access',
    'administer nodes',
    'clone node entity'
  ];

  /**
   * A user with permission to bypass content access checks.
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

  public function testContentEntityClone() {
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    // Create node to edit.

    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => $edit[$title_key],
        'body' => $edit[$body_key],
      ]);
    $node = reset($nodes);
    $this->assertTrue($node, 'Test node for clone found in database.');

    $this->drupalPostForm('entity_clone/node/' . $node->id(), [], t('Clone'));

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => $edit[$title_key] . ' - Cloned',
        'body' => $edit[$body_key],
      ]);
    $node = reset($nodes);
    $this->assertTrue($node, 'Test node cloned found in database.');
  }

}