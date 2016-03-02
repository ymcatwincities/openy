<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Queuer\ServiceTest.
 */

namespace Drupal\purge\Tests\Queuer;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersService.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService
 * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.queuers';
  public static $modules = ['purge_queuer_test'];

  /**
   * Set up the test.
   */
  function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelTestBase::setUp();
    $this->installConfig(['purge_queuer_test']);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersService::count
   */
  public function testCount() {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Countable);
    $this->assertEqual(2, count($this->service));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Processor\QueuersService::get
   */
  public function testGet() {
    $this->initializeService();
    $this->assertTrue($this->service->get('a') instanceof QueuerInterface);
    $this->assertTrue($this->service->get('b') instanceof QueuerInterface);
    $this->assertFalse($this->service->get('c'));
    $this->service->setPluginsEnabled(['c']);
    $this->assertTrue($this->service->get('c') instanceof QueuerInterface);
  }

  /**
   * Tests the \Iterator implementation.
   *
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::current
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::key
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::next
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::rewind
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::valid
   */
  public function testIteration() {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Iterator);
    $items = 0;
    foreach ($this->service as $instance) {
      $this->assertTrue($instance instanceof QueuerInterface);
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
