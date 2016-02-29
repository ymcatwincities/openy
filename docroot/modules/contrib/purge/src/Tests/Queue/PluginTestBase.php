<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Queue\PluginTestBase.
 */

namespace Drupal\purge\Tests\Queue;

use Drupal\purge\Tests\KernelTestBase;

/**
 * Provides a abstract test class to aid thorough tests for queue plugins.
 *
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface
 */
abstract class PluginTestBase extends KernelTestBase {

  /**
   * The plugin ID of the queue plugin being tested.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * The plugin manager for queues ('plugin.manager.purge.queue').
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\PluginManager
   */
  protected $pluginManagerPurgeQueue;

  /**
   * The queue plugin being tested.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Set up the test.
   */
  function setUp() {
    parent::setUp();
    $this->pluginManagerPurgeQueue = $this->container->get('plugin.manager.purge.queue');
    $this->setUpQueuePlugin();
  }

  /**
   * Load the queue plugin and make $this->queue available.
   */
  protected function setUpQueuePlugin() {
    if (!is_null($this->queue)) {
      return;
    }
    $this->queue = $this->pluginManagerPurgeQueue->createInstance($this->plugin_id);
    $this->assertNull($this->queue->createQueue());
  }

  /**
   * Test the data integrity of data stored in the queue.
   */
  function testDataStorageIntegrity() {
    $samples = [
      'a' => 'string',
      'b' => 'StrinG with Capitalization',
      'c' => 1,
      'd' => -1500,
      'e' => 0.1500,
      'f' => -99999,
      'g' => NULL,
      'h' => FALSE,
      'i' => TRUE
    ];

    // Test if we get back the exact same thing if we store it as scalar value.
    foreach ($samples as $sample) {
      $this->queue->createItem($sample);
      $reference = $this->queue->claimItem(3600);
      $this->assertIdentical($sample, $reference->data);
    }

    // Test that we get the same data back by storing it in an object.
    $this->queue->createItem($samples);
    $reference = $this->queue->claimItem(3600);
    $this->assertIdentical($samples, $reference->data);

    $this->queue->deleteQueue();
  }

  /**
   * Test the queue counter by deleting items and emptying the queue.
   */
  public function testQueueCountBehavior() {
    $this->assertNull($this->queue->deleteQueue());
    $this->assertTrue(is_int($this->queue->numberOfItems()));
    $this->assertEqual(0, $this->queue->numberOfItems());
    for ($i=1; $i <= 5; $i++) {
      $id = $this->queue->createItem($i);
      $this->assertTrue(is_scalar($id));
      $this->assertTrue($id !== FALSE);
      $this->assertEqual($i, $this->queue->numberOfItems());
    }
    $this->assertTrue(is_object($this->queue->claimItem(1)));
    $this->assertTrue(is_int($this->queue->numberOfItems()));
    $this->assertEqual(5, $this->queue->numberOfItems());
    $this->assertNull($this->queue->deleteQueue());
    $this->assertEqual(0, $this->queue->numberOfItems());
    for ($i=1; $i <= 10; $i++) {
      $this->queue->createItem($i);
    }
    for ($i=10; $i > 5; $i--) {
      $claim = $this->queue->claimItem();
      $this->assertNull($this->queue->deleteItem($claim));
      $this->assertEqual($i-1, $this->queue->numberOfItems());
    }
    $claims = $this->queue->claimItemMultiple(5);
    $this->queue->deleteItemMultiple($claims);
    $this->assertEqual(0, $this->queue->numberOfItems());

    $this->queue->deleteQueue();
  }

  /**
   * Test that createQueue() doesn't empty the queue if already created.
   */
  function testCreateQueue() {
    $this->queue->createItem([1,2,3]);
    $this->queue->createQueue();
    $this->assertEqual(1, $this->queue->numberOfItems());

    $this->queue->deleteQueue();
  }

  /**
   * Test creating, claiming and releasing of items.
   */
  function testCreatingClaimingAndReleasing() {
    $this->queue->createItem([1,2,3]);
    $claim = $this->queue->claimItem(3600);
    // Change the claim data to verify that releasing changed data, persists.
    $claim->data = [4,5,6];
    $this->assertFalse($this->queue->claimItem(3600));
    $this->assertTrue($this->queue->releaseItem($claim));
    $this->assertTrue($claim = $this->queue->claimItem(3600));
    $this->assertIdentical([4,5,6], $claim->data);
    $this->queue->releaseItem($claim);
    $this->assertIdentical(4, count($this->queue->createItemMultiple([1,2,3,4])));
    $claims = $this->queue->claimItemMultiple(5, 3600);
    foreach ($claims as $i => $claim) {
      $claim->data = 9;
      $claims[$i] = $claim;
    }
    $this->assertIdentical([], $this->queue->claimItemMultiple(5, 3600));
    $this->assertIdentical([], $this->queue->releaseItemMultiple($claims));
    $claims = $this->queue->claimItemMultiple(5, 3600);
    $this->assertIdentical(5, count($claims));
    foreach ($claims as $i => $claim) {
      $this->assertEqual(9, $claim->data);
    }

    $this->queue->deleteQueue();
  }

  /**
   * Test the behavior of lease time when claiming queue items.
   */
  function testLeaseTime() {
    $this->assertFalse($this->queue->claimItem());
    $this->queue->createItem($this->randomString());
    $this->assertEqual(1, $this->queue->numberOfItems());
    $this->assertTrue($this->queue->claimItem(5));
    $this->assertFalse($this->queue->claimItem());
    sleep(6);
    $this->assertTrue($this->queue->claimItem(2));
    $this->assertFalse($this->queue->claimItem(1));
    sleep(3);
    $this->assertTrue($claim = $this->queue->claimItem(2));
    $this->queue->deleteQueue();

    // Test claimItemMultiple which should work in the same way.
    $this->assertTrue(empty($this->queue->claimItemMultiple(2)));
    for ($i=1; $i <= 5; $i++) {
      $this->queue->createItem($this->randomString());
    }
    $this->assertIdentical(5, count($this->queue->claimItemMultiple(5, 5)));
    $this->assertTrue(empty($this->queue->claimItemMultiple(2)));
    sleep(6);
    $this->assertIdentical(5, count($this->queue->claimItemMultiple(5, 5)));

    $this->queue->deleteQueue();
  }

}
