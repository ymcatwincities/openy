<?php

namespace Drupal\purge\Tests\Queue;

use Drupal\purge\Tests\Queue\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\DatabaseQueue.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface
 */
class DatabaseQueueTest extends PluginTestBase {
  protected $plugin_id = 'database';

  /**
   * {@inheritdoc}
   */
  protected function setUpQueuePlugin() {
    // Override parent::setUpQueuePlugin() to always recreate the instance, else
    // the tests fail: "failed to instantiate user-supplied statement class".
    $this->queue = $this->pluginManagerPurgeQueue->createInstance($this->plugin_id);
    $this->assertNull($this->queue->createQueue());
  }

}
