<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Processor\ServiceTest.
 */

namespace Drupal\purge\Tests\Processor;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsService.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService
 * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.processors';
  public static $modules = ['purge_processor_test'];

  /**
   * Set up the test.
   */
  function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelTestBase::setUp();
    $this->installConfig(['purge_processor_test']);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::count
   */
  public function testCount() {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Countable);
    $this->assertEqual(2, count($this->service));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::get
   */
  public function testGet() {
    $this->initializeService();
    $this->assertTrue($this->service->get('a') instanceof ProcessorInterface);
    $this->assertTrue($this->service->get('b') instanceof ProcessorInterface);
    $this->assertFalse($this->service->get('c'));
    $this->service->setPluginsEnabled(['c']);
    $this->assertTrue($this->service->get('c') instanceof ProcessorInterface);
  }

  /**
   * Tests the \Iterator implementation.
   *
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::current
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::key
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::next
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::rewind
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::valid
   */
  public function testIteration() {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Iterator);
    $items = 0;
    foreach ($this->service as $instance) {
      $this->assertTrue($instance instanceof ProcessorInterface);
      $items++;
    }
    $this->assertEqual(2, $items);
    $this->assertFalse($this->service->current());
    $this->assertFalse($this->service->valid());
    $this->assertNull($this->service->rewind());
    $this->assertEqual('b', $this->service->current()->getPluginId());
    $this->assertNull($this->service->next());
    $this->assertEqual('a', $this->service->current()->getPluginId());
    $this->assertTrue($this->service->valid());
  }

}
