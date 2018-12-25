<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface;

/**
 * Desribes the invalidation object.
 *
 * Invalidations are small value objects that describe and track invalidations
 * on one or more external caching systems within the Purge pipeline. These
 * objects can be directly instantiated from InvalidationsService and float
 * freely between the QueueService and the PurgersService.
 */
interface InvalidationInterface extends ImmutableInvalidationInterface, ContainerFactoryPluginInterface {

  /**
   * Delete a purger specific property.
   *
   * Once ::setStateContext() has been called, purgers can call ::setProperty()
   * and ::getProperty() to store specific metadata on the invalidation. The
   * most common usecase for setting properties is for multi-step cache
   * invalidation, for instance CDNs returning IDs to check against later.
   *
   * @param string $key
   *   The key of the stored property, unique to the current purger context.
   *
   * @throws \LogicException
   *   Thrown when operating in general context, call ::setStateContext() first.
   *
   * @return void
   */
  public function deleteProperty($key);

  /**
   * Get the instance ID.
   *
   * @return int
   *   Unique integer ID for this object instance (during runtime).
   */
  public function getId();

  /**
   * Set a purger specific property.
   *
   * Once ::setStateContext() has been called, purgers can call ::setProperty()
   * and ::getProperty() to store specific metadata on the invalidation. The
   * most common usecase for setting properties is for multi-step cache
   * invalidation, for instance CDNs returning IDs to check against later.
   *
   * @param string $key
   *   The key of the property to set, unique to the current purger context.
   * @param mixed $value
   *   The value of the property.
   *
   * @throws \LogicException
   *   Thrown when operating in general context, call ::setStateContext() first.
   *
   * @return void
   */
  public function setProperty($key, $value);

  /**
   * Set the state of the invalidation.
   *
   * Setting state on invalidation objects is the responsibility of purgers, as
   * only purgers decide what succeeded and what failed. For this reason a call
   * to ::setStateContext() before the state is set, is obligatory.
   *
   * @param int $state
   *   One of the following states:
   *   - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::SUCCEEDED
   *   - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::FAILED
   *   - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::PROCESSING
   *   - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::NOT_SUPPORTED
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException
   *   Thrown when the $state parameter doesn't match any of the constants
   *   defined in \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface.
   * @throws \LogicException
   *   Thrown when the state is being set in general context.
   *
   * @return void
   */
  public function setState($state);

  /**
   * Set (or reset) state context to the purger instance next in line.
   *
   * New, freshly claimed invalidations and those exiting the PurgersService
   * always have NULL as their state context. This means that when called,
   * ::getState() resolves the general state by triaging all stored states. So
   * for example: when no states are known, it will evaluate to FRESH but when
   * one state is set to SUCCEEDED and a few others to FAILED, the general state
   * becomes FAILED. When only SUCCEEDED's is stored, it will evaluate as such.
   *
   * However, the behaviors of ::getState() and ::setState() change after a call
   * to ::setStateContext(). From this point on, both will respectively retrieve
   * and store the state *specific* to that purger context. Context switching is
   * handled by PurgersServiceInterface::invalidate() and therefore no
   * understanding of this concept is required outside the purgers service code.
   *
   * @param string|null $purger_instance_id
   *   The instance ID of the purger that is about to process the object, or
   *   NULL when no longer any purgers are processing it. NULL is the default.
   *
   * @throws \LogicException
   *   Thrown when the given parameter is empty, not a string or NULL.
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException
   *   Thrown when the last set state was not any of:
   *   - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::SUCCEEDED
   *   - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::FAILED
   *   - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::PROCESSING
   *   - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::NOT_SUPPORTED
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState()
   * @see \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface::getState()
   *
   * @return void
   */
  public function setStateContext($purger_instance_id);

  /**
   * Validate the expression given to the invalidation during instantiation.
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of invalidation.
   *
   * @see \Drupal\purge\Annotation\PurgeInvalidation::$expression_required
   * @see \Drupal\purge\Annotation\PurgeInvalidation::$expression_can_be_empty
   *
   * @return void
   */
  public function validateExpression();

}
