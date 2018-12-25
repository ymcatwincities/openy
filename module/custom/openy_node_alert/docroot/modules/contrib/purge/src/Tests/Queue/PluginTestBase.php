<?php

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
  public function setUp() {
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
  public function testDataStorageIntegrity() {
    $samples = [
      'a' => 'string',
      'b' => 'StrinG with Capitalization',
      'c' => 1,
      'd' => -1500,
      'e' => 0.1500,
      'f' => -99999,
      'g' => NULL,
      'h' => FALSE,
      'i' => TRUE,
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
    for ($i = 1; $i <= 5; $i++) {
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
    for ($i = 1; $i <= 10; $i++) {
      $this->queue->createItem($i);
    }
    for ($i = 10; $i > 5; $i--) {
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
  public function testCreateQueue() {
    $this->queue->createItem([1, 2, 3]);
    $this->queue->createQueue();
    $this->assertEqual(1, $this->queue->numberOfItems());

    $this->queue->deleteQueue();
  }

  /**
   * Test creating, claiming and releasing of items.
   */
  public function testCreatingClaimingAndReleasing() {
    $this->queue->createItem([1, 2, 3]);
    $claim = $this->queue->claimItem(3600);
    // Change the claim data to verify that releasing changed data, persists.
    $claim->data = [4, 5, 6];
    $this->assertFalse($this->queue->claimItem(3600));
    $this->assertTrue($this->queue->releaseItem($claim));
    $this->assertTrue($claim = $this->queue->claimItem(3600));
    $this->assertIdentical([4, 5, 6], $claim->data);
    $this->queue->releaseItem($claim);
    $this->assertIdentical(4, count($this->queue->createItemMultiple([1, 2, 3, 4])));
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
  public function testLeaseTime() {
    $this->assertFalse($this->queue->claimItem());
    $this->queue->createItem($this->randomString());
    $this->assertEqual(1, $this->queue->numberOfItems());
    $this->assertTrue($this->queue->claimItem(5));
    $this->assertFalse($this->queue->claimItem());
    sleep(6);
    $this->assertTrue($this->queue->claimItem(2));
    $this->assertFalse($this->queue->claimItem(1));
    sleep(3);
    $this->assertTrue($this->queue->claimItem(2));
    $this->queue->deleteQueue();

    // Test claimItemMultiple which should work in the same way.
    $this->assertTrue(empty($this->queue->claimItemMultiple(2)));
    for ($i = 1; $i <= 5; $i++) {
      $this->queue->createItem($this->randomString());
    }
    $this->assertIdentical(5, count($this->queue->claimItemMultiple(5, 5)));
    $this->assertTrue(empty($this->queue->claimItemMultiple(2)));
    sleep(6);
    $this->assertIdentical(5, count($this->queue->claimItemMultiple(5, 5)));

    $this->queue->deleteQueue();
  }

  /**
   * Test the paging behavior.
   */
  public function testPaging() {
    $this->assertEqual(0, $this->queue->numberOfItems());
    // Assert that setting the paging limit, gets reflected properly.
    $this->assertEqual($this->queue->selectPageLimit(), 15);
    $this->assertEqual($this->queue->selectPageLimit(37), 37);
    $this->assertEqual($this->queue->selectPageLimit(), 37);
    $this->assertEqual($this->queue->selectPageLimit(7), 7);
    $this->assertEqual($this->queue->selectPageLimit(), 7);
    // Assert that an empty queue, results in no pages at all.
    $this->assertEqual($this->queue->selectPageMax(), 0);
    $this->assertEqual($this->queue->selectPage(), []);
    // Create 25 items, which should be 3,5 (so 4) pages of 7 items each.
    $this->assertIdentical(25, count($this->queue->createItemMultiple([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25])));
    $this->assertEqual($this->queue->selectPageMax(), 4);
    $this->assertEqual($this->queue->selectPageLimit(5), 5);
    $this->assertEqual($this->queue->selectPageMax(), 5);
    $this->assertEqual($this->queue->selectPageLimit(7), 7);
    // Test the data in the first page, omit the page parameter which is 1.
    $page_1 = $this->queue->selectPage();
    $this->assertEqual(count($page_1), 7);
    $this->assertEqual($page_1[0]->item_id, 1);
    $this->assertEqual($page_1[1]->item_id, 2);
    $this->assertEqual($page_1[2]->item_id, 3);
    $this->assertEqual($page_1[3]->item_id, 4);
    $this->assertEqual($page_1[4]->item_id, 5);
    $this->assertEqual($page_1[5]->item_id, 6);
    $this->assertEqual($page_1[6]->item_id, 7);
    $this->assertEqual($page_1[0]->data, 1);
    $this->assertEqual($page_1[1]->data, 2);
    $this->assertEqual($page_1[2]->data, 3);
    $this->assertEqual($page_1[3]->data, 4);
    $this->assertEqual($page_1[4]->data, 5);
    $this->assertEqual($page_1[5]->data, 6);
    $this->assertEqual($page_1[6]->data, 7);
    $this->assertEqual($page_1[0]->expire, 0);
    $this->assertEqual($page_1[1]->expire, 0);
    $this->assertEqual($page_1[2]->expire, 0);
    $this->assertEqual($page_1[3]->expire, 0);
    $this->assertEqual($page_1[4]->expire, 0);
    $this->assertEqual($page_1[5]->expire, 0);
    $this->assertEqual($page_1[6]->expire, 0);
    // Test the second page, which should be 7 items.
    $page_2 = $this->queue->selectPage(2);
    $this->assertEqual(count($page_2), 7);
    $this->assertEqual($page_2[0]->data, 8);
    $this->assertEqual($page_2[1]->data, 9);
    $this->assertEqual($page_2[2]->data, 10);
    $this->assertEqual($page_2[3]->data, 11);
    $this->assertEqual($page_2[4]->data, 12);
    $this->assertEqual($page_2[5]->data, 13);
    $this->assertEqual($page_2[6]->data, 14);
    // Test the third page, which should be 7 items as well.
    $page_3 = $this->queue->selectPage(3);
    $this->assertEqual(count($page_3), 7);
    $this->assertEqual($page_3[0]->data, 15);
    $this->assertEqual($page_3[1]->data, 16);
    $this->assertEqual($page_3[2]->data, 17);
    $this->assertEqual($page_3[3]->data, 18);
    $this->assertEqual($page_3[4]->data, 19);
    $this->assertEqual($page_3[5]->data, 20);
    $this->assertEqual($page_3[6]->data, 21);
    // The last page, should only be 4 items in total.
    $page_4 = $this->queue->selectPage(4);
    $this->assertEqual(count($page_4), 4);
    $this->assertEqual($page_4[0]->data, 22);
    $this->assertEqual($page_4[1]->data, 23);
    $this->assertEqual($page_4[2]->data, 24);
    $this->assertEqual($page_4[3]->data, 25);
    // And obviously, there should be no fifth page.
    $this->assertEqual($this->queue->selectPage(5), []);

    $this->queue->deleteQueue();
  }

}
