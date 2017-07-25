<?php

namespace Drupal\sitemap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the display of menus based on sitemap settings.
 */
abstract class SitemapMenuTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('sitemap', 'node', 'menu_ui');

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Anonymous user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $anonUser;

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
    $this->adminUser = $this->drupalCreateUser(array(
      'administer sitemap',
      'access sitemap',
      'administer menu',
      'administer nodes',
      'create article content',
    ));
    $this->drupalLogin($this->adminUser);

    // Create anonymous user for use too.
    $this->anonUser = $this->drupalCreateUser(array(
      'access sitemap',
    ));
  }

}
