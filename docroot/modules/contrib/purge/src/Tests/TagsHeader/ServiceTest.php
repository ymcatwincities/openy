<?php

namespace Drupal\purge\Tests\TagsHeader;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;

/**
 * Tests \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService
 * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.tagsheaders';
  public static $modules = ['purge_tagsheader_test'];

  /**
   * All tagsheader plugins that can be expected.
   *
   * @var string[]
   */
  protected $plugins = [
    'a',
    'b',
    'c',
  ];

  /**
   * Set up the test.
   */
  public function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelTestBase::setUp();
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::count
   */
  public function testCount() {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Countable);
    $this->assertEqual(count($this->plugins), count($this->service));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::getPluginsEnabled
   */
  public function testGetPluginsEnabled() {
    $this->initializeService();
    $plugin_ids = $this->service->getPluginsEnabled();
    foreach ($this->plugins as $plugin_id) {
      $this->assertTrue(in_array($plugin_id, $plugin_ids));
    }
  }

  /**
   * Tests the \Iterator implementation.
   *
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::current
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::key
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::next
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::rewind
   * @see \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersService::valid
   */
  public function testIteration() {
    $this->initializeService();
    $this->assertIterator('\Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface',
      $this->plugins
    );
  }

}
