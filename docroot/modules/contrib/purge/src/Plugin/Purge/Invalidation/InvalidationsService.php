<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsService.
 */

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidation;

/**
 * Provides a service that instantiates invalidation objects on-demand.
 */
class InvalidationsService extends ServiceBase implements InvalidationsServiceInterface {

  /**
   * Incremental ID counter for handing out unique instance IDs.
   *
   * @var int
   */
  protected $instance_counter = 0;

  /**
   * As immutable instances cannot change the queue, they are counted negative
   * and the counter only decrements. Its IDs can never clash with real ones.
   *
   * @var int
   */
  protected $instance_counter_immutables = -1;

  /**
   * Instantiates a \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsService.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   */
  public function __construct(PluginManagerInterface $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function get($plugin_id, $expression = NULL) {
    return $this->pluginManager->createInstance(
      $plugin_id, [
        'id' => $this->instance_counter++,
        'expression' => $expression
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getImmutable($plugin_id, $expression = NULL) {
    return new ImmutableInvalidation(
      $this->pluginManager->createInstance(
        $plugin_id, [
          'id' => $this->instance_counter_immutables--,
          'expression' => $expression
        ]
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFromQueueData($item_data) {
    $instance = $this->get($item_data[0], $item_data[2]);
    // Replay the purger states as stored in item_data[1].
    foreach ($item_data[1] as $purger_instance_id => $state) {
      $instance->setStateContext($purger_instance_id);
      $instance->setState($state);
    }
    $instance->setStateContext(NULL);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getImmutableFromQueueData($item_data) {
    $instance = $this->pluginManager->createInstance(
      $item_data[0], [
        'id' => $this->instance_counter_immutables--,
        'expression' => $item_data[2]
      ]
    );
    // Replay the purger states as stored in item_data[1].
    foreach ($item_data[1] as $purger_instance_id => $state) {
      $instance->setStateContext($purger_instance_id);
      $instance->setState($state);
    }
    $instance->setStateContext(NULL);
    return new ImmutableInvalidation($instance);
  }

}
