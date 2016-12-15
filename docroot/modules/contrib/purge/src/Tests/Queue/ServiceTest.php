<?php

namespace Drupal\purge\Tests\Queue;

use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueService
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.queue';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'purge_queuer_test'];

  /**
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface
   */
  protected $queuer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['queue']);
    $this->initializeQueueService();
    $this->initializeQueuersService();
    $this->queuer = $this->purgeQueuers->get('a');
    $this->purgeQueue->emptyQueue();
    $this->initializeInvalidationFactoryService();
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::getPlugins
   */
  public function testGetPlugins() {
    $this->assertTrue(is_array($this->purgeQueue->getPlugins()));
    $this->assertTrue(isset($this->purgeQueue->getPlugins()['file']));
    $this->assertTrue(isset($this->purgeQueue->getPlugins()['memory']));
    $this->assertTrue(isset($this->purgeQueue->getPlugins()['database']));
    $this->assertFalse(isset($this->purgeQueue->getPlugins()['null']));
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::getPluginsEnabled
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::setPluginsEnabled
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::reload
   */
  public function testSettingAndGettingPlugins() {
    $this->purgeQueue->setPluginsEnabled(['file']);
    $this->assertTrue(in_array('file', $this->purgeQueue->getPluginsEnabled()));
    $this->purgeQueue->setPluginsEnabled(['memory']);
    $this->assertTrue(in_array('memory', $this->purgeQueue->getPluginsEnabled()));
    $thrown = FALSE;
    try {
      $this->purgeQueue->setPluginsEnabled(['DOESNOTEXIST']);
    }
    catch (\LogicException $e) {
      $thrown = $e instanceof \LogicException;
    }
    $this->assertTrue($thrown);
    $thrown = FALSE;
    try {
      $this->purgeQueue->setPluginsEnabled([]);
    }
    catch (\LogicException $e) {
      $thrown = $e instanceof \LogicException;
    }
    $this->assertTrue($thrown);
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::add
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::claim
   */
  public function testAddClaim() {
    $this->assertTrue(empty($this->purgeQueue->claim(10, 10)));
    $i = $this->getInvalidations(1);
    $this->assertNull($this->purgeQueue->add($this->queuer, [$i]));
    $claims = $this->purgeQueue->claim(100, 10);
    $this->assertTrue(is_array($claims));
    $this->assertEqual(1, count($claims));
    $this->assertTrue($claims[0] instanceof InvalidationInterface);
    $this->assertTrue($claims[0]->getId() === $i->getId());
    $this->assertEqual($claims[0]->getState(), InvalidationInterface::FRESH);
    // Now test with more objects.
    $this->purgeQueue->emptyQueue();
    $this->purgeQueue->add($this->queuer, $this->getInvalidations(50));
    $this->assertEqual(50, $this->purgeQueue->numberOfItems());
    $this->assertTrue(37 === count($this->purgeQueue->claim(37, 10)));
    $this->assertTrue(13 === count($this->purgeQueue->claim(15, 10)));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::emptyQueue
   */
  public function testEmptyQueue() {
    $this->purgeQueue->add($this->queuer, $this->getInvalidations(10));
    $this->purgeQueue->emptyQueue();
    $this->assertTrue(empty($this->purgeQueue->claim(10, 10)));
    $this->assertTrue(is_int($this->purgeQueue->numberOfItems()));
    $this->assertEqual(0, $this->purgeQueue->numberOfItems());
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::reload
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::commit
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::claim
   */
  public function testStateConsistency() {
    $this->purgeQueue->setPluginsEnabled(['database']);
    // Add four objects to the queue. reload it, and verify they're the same.
    $invalidations = $this->getInvalidations(4);
    foreach ($invalidations as $invalidation) {
      $invalidation->setStateContext('purger1');
    }
    $invalidations[0]->setState(InvalidationInterface::SUCCEEDED);
    $invalidations[1]->setState(InvalidationInterface::PROCESSING);
    $invalidations[2]->setState(InvalidationInterface::FAILED);
    $invalidations[3]->setState(InvalidationInterface::NOT_SUPPORTED);
    foreach ($invalidations as $invalidation) {
      $invalidation->setStateContext(NULL);
    }
    $this->purgeQueue->add($this->queuer, $invalidations);
    // Reload so that \Drupal\purge\Plugin\Purge\Queue\QueueService::$buffer gets cleaned too.
    $this->purgeQueue->reload();
    // Now it has to refetch all objects, assure their states.
    $claims = $this->purgeQueue->claim(3, 1);
    $this->assertTrue(InvalidationInterface::SUCCEEDED === $claims[0]->getState());
    $this->assertTrue(InvalidationInterface::PROCESSING === $claims[1]->getState());
    $this->assertTrue(InvalidationInterface::FAILED === $claims[2]->getState());
    $this->assertTrue(InvalidationInterface::NOT_SUPPORTED === $this->purgeQueue->claim(10, 10)[0]->getState());
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::release
   */
  public function testRelease() {
    $this->assertTrue(empty($this->purgeQueue->claim(10, 10)));
    $this->purgeQueue->add($this->queuer, $this->getInvalidations(4));
    $claims = $this->purgeQueue->claim(4, 10);
    $this->assertTrue(empty($this->purgeQueue->claim(10, 10)));
    $this->purgeQueue->release([$claims[0]]);
    $this->assertTrue(1 === count($this->purgeQueue->claim(4, 1)));
    $this->purgeQueue->release([$claims[1], $claims[2], $claims[3]]);
    $this->assertTrue(3 === count($this->purgeQueue->claim(4, 1)));

    // Assert that the claims become available again after our 1*4=4s expired.
    sleep(5);
    $this->assertTrue(4 === count($this->purgeQueue->claim(10, 10)));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::delete
   */
  public function testDelete() {
    $this->assertTrue(empty($this->purgeQueue->claim(10, 10)));
    $this->purgeQueue->add($this->queuer, $this->getInvalidations(3));
    $claims = $this->purgeQueue->claim(3, 1);
    $this->purgeQueue->delete([array_pop($claims)]);
    sleep(4);
    $claims = $this->purgeQueue->claim(3, 1);
    $this->assertTrue(2 === count($claims));
    $this->purgeQueue->delete($claims);
    sleep(4);
    $this->assertTrue(empty($this->purgeQueue->claim(10, 10)));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::handleResults
   */
  public function testHandleResults() {
    $this->purgeQueue->add($this->queuer, $this->getInvalidations(5));

    // Claim for 1s, mark as purged and assert it gets deleted.
    $claims = $this->purgeQueue->claim(1, 10);
    $claims[0]->setStateContext('purger1');
    $claims[0]->setState(InvalidationInterface::SUCCEEDED);
    $this->purgeQueue->handleResults($claims);
    sleep(3);

    // Claim for 2s, mark all as not-successfull and assert releases.
    $claims = $this->purgeQueue->claim(10, 2);
    $this->assertTrue(4 === count($claims));
    foreach ($claims as $claim) {
      $claim->setStateContext('purger1');
    }
    $claims[0]->setState(InvalidationInterface::SUCCEEDED);
    $claims[1]->setState(InvalidationInterface::PROCESSING);
    $claims[2]->setState(InvalidationInterface::FAILED);
    $claims[3]->setState(InvalidationInterface::NOT_SUPPORTED);
    $this->purgeQueue->handleResults($claims);
    sleep(4);
    $this->assertTrue(3 === count($this->purgeQueue->claim(10, 10)));
  }

}
