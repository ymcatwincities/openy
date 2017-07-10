<?php

namespace Drupal\sitemap\Tests;

/**
 * Test the display of menus based on sitemap settings.
 *
 * @group sitemap
 */
class SitemapMenuCustomTitleTest extends SitemapMenuTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('sitemap', 'node', 'menu_ui');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Configure module to show main menu.
    $edit = array(
      'show_menus[main]' => 'main',
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Create test node with enabled menu item.
    $edit = array(
      'title[0][value]' => $this->randomString(),
      'menu[enabled]' => TRUE,
      'menu[title]' => $this->randomString(),
      // In order to make main navigation menu displayed, there must be at least
      // one child menu item of that menu.
      'menu[menu_parent]' => 'main:',
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
  }

  /**
   * Tests a custom title setting for menus.
   */
  public function testMenusCustomTitle() {

    // Assert that main menu is included in the sitemap.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".sitemap-box h2:contains('Main navigation')");
    $this->assertEqual(count($elements), 1, 'Main menu with default title is included.');

    // Set a custom title for the main menu display.
    $custom_title = $this->randomString();
    $edit = array(
      'sitemap_display_name' => $custom_title,
    );
    $this->drupalPostForm('admin/structure/menu/manage/main', $edit, t('Save'));
    drupal_flush_all_caches();

    // Check that the custom title appears on the sitemap
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".sitemap-box h2:contains('" . $custom_title . "')");
    $this->assertEqual(count($elements), 1, 'Main menu with custom title is included.');

  }

}
