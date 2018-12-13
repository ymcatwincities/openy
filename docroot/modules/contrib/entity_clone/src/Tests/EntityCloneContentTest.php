<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneContentTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\node\Entity\Node;
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
    $node_title = $this->randomMachineName(8);
    $node = Node::create([
      'type' => 'page',
      'title' => $node_title,
    ]);
    $node->save();

    $this->drupalPostForm('entity_clone/node/' . $node->id(), [], t('Clone'));

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => $node_title . ' - Cloned',
      ]);
    $node = reset($nodes);
    $this->assertTrue($node, 'Test node cloned found in database.');
  }

}