<?php

namespace Drupal\purge\Tests\Queue;

use Drupal\purge\Tests\Queue\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\MemoryQueue.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface
 */
class MemoryQueueTest extends PluginTestBase {
  protected $plugin_id = 'memory';

}
