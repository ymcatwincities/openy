<?php

/**
 * @file
 * Contains \Drupal\page_manager\Tests\PageConfigSchemaTest.
 */

namespace Drupal\page_manager\Tests;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;

/**
 * Ensures that page entities have valid config schema.
 *
 * @group page_manager
 */
class PageConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['page_manager', 'block', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['page_manager']);
  }

  /**
   * Tests whether the page entity config schema is valid.
   */
  public function testValidPageConfigSchema() {
    $id = 'node_view';
    $label = 'Node view';
    $description = 'When enabled, this overrides the default Drupal behavior for displaying nodes at <em>/node/{node}</em>. If you add variants, you may use selection criteria such as node type or language or user access to provide different views of nodes. If no variant is selected, the default Drupal node view will be used. This page only affects nodes viewed as pages, it will not affect nodes viewed in lists or at other locations.';

    /** @var \Drupal\page_manager\PageInterface $page */
    $page = Page::load($id);

    // Add an access condition.
    $page->addAccessCondition([
      'id' => 'node_type',
      'bundles' => [
        'article' => 'article',
      ],
      'negate' => TRUE,
      'context_mapping' => [
        'node' => 'node',
      ],
    ]);
    $page->save();

    $page_variant_id = 'block_page';
    // Add a block variant.
    $page_variant = PageVariant::create([
      'variant' => 'block_display',
      'id' => $page_variant_id,
      'label' => 'Block page',
      'page' => $page->id(),
    ]);
    $page_variant->save();
    $page->addVariant($page_variant);
    /** @var \Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant $variant_plugin */
    $variant_plugin = $page_variant->getVariantPlugin();

    // Add a selection condition.
    $page_variant->addSelectionCondition([
      'id' => 'node_type',
      'bundles' => [
        'page' => 'page',
      ],
      'context_mapping' => [
        'node' => 'node',
      ],
    ]);

    // Add a block.
    $variant_plugin->addBlock([
      'id' => 'entity_view:node',
      'label' => 'View the node',
      'provider' => 'page_manager',
      'label_display' => 'visible',
      'view_mode' => 'default',
    ]);
    $page_variant->save();

    $page_config = \Drupal::config("page_manager.page.$id");
    $this->assertSame($page_config->get('id'), $id);
    $this->assertSame($page_config->get('label'), $label);
    $this->assertSame($page_config->get('description'), $description);

    $variant_config = \Drupal::config("page_manager.page_variant.$page_variant_id");
    $this->assertSame($variant_config->get('id'), $page_variant_id);

    $this->assertConfigSchema(\Drupal::service('config.typed'), $page_config->getName(), $page_config->get());
    $this->assertConfigSchema(\Drupal::service('config.typed'), $variant_config->getName(), $variant_config->get());
  }

}
