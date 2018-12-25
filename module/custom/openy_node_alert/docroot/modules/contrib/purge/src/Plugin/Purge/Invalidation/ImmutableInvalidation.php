<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase;

/**
 * Provides the immutable invalidation object.
 *
 * Immutable invalidations are not used in real-life cache invalidation, as
 * \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface doesn't accept
 * them. However, as they are read-only, they are used by user interfaces to
 * see what is in the queue without actually claiming or changing it.
 */
class ImmutableInvalidation extends ImmutableInvalidationBase implements ImmutableInvalidationInterface {

  /**
   * The wrapped invalidation object.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
   */
  protected $invalidation;

  /**
   * Constructs the immutable invalidation object.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation
   *   The invalidation object describes what needs to be invalidated from the
   *   external caching system, and gets instantiated by the service
   *   'purge.invalidation.factory', either directly or through a queue claim.
   *
   * @return void
   */
  public function __construct(InvalidationInterface $invalidation) {
    $this->invalidation = $invalidation;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->invalidation->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getExpression() {
    return $this->invalidation->getExpression();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->invalidation->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->invalidation->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->invalidation->getState();
  }

  /**
   * {@inheritdoc}
   */
  public function getStateString() {
    return $this->invalidation->getStateString();
  }

}
