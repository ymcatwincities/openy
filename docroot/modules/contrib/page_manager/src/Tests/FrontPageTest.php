<?php

namespace Drupal\page_manager\Tests;

use Drupal\block\Entity\Block;
use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\simpletest\WebTestBase;

/**
 * Tests a page manager page as front page.
 *
 * @group page_manager
 */
class FrontPageTest extends WebTestBase {

  use PageTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['page_manager', 'block'];

  /**
   * Tests the front page title.
   */
  public function testFrontPageTitle() {
    // Use a block page for frontpage.
    $page = Page::create([
      'label' => 'My frontpage',
      'id' => 'myfront',
      'path' => '/myfront',
    ]);
    $page->save();
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = PageVariant::create([
      'variant' => 'block_display',
      'id' => 'block_page',
      'label' => 'Block page',
      'page' => 'myfront',
    ]);
    $page_variant->save();

    $this->config('system.site')->set('page.front', '/myfront')->save();

    $block = Block::create([
      'id' => $this->randomMachineName(),
      'plugin' => 'system_powered_by_block',
    ]);
    $block->save();
    $page_variant->getVariantPlugin()->setConfiguration([
      'page_title' => '',
      'blocks' => [
        $block->uuid() => [
          'region' => 'top',
          'weight' => 0,
          'id' => $block->id(),
          'uuid' => $block->uuid(),
          'context_mapping' => [],
        ],
      ],
    ]);

    $this->verbose(var_export($page_variant->toArray(), TRUE));

    $this->triggerRouterRebuild();

    // The title should default to "Home" on the front page.
    // @todo This gives 404 :(
    $this->drupalGet('');
    $this->assertTitle('Home | Drupal');
  }

}
