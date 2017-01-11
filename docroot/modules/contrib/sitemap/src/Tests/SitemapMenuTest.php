<?php

/**
 * @file
 * Contains \Drupal\sitemap\Tests\SitemapMenuTest.
 */

namespace Drupal\sitemap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the display of menus based on sitemap settings.
 *
 * @group sitemap
 */
class SitemapMenuTest extends WebTestBase {

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

    // Create an Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    // Create user then login.
    $this->user = $this->drupalCreateUser(array(
      'administer sitemap',
      'access sitemap',
      'administer menu',
      'administer nodes',
      'create article content',
    ));
    $this->drupalLogin($this->user);
  }

  /**
   * Tests menus.
   */
  public function testMenus() {
    // Assert that main menu is not included in the sitemap by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".sitemap-box h2:contains('Main navigation')");
    $this->assertEqual(count($elements), 0, 'Main menu is not included.');

    // Configure module to show main menu, with enabled menu items only.
    $edit = array(
      'show_menus[main]' => 'main',
      'show_menus_hidden' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Create dummy node with enabled menu item.
    $node_1_title = $this->randomString();
    $edit = array(
      'title[0][value]' => $node_1_title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $node_1_title,
      // In order to make main navigation menu displayed, there must be at least
      // one child menu item of that menu.
      'menu[menu_parent]' => 'main:',
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Create dummy node with disabled menu item.
    $node_2_title = $this->randomString();
    $edit = array(
      'title[0][value]' => $node_2_title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $node_2_title,
      'menu[menu_parent]' => 'main:',
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Disable menu item.
    $menu_links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(array('title' => $node_2_title));
    $menu_link = reset($menu_links);
    $mlid = $menu_link->id();
    $edit = array(
      'enabled[value]' => FALSE,
    );
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));

    // Assert that main menu is included in the sitemap.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".sitemap-box h2:contains('Main navigation')");
    $this->assertEqual(count($elements), 1, 'Main menu is included.');
    // Assert that node 1 is listed in the sitemap, but not node 2.
    $this->assertLink($node_1_title);
    $this->assertNoLink($node_2_title);

    // Configure module to show all menu items.
    $edit = array(
      'show_menus_hidden' => TRUE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that both node 1 and node 2 are listed in the sitemap.
    $this->drupalGet('/sitemap');
    $this->assertLink($node_1_title);
    $this->assertLink($node_2_title);
  }

}
