<?php

namespace Drupal\Tests\views_infinite_scroll\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\views\Entity\View;

/**
 * Test views infinite scroll.
 *
 * @group views_infinite_scroll
 */
class InfiniteScrollTest extends JavascriptTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'views_ui',
    'views_infinite_scroll',
    'node',
  ];

  /**
   * How long to wait for AJAX requests to complete.
   */
  const ajaxWaitDelay = 500;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType([
      'type' => 'page',
    ]);
    foreach (range(0, 10) as $i) {
      $this->createNode([
        'status' => TRUE,
        'type' => 'page',
      ]);
    }
  }

  /**
   * Test infinite scrolling under different conditions.
   */
  public function testInfiniteScroll() {
    // Test manually clicking a view.
    $this->createView('click-to-load', [
      'button_text' => 'Load More',
      'automatically_load_content' => FALSE,
    ]);
    $this->drupalGet('click-to-load');
    $this->assertTotalNodes(3);
    $this->getSession()->getPage()->clickLink('Load More');
    $this->getSession()->wait(static::ajaxWaitDelay);
    $this->assertTotalNodes(6);

    // Test the view automatically loading.
    $this->createView('automatic-load', [
      'button_text' => 'Load More',
      'automatically_load_content' => TRUE,
    ]);
    $this->getSession()->resizeWindow(1200, 200);
    $this->drupalGet('automatic-load');
    $this->assertTotalNodes(3);
    $this->scrollTo(500);
    $this->getSession()->wait(static::ajaxWaitDelay);
    $this->assertTotalNodes(6);
  }

  /**
   * Assert how many nodes appear on the page.
   *
   * @param int $total
   *   The total nodes on the page.
   */
  protected function assertTotalNodes($total) {
    $this->assertEquals($total, count($this->getSession()->getPage()->findAll('css', '.node--type-page')));
  }

  /**
   * Scroll to a pixel offset.
   *
   * @param int $pixels
   *   The pixel offset to scroll to.
   */
  protected function scrollTo($pixels) {
    $this->getSession()->getDriver()->executeScript("window.scrollTo(null, $pixels);");
  }

  /**
   * Create a view setup for testing views_infinite_scroll.
   *
   * @param string $path
   *   The path for the view.
   * @param array $settings
   *   The VIS settings.
   */
  protected function createView($path, $settings) {
    View::create([
      'label' => 'VIS Test',
      'id' => $this->randomMachineName(),
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'row' => [
              'type' => 'entity:node',
              'options' => [
                'view_mode' => 'teaser',
              ],
            ],
            'pager' => [
              'type' => 'infinite_scroll',
              'options' => [
                'items_per_page' => 3,
                'offset' => 0,
                'views_infinite_scroll' => $settings,
              ],
            ],
          ],
        ],
        'page_1' => [
          'display_plugin' => 'page',
          'id' => 'page_1',
          'display_options' => [
            'path' => $path,
          ],
        ],
      ],
    ])->save();
    \Drupal::service('router.builder')->rebuild();
  }

}
