<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * Describes the transaction buffer.
 *
 * \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface derivatives use the
 * transaction buffer as instance registry for invalidation objects. References
 * are kept to invalidations and the buffer allows setting properties and queue
 * specific states. The held object references can be retrieved and deleted.
 *
 * What the buffer allows the queue service to do is to reduce actual calls on
 * the underlying queue backend and by doing so, being a lot more efficient.
 */
interface TxBufferInterface extends \Countable, \Iterator {

  /**
   * Freshly claimed objects.
   */
  const CLAIMED = 0;

  /**
   * Objects in the process of being added to the queue.
   */
  const ADDING = 1;

  /**
   * Objects that just got added to the queue.
   */
  const ADDED = 2;

  /**
   * Objects in the process of being released back to the queue.
   */
  const RELEASING = 3;

  /**
   * Objects that just got released back to the queue.
   */
  const RELEASED = 4;

  /**
   * Objects in the process of being deleted from the queue.
   */
  const DELETING = 5;

  /**
   * Delete the given invalidation object from the buffer.
   *
   * @param array|\Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidations
   *   Invalidation object or array with objects.
   *
   * @return void
   */
  public function delete($invalidations);

  /**
   * Delete everything in the buffer.
   *
   * @return void
   */
  public function deleteEverything();

  /**
   * Retrieve a buffered object by property=value combination.
   *
   * @param string $property
   *   The name of the property you want to look for.
   * @param mixed $value
   *   The (unique) value of the property that has to be stored in the buffer
   *   in order to return the object.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface|false
   *   The matched invalidation object or FALSE when there was no combination
   *   found of the property and value.
   */
  public function getByProperty($property, $value);

  /**
   * Only retrieve items from the buffer in a particular given state(s).
   *
   * @param int|array $states
   *   Individual state or array with one of the following states:
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::CLAIMED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::DELETING
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[]
   */
  public function getFiltered($states);

  /**
   * Request the in-buffer set state for the given invalidation object.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation
   *   Invalidation object.
   *
   * @return int|null
   *   The state of the given object or NULL when not found.
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::CLAIMED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::DELETING
   */
  public function getState(InvalidationInterface $invalidation);

  /**
   * Retrieve a stored property for the given invalidation object.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation
   *   Invalidation object.
   * @param string $property
   *   The string key of the stored property you want to receive.
   * @param mixed $default
   *   The return value for when the property is not found.
   *
   * @return mixed|null
   *   The stored property value or the value of the $default argument.
   */
  public function getProperty(InvalidationInterface $invalidation, $property, $default = NULL);

  /**
   * Check if the given object is already in buffer our not.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation
   *   Invalidation object.
   *
   * @return TRUE|FALSE
   */
  public function has(InvalidationInterface $invalidation);

  /**
   * Set the given state on one or multiple invalidation objects.
   *
   * @param array|\Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidations
   *   Invalidation object or array with objects.
   * @param int $state
   *   One of the following states:
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::CLAIMED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::ADDED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASING
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::RELEASED
   *     - \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface::DELETING
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $invalidations contains other data than derivatives of
   *   \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface.
   *
   * @return void
   */
  public function set($invalidations, $state);

  /**
   * Store a named property for the given invalidation object.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation
   *   Invalidation object.
   * @param string $property
   *   The string key of the property you want to store.
   * @param mixed $value
   *   The value of the property you want to set.
   *
   * @return void
   */
  public function setProperty(InvalidationInterface $invalidation, $property, $value);

}
