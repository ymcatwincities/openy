<?php

namespace Drupal\purge\Plugin\Purge\Purger\Exception;

/**
 * Thrown when purgers are not implemented as outlined in the documentation.
 *
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setStateContext().
 * @see \Drupal\purge\Plugin\Purge\Purger\CapacityTrackerInterface::getTimeHint().
 */
class BadPluginBehaviorException extends \Exception {}
