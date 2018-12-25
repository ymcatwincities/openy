<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\purge\ServiceInterface;

/**
 * Describes a service that instantiates invalidation objects on-demand.
 */
interface InvalidationsServiceInterface extends ServiceInterface {

  /**
   * Create a new invalidation object of the given type.
   *
   * @param string $plugin_id
   *   The id of the invalidation type being instantiated.
   * @param mixed|null $expression
   *   Value - usually string - that describes the kind of invalidation, NULL
   *   when the type of invalidation doesn't require $expression. Types usually
   *   validate the given expression and throw exceptions for bad input.
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of invalidation.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
   */
  public function get($plugin_id, $expression = NULL);

  /**
   * Create a new immutable invalidation object of the given type.
   *
   * @param string $plugin_id
   *   The id of the invalidation type being instantiated.
   * @param mixed|null $expression
   *   Value - usually string - that describes the kind of invalidation, NULL
   *   when the type of invalidation doesn't require $expression. Types usually
   *   validate the given expression and throw exceptions for bad input.
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of invalidation.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface
   */
  public function getImmutable($plugin_id, $expression = NULL);

  /**
   * Replicate a invalidation object from serialized queue item data.
   *
   * @param string $item_data
   *   Arbitrary PHP data structured that was stored into the queue.
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of invalidation.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
   */
  public function getFromQueueData($item_data);

  /**
   * Replicate a immutable invalidation object from serialized queue item data.
   *
   * @param string $item_data
   *   Arbitrary PHP data structured that was stored into the queue.
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of invalidation.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface
   */
  public function getImmutableFromQueueData($item_data);

}
