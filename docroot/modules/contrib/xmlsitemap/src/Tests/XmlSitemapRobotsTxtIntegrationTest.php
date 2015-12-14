<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapRobotsTxtIntegrationTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Url;

/**
 * Tests the robots.txt file existance.
 */
class XmlSitemapRobotsTxtIntegrationTest extends XmlSitemapTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('xmlsitemap', 'robotstxt');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap robots.txt',
      'description' => 'Integration tests for the XML sitemap and robots.txt module.',
      'group' => 'XML sitemap',
      'dependencies' => array('robotstxt'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Test if sitemap link is included in robots.txt file.
   */
  public function testRobotsTxt() {
    // Request the un-clean robots.txt path so this will work in case there is
    // still the robots.txt file in the root directory.
    if (file_exists(DRUPAL_ROOT . '/robots.txt')) {
      $this->error(t('Unable to proceed with configured robots.txt tests: A local file already exists at @s, so the menu override in this module will never run.', array('@s' => DRUPAL_ROOT . '/robots.txt')));
      return;
    }
    $this->drupalGet('/robots.txt');
    $this->assertRaw('Sitemap: ' . Url::fromRoute('xmlsitemap.sitemap_xml', [], array('absolute' => TRUE)));
  }

}
