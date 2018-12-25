<?php

namespace Drupal\purge\Counter;

use Drupal\purge\Counter\CounterInterface;

/**
 * Describes a numeric counter that can be stored elsewhere.
 */
interface PersistentCounterInterface extends CounterInterface {

  /**
   * Set the callback that gets called when writes occur.
   *
   * The callback is called every time the counter changes value. The first
   * parameter passed to the callback is the given $id parameter and the second
   * parameter is the new value of the counter.
   *
   * @param string $id
   *   A unique identifier which describes this counter.
   * @param callable $callback
   *   Any PHP callable.
   */
  public function setWriteCallback($id, callable $callback);

}
