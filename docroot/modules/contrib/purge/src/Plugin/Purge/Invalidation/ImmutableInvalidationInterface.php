<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface;

/**
 * Describes the immutable invalidation.
 *
 * Immutable invalidations are not used in real-life cache invalidation, as
 * \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface doesn't accept
 * them. However, as they are read-only, they are used by user interfaces to
 * see what is in the queue without actually claiming or changing it.
 */
interface ImmutableInvalidationInterface extends InvStatesInterface, PluginInspectionInterface {

  /**
   * Return the string expression of the invalidation.
   *
   * @return string
   *   Returns the string serialization, e.g. "node/1".
   */
  public function __toString();

  /**
   * Get the invalidation expression.
   *
   * @return mixed|null
   *   Mixed expression (or NULL) that describes what needs to be invalidated.
   */
  public function getExpression();

  /**
   * Get all stored properties.
   *
   * @throws \LogicException
   *   Thrown when NOT operating in general context.
   *
   * @return array[]
   *   Associative array in which the keys point to purger instances and where
   *   each value represents a associative array with key-value stored metadata.
   */
  public function getProperties();

  /**
   * Retrieve a purger specific property value.
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
   * @return null|mixed
   *   The property value or NULL when no such property exists.
   */
  public function getProperty($key);

  /**
   * Get the current or general state of the invalidation.
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
   * @throws \LogicException
   *   Thrown state are stored that should not have been stored, as is not
   *   never supposed to happen catching this exception is not recommended.
   *
   * @return int
   *   Any \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface constant.
   */
  public function getState();

  /**
   * Get all invalidation states.
   *
   * @return int[]
   *   Associative list of which the keys refer to purger instances and the
   *   values from \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface.
   */
  public function getStates();

  /**
   * Get the current state as string.
   *
   * @return string
   *   A capitalized string exactly matching the names of the constants in
   *   \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface.
   */
  public function getStateString();

  /**
   * Get the current state as user translated string.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated string describing the current state of the invalidation.
   */
  public function getStateStringTranslated();

  /**
   * Get the type of invalidation.
   *
   * @return string
   *   The plugin ID of the invalidation.
   */
  public function getType();
}
