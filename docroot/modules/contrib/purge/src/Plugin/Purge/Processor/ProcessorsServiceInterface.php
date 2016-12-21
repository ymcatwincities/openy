<?php

namespace Drupal\purge\Plugin\Purge\Processor;

use Drupal\purge\ServiceInterface;
use Drupal\purge\ModifiableServiceInterface;

/**
 * Describes a service that provides access to loaded processors.
 */
interface ProcessorsServiceInterface extends ServiceInterface, ModifiableServiceInterface, \Iterator, \Countable {

  /**
   * Get the requested processor instance.
   *
   * @param string $plugin_id
   *   The plugin ID of the processor you want to retrieve.
   *
   * @return \Drupal\purge\Plugin\Purge\Processor\ProcessorInterface|false
   */
  public function get($plugin_id);

}
