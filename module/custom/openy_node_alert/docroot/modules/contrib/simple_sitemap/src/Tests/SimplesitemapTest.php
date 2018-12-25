<?php

namespace Drupal\simple_sitemap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Class SimplesitemapTest.
 *
 * Tests Simple XML sitemap functional integration.
 *
 * @package Drupal\simple_sitemap\Tests
 * @group simple_sitemap
 */
class SimplesitemapTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['simple_sitemap', 'node'];

  protected $generator;
  protected $node;
  protected $node2;
  protected $privilegedUser;

  // Uncomment to disable strict schema checks.
//  protected $strictConfigSchema = FALSE;

  /**
   * Implements setup().
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);
    $this->node = $this->createNode(['title' => 'Node', 'type' => 'page']);
    $this->node2 = $this->createNode(['title' => 'Node2', 'type' => 'page']);

    $perms = array_keys(\Drupal::service('user.permissions')->getPermissions());
    $this->privilegedUser = $this->drupalCreateUser($perms);

    $this->generator = \Drupal::service('simple_sitemap.generator');
  }

  /**
   * Verify sitemap.xml has the link to the front page with priority '1.0' after first generation.
   */
  public function testInitialGeneration() {
    $this->generator->generateSitemap('nobatch');
    $this->drupalGet('sitemap.xml');
    $this->assertRaw('urlset');
    $this->assertText($GLOBALS['base_url']);
    $this->assertText('1.0');
  }

  /**
   *
   */
  public function testSetBundleSettings() {

    // Index new bundle.
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.5');

    // Only change bundle priority.
    $this->generator->setBundleSettings('node', 'page', ['priority' => 0.9])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertNoText('0.5');
    $this->assertText('0.9');

    // Set bundle 'index' setting to 0.
    $this->generator->setBundleSettings('node', 'page', ['index' => 0])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoText('node/' . $this->node->id());
    $this->assertNoText('0.5');
    $this->assertNoText('0.9');
  }

  /**
   * Test cacheability of the response.
   */
  public function testCacheability() {
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5])
      ->generateSitemap('nobatch');

    // Verify the cache was flushed and node is in the sitemap.
    $this->drupalGet('sitemap.xml');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertText('node/' . $this->node->id());

    // Verify the sitemap is taken from cache on second call and node is in the sitemap.
    $this->drupalGet('sitemap.xml');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertText('node/' . $this->node->id());
  }

  /**
   * Test overriding of bundle settings for a single entity.
   */
  public function testSetEntityInstanceSettings() {
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5])
      ->setEntityInstanceSettings('node', $this->node->id(), ['index' => TRUE, 'priority' => 0.1])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.5');
    $this->assertText('0.1');
  }

  /**
   * Test disabling sitemap support for an entity type.
   */
  public function testDisableEntityType() {
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5])
      ->disableEntityType('node');

    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertNoText('Simple XML sitemap');

    $this->generator->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoText('node/' . $this->node->id());
    $this->assertNoText('0.5');
  }

  /**
   * Test enabling sitemap support for an entity type.
   */
  public function testEnableEntityType() {
    $this->generator->disableEntityType('node')
      ->enableEntityType('node')
      ->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5]);

    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertText('Simple XML sitemap');

    $this->generator->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.5');
  }

  /**
   * Test removing all custom paths from the sitemap settings.
   */
  public function testRemoveCustomLinks() {
    $this->generator->removeCustomLinks()
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoText($GLOBALS['base_url']);
  }

  /**
   * Test sitemap index.
   */
  public function testSitemapIndex() {
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5])
      ->saveSetting('max_links', 1)
      ->removeCustomLinks()
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('sitemaps/1/sitemap.xml');
    $this->assertText('sitemaps/2/sitemap.xml');

    $this->drupalGet('sitemaps/1/sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.5');

    $this->drupalGet('sitemaps/2/sitemap.xml');
    $this->assertText('node/' . $this->node2->id());
    $this->assertText('0.5');
  }

  /**
   * Test setting the base URL.
   */
  public function testSetBaseUrl() {
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5])
      ->saveSetting('base_url', 'http://base_url_test')
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('http://base_url_test');
  }

  /**
   * Test setting the base URL in the sitemap index.
   */
  public function testSetBaseUrlInSitemapIndex() {
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE, 'priority' => 0.5])
      ->saveSetting('max_links', 1)
      ->saveSetting('base_url', 'http://base_url_test')
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('http://base_url_test');
  }

  /**
   * Test adding a custom link to the sitemap.
   */
  public function testAddCustomLink() {
    $this->generator->addCustomLink('/node/' . $this->node->id(), ['priority' => 0.2])
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertText('node/' . $this->node->id());
    $this->assertText('0.2');
  }

  /**
   * Test removing custom links from the sitemap.
   */
  public function testRemoveCustomLink() {
    $this->generator->addCustomLink('/node/' . $this->node->id(), ['priority' => 0.2])
      ->removeCustomLink('/node/' . $this->node->id())
      ->generateSitemap('nobatch');

    $this->drupalGet('sitemap.xml');
    $this->assertNoText('node/' . $this->node->id());
    $this->assertNoText('0.2');
  }

}
