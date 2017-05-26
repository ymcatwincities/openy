<?php

/**
 * @file
 */

namespace Drupal\Tests\panels_ipe\FunctionalJavascript;

/**
 * Tests the JavaScript functionality of Panels IPE with PageManager.
 *
 * @group panels
 */
class PageManagerIntegrationTest extends PanelsIPETestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'panels',
    'panels_ipe',
    'page_manager',
    'panels_ipe_page_manager_test_config',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a user with appropriate permissions to use Panels IPE.
    $admin_user = $this->drupalCreateUser([
      'access panels in-place editing',
      'administer blocks',
      'administer pages',
    ]);
    $this->drupalLogin($admin_user);

    $this->test_route = 'test-page';
  }

}
