<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\TestTrait.
 */

namespace Drupal\purge\Tests;

/**
 * Several helper properties and methods for purge tests.
 *
 * @see \Drupal\purge\Tests\KernelTestBase
 * @see \Drupal\purge\Tests\WebTestBase
 */
trait TestTrait {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
   */
  protected $purgeProcessors;

  /**
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface
   */
  protected $purgeQueueStats;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface
   */
  protected $purgeQueueTxbuffer;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface
   */
  protected $purgeQueuers;

  /**
   * @var \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * Make $this->purgeProcessors available.
   *
   * @param string[] $plugin_ids
   *   Array of plugin ids to be enabled.
   */
  protected function initializeProcessorsService($plugin_ids = []) {
    if (is_null($this->purgeProcessors)) {
      $this->purgeProcessors = $this->container->get('purge.processors');
    }
    if (count($plugin_ids)) {
      $this->purgeProcessors->reload();
      $this->purgeProcessors->setPluginsEnabled($plugin_ids);
    }
  }

  /**
   * Make $this->purgePurgers available.
   *
   * @param string[] $plugin_ids
   *   Array of plugin ids to be enabled.
   */
  protected function initializePurgersService($plugin_ids = []) {
    if (is_null($this->purgePurgers)) {
      $this->purgePurgers = $this->container->get('purge.purgers');
    }
    if (count($plugin_ids)) {
      $ids = [];
      foreach ($plugin_ids as $i => $plugin_id) {
        $ids["id$i"] = $plugin_id;
      }
      $this->purgePurgers->reload();
      $this->purgePurgers->setPluginsEnabled($ids);
    }
  }

  /**
   * Make $this->purgeInvalidationFactory available.
   */
  protected function initializeInvalidationFactoryService() {
    if (is_null($this->purgeInvalidationFactory)) {
      $this->purgeInvalidationFactory = $this->container->get('purge.invalidation.factory');
    }
  }

  /**
   * Make $this->purgeQueue available.
   *
   * @param null|string[] $plugin_id
   *   The plugin ID of the queue to be configured.
   */
  protected function initializeQueueService($plugin_id = NULL) {
    if (is_null($this->purgeQueue)) {
      $this->purgeQueue = $this->container->get('purge.queue');
    }
    if (!is_null($plugin_id)) {
      $this->purgeQueue->reload();
      $this->purgeQueue->setPluginsEnabled([$plugin_id]);
    }
  }

  /**
   * Make $this->purgeQueuers available.
   *
   * @param string[] $plugin_ids
   *   Array of plugin ids to be enabled.
   */
  protected function initializeQueuersService($plugin_ids = []) {
    if (is_null($this->purgeQueuers)) {
      $this->purgeQueuers = $this->container->get('purge.queuers');
    }
    if (count($plugin_ids)) {
      $this->purgeQueuers->reload();
      $this->purgeQueuers->setPluginsEnabled($plugin_ids);
    }
  }

  /**
   * Make $this->purgeDiagnostics available.
   */
  protected function initializeDiagnosticsService() {
    if (is_null($this->purgeDiagnostics)) {
      $this->purgeDiagnostics = $this->container->get('purge.diagnostics');
    }
    else {
      $this->purgeDiagnostics->reload();
    }
  }

  /**
   * Create $number requested invalidation objects.
   *
   * @param int $number
   *   The number of objects to generate.
   *
   * @return array|\Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
   */
  public function getInvalidations($number) {
    $this->initializeInvalidationFactoryService();
    $set = [];
    for ($i = 0; $i < $number; $i++) {
      $set[] = $this->purgeInvalidationFactory->get('everything');
    }
    return ($number === 1) ? $set[0] : $set;
  }

  /**
   * Switch to the memory queue backend.
   */
  public function setMemoryQueue() {
    $this->configFactory = $this->container->get('config.factory');
    $this->config('purge.plugins')
      ->set('queue', 'memory')
      ->save();
  }

}
