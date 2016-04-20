<?php

/**
 * @file
 * Contains \Drupal\page_manager\Tests\PageNodeAccessTest.
 */

namespace Drupal\page_manager\Tests;

use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the access for an overridden route, specifically /node/{node}.
 *
 * @group page_manager
 */
class PageNodeAccessTest extends WebTestBase {

  use PageTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['page_manager', 'node', 'user'];

  /**
   * @var \Drupal\page_manager\PageInterface
   */
  protected $page;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Remove the 'access content' permission from anonymous and auth users.
    Role::load(RoleInterface::ANONYMOUS_ID)->revokePermission('access content')->save();
    Role::load(RoleInterface::AUTHENTICATED_ID)->revokePermission('access content')->save();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->drupalPlaceBlock('page_title_block');
    $this->page = Page::load('node_view');
  }

  /**
   * Tests that a user role condition controls the node view page.
   */
  public function testUserRoleAccessCondition() {
    $node1 = $this->drupalCreateNode(['type' => 'page']);
    $node2 = $this->drupalCreateNode(['type' => 'article']);

    $this->drupalLogin($this->drupalCreateUser(['access content']));
    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(200);
    $this->assertText($node1->label());
    $this->assertTitle($node1->label() . ' | Drupal');

    // Add a variant and an access condition.
    /** @var \Drupal\page_manager\Entity\PageVariant $page_variant */
    $page_variant = PageVariant::create([
      'variant' => 'block_display',
      'id' => 'block_page',
      'label' => 'Block page',
      'page' => $this->page->id(),
    ]);
    $page_variant->getVariantPlugin()->setConfiguration(['page_title' => 'The overridden page']);
    $page_variant->save();

    $this->page->addAccessCondition([
      'id' => 'user_role',
      'roles' => [
        RoleInterface::AUTHENTICATED_ID => RoleInterface::AUTHENTICATED_ID,
      ],
      'context_mapping' => [
        'user' => 'current_user',
      ],
    ]);
    $this->page->addAccessCondition([
      'id' => 'node_type',
      'bundles' => [
        'page' => 'page',
      ],
      'context_mapping' => [
        'node' => 'node',
      ],
    ]);
    $this->page->save();
    $this->triggerRouterRebuild();

    $this->drupalLogout();
    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(403);
    $this->assertNoText($node1->label());
    $this->assertTitle('Access denied | Drupal');

    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(403);
    $this->assertNoText($node1->label());
    $this->assertTitle('Access denied | Drupal');

    $this->drupalLogin($this->drupalCreateUser(['access content']));
    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(200);
    $this->assertNoText($node1->label());
    $this->assertTitle('The overridden page | Drupal');

    $this->drupalGet('node/' . $node2->id());
    $this->assertResponse(403);
    $this->assertNoText($node2->label());
    $this->assertTitle('Access denied | Drupal');
  }

}
