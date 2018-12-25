<?php

namespace Drupal\views_infinite_scroll\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Basic integration smoke test for the pager plugin.
 *
 * @group views_infinite_scroll
 */
class IntegrationSmokeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views', 'views_ui', 'views_infinite_scroll'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->createUser(['administer views']));
  }

  /**
   * Test the views plugin.
   */
  public function testPlugin() {
    // Create a view with the pager plugin enabled.
    $this->drupalPostForm('admin/structure/views/add', [
      'label' => 'Test Plugin',
      'id' => 'test_plugin',
      'page[create]' => '1',
      'page[title]' => 'Test Plugin',
      'page[path]' => 'test-plugin',
    ], 'Save and edit');
    $this->clickLink('Mini');
    $this->drupalPostForm(NULL, [
      'pager[type]' => 'infinite_scroll',
    ], 'Apply');
    $this->drupalPostForm(NULL, [
      'pager_options[views_infinite_scroll][button_text]' => 'More Please',
      'pager_options[views_infinite_scroll][automatically_load_content]' => '',
    ], 'Apply');
    $this->assertLink('Infinite Scroll');
    $this->assertText('Automatic infinite scroll, 10 items');
    $this->drupalPostForm(NULL, [], 'Save');

    // Open the permissions to view the page.
    $this->clickLink('Permission');
    $this->drupalPostForm(NULL, [
      'access[type]' => 'none',
    ], 'Apply');
    $this->drupalPostForm(NULL, [], 'Save');

    // Ensure the wrapper div appears on the page.
    $this->drupalGet('test-plugin');
    $this->assertRaw('data-drupal-views-infinite-scroll-content-wrapper');
  }

}
