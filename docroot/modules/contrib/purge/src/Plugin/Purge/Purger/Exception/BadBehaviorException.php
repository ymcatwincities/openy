<?php

namespace Drupal\purge\Plugin\Purge\Purger\Exception;

/**
 * Thrown when APIs aren't being called as intended.
 *
 * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidate().
 * @see \Drupal\purge\Counter\CounterInterface::__construct().
 * @see \Drupal\purge\Counter\CounterInterface::set().
 * @see \Drupal\purge\Counter\CounterInterface::increment().
 * @see \Drupal\purge\Counter\CounterInterface::decrement().
 * @see \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::set().
 */
class BadBehaviorException extends \Exception {}
