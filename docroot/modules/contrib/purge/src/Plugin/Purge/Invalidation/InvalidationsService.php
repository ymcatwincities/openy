<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidation;
use Drupal\purge\Plugin\Purge\Queue\ProxyItemInterface;

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
        'expression' => $expression,
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
          'expression' => $expression,
        ]
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFromQueueData($item_data) {
    $instance = $this->get(
      $item_data[ProxyItemInterface::DATA_INDEX_TYPE],
      $item_data[ProxyItemInterface::DATA_INDEX_EXPRESSION]
    );

    // Replay stored purger states.
    if (isset($item_data[ProxyItemInterface::DATA_INDEX_STATES])) {
      foreach ($item_data[ProxyItemInterface::DATA_INDEX_STATES] as $id => $state) {
        $instance->setStateContext($id);
        $instance->setState($state);
      }
      $instance->setStateContext(NULL);
    }

    // Replay stored properties.
    if (isset($item_data[ProxyItemInterface::DATA_INDEX_PROPERTIES])) {
      foreach ($item_data[ProxyItemInterface::DATA_INDEX_PROPERTIES] as $id => $properties) {
        $instance->setStateContext($id);
        foreach ($properties as $key => $value) {
          $instance->setProperty($key, $value);
        }
      }
      $instance->setStateContext(NULL);
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getImmutableFromQueueData($item_data) {
    $instance = $this->pluginManager->createInstance(
      $item_data[ProxyItemInterface::DATA_INDEX_TYPE], [
        'id' => $this->instance_counter_immutables--,
        'expression' => $item_data[ProxyItemInterface::DATA_INDEX_EXPRESSION],
      ]
    );

    // Replay stored purger states.
    if (isset($item_data[ProxyItemInterface::DATA_INDEX_STATES])) {
      foreach ($item_data[ProxyItemInterface::DATA_INDEX_STATES] as $id => $state) {
        $instance->setStateContext($id);
        $instance->setState($state);
      }
      $instance->setStateContext(NULL);
    }

    // Replay stored properties.
    if (isset($item_data[ProxyItemInterface::DATA_INDEX_PROPERTIES])) {
      foreach ($item_data[ProxyItemInterface::DATA_INDEX_PROPERTIES] as $id => $properties) {
        $instance->setStateContext($id);
        foreach ($properties as $key => $value) {
          $instance->setProperty($key, $value);
        }
      }
      $instance->setStateContext(NULL);
    }

    return new ImmutableInvalidation($instance);
  }

}
