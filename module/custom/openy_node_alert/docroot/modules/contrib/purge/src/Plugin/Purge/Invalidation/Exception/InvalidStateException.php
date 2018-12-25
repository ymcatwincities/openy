<?php

namespace Drupal\purge\Plugin\Purge\Invalidation\Exception;

/**
 * Thrown when the incoming or outgoing object states are not valid.
 *
 * InvalidStateException gets thrown in the following circumstances:
 *
 * 1) in \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
 *    when the $state parameter doesn't match any of the constants defined in
 *    \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface.
 *
 * 2) When a \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 *    object gets fed to the purger service that isn't a valid condition.
 *
 * 2) When a purger plugin doesn't set a valid state after processing the
 *    \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface object.
 *
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
 */
class InvalidStateException extends \Exception {}
