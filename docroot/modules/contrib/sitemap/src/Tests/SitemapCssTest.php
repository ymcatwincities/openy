<?php

/**
 * @file
 * Contains \Drupal\sitemap\Tests\SitemapCssTest.
 */

namespace Drupal\sitemap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the inclusion of the sitemap css file based on sitemap settings.
 *
 * @group sitemap
 */
class SitemapCssTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('sitemap');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user then login.
    $this->user = $this->drupalCreateUser(array(
      'administer sitemap',
      'access sitemap',
    ));
    $this->drupalLogin($this->user);
  }

  /**
   * Tests include css file.
   */
  public function testIncludeCssFile() {
    // Assert that css file is included by default.
    $this->drupalGet('/sitemap');
    $this->assertRaw('sitemap.theme.css');

    // Change module not to include css file.
    $edit = array(
      'css' => TRUE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that css file is not included.
    $this->drupalGet('/sitemap');
    $this->assertNoRaw('sitemap.theme.css');
  }

}
