<?php

/**
 * @file
 * Contains \Drupal\page_manager\Tests\PageNodeSelectionTest.
 */

namespace Drupal\page_manager\Tests;

use Drupal\page_manager\Entity\PageVariant;
use Drupal\simpletest\WebTestBase;

/**
 * Tests selecting variants based on nodes.
 *
 * @group page_manager
 */
class PageNodeSelectionTest extends WebTestBase {

  use PageTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['page_manager', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->drupalLogin($this->drupalCreateUser(['administer pages', 'create article content', 'create page content']));

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests that a node bundle condition controls the node view page.
   */
  public function testAdmin() {
    // Create two nodes, and view their pages.
    $node1 = $this->drupalCreateNode(['type' => 'page']);
    $node2 = $this->drupalCreateNode(['type' => 'article']);
    $node3 = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(200);
    $this->assertText($node1->label());
    $this->assertTitle($node1->label() . ' | Drupal');
    $this->drupalGet('node/' . $node2->id());
    $this->assertResponse(200);
    $this->assertCacheTag('page_manager_route_name:entity.node.canonical');
    $this->assertText($node2->label());
    $this->assertTitle($node2->label() . ' | Drupal');

    // Create a new variant to always return 404, the node_view page exists by
    // default.
    $http_status_variant = PageVariant::create([
      'variant' => 'http_status_code',
      'label' => 'HTTP status code',
      'id' => 'http_status_code',
      'page' => 'node_view',
    ]);
    $http_status_variant->getVariantPlugin()->setConfiguration(['status_code' => 404]);
    $http_status_variant->save();
    $this->triggerRouterRebuild();

    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(404);
    $this->assertCacheTag('page_manager_route_name:entity.node.canonical');
    $this->assertNoText($node1->label());
    $this->drupalGet('node/' . $node2->id());
    $this->assertResponse(404);
    $this->assertNoText($node2->label());

    // Add a new variant.
    /** @var \Drupal\page_manager\PageVariantInterface $block_page_variant */
    $block_page_variant = PageVariant::create([
      'variant' => 'block_display',
      'id' => 'block_page_first',
      'label' => 'First',
      'page' => 'node_view',
    ]);
    $block_page_plugin = $block_page_variant->getVariantPlugin();
    $block_page_plugin->setConfiguration(['page_title' => '[node:title]']);
    /** @var \Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant $block_page_plugin */
    $block_page_plugin->addBlock([
      'id' => 'entity_view:node',
      'label' => 'Entity view (Content)',
      'label_display' => FALSE,
      'view_mode' => 'default',
      'region' => 'top',
      'context_mapping' => [
        'entity' => 'node',
      ],
    ]);
    $block_page_variant->addSelectionCondition([
      'id' => 'node_type',
      'bundles' => [
        'article' => 'article',
      ],
      'context_mapping' => [
        'node' => 'node',
      ],
    ]);
    $block_page_variant->setWeight(-10);
    $block_page_variant->save();
    $this->triggerRouterRebuild();

    // The page node will 404, but the article node will display the variant.
    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(404);
    $this->assertNoText($node1->label());

    $this->drupalGet('node/' . $node2->id());
    $this->assertResponse(200);
    $this->assertTitle($node2->label() . ' | Drupal');
    $this->assertText($node2->body->value);

    // Test cacheability metadata.
    $this->drupalGet('node/' . $node3->id());
    $this->assertNoText($node2->label());

    // Ensure this doesn't affect the /node/add page.
    $this->drupalGet('node/add');
    $this->assertResponse(200);
  }

}
