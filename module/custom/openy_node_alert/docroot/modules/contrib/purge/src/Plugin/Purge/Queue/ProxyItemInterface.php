<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Queue\TxBufferInterface;

/**
 * Describes a proxy item.
 *
 * Queue proxy objects act as middle man between our high level invalidation
 * objects and the lower level \Drupal\Core\Queue\QueueInterface API. As Purge
 * queue plugins extend core's API, these are also unaware of invalidation
 * objects and therefore proxy objects play a key translation role. A proxy has
 * the properties 'data', 'item_id' and 'created', of which the data is stored
 * in the given \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface derivative.
 */
interface ProxyItemInterface {

  /**
   * The array index in the data property where the invalidation type is stored.
   *
   * @var int
   */
  const DATA_INDEX_TYPE = 0;

  /**
   * The array index in the data property that holds the states.
   *
   * @var int
   */
  const DATA_INDEX_STATES = 1;

  /**
   * The array index in the data property that holds the expression.
   *
   * @var int
   */
  const DATA_INDEX_EXPRESSION = 2;

  /**
   * The array index in the data property that holds the purger properties.
   *
   * @var int
   */
  const DATA_INDEX_PROPERTIES = 3;

  /**
   * Constructs a proxy item object.
   *
   * @see \Drupal\Core\Queue\QueueInterface::createItem
   * @see \Drupal\Core\Queue\QueueInterface::claimItem
   * @see \Drupal\Core\Queue\QueueInterface::deleteItem
   * @see \Drupal\Core\Queue\QueueInterface::releaseItem
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation
   *   Invalidation object being wrapped in a proxy.
   * @param \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface $buffer
   *   The actively used TxBuffer object by \Drupal\purge\Plugin\Purge\Queue\QueueService.
   */
  public function __construct(InvalidationInterface $invalidation, TxBufferInterface $buffer);

  /**
   * Retrieve a property.
   *
   * @param string $name
   *   The name of the property, can be 'item_id', 'created' or 'data'.
   *
   * @throws \Drupal\purge\Plugin\Purge\Queue\Exception\InvalidPropertyException
   *   Thrown when the requested property isn't 'item_id', 'created' or 'data'.
   *
   * @see http://php.net/manual/en/language.oop5.overloading.php#object.get
   *
   * @return mixed
   */
  public function __get($name);

  /**
   * Set a writable property.
   *
   * @param string $name
   *   The name of the property, can be 'item_id' or 'created'.
   * @param mixed $value
   *   The value of the property you want to set.
   *
   * @throws \Drupal\purge\Plugin\Purge\Queue\Exception\InvalidPropertyException
   *   Thrown when the requested property isn't 'item_id' or 'created'.
   *
   * @see http://php.net/manual/en/language.oop5.overloading.php#object.set
   *
   * @return void
   */
  public function __set($name, $value);

}
