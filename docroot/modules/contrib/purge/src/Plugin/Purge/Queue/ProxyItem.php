<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Queue\Exception\InvalidPropertyException;
use Drupal\purge\Plugin\Purge\Queue\ProxyItemInterface;
use Drupal\purge\Plugin\Purge\Queue\TxBufferInterface;

/**
 * Provides a proxy item.
 *
 * Queue proxy objects act as middle man between our high level invalidation
 * objects and the lower level \Drupal\Core\Queue\QueueInterface API. As Purge
 * queue plugins extend core's API, these are also unaware of invalidation
 * objects and therefore proxy objects play a key translation role. A proxy has
 * the properties 'data', 'item_id' and 'created', of which the data is stored
 * in the given \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface derivative.
 */
class ProxyItem implements ProxyItemInterface {

  /**
   * The proxied invalidation object.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
   */
  protected $invalidation;

  /**
   * The actively used TxBuffer object by \Drupal\purge\Plugin\Purge\Queue\QueueService.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface
   */
  protected $buffer;

  /**
   * Describes the accessible properties and if they're RO (FALSE) or RW (TRUE).
   *
   * @var bool[string]
   */
  protected $properties = [
    'item_id' => TRUE,
    'created' => TRUE,
    'data' => FALSE,
  ];

  /**
   * The unique ID from \Drupal\Core\Queue\QueueInterface::createItem().
   *
   * @var mixed|null
   * @see \Drupal\Core\Queue\QueueInterface::createItem
   * @see \Drupal\Core\Queue\QueueInterface::claimItem
   */
  private $item_id;

  /**
   * Purge specific data to be associated with the new task in the queue.
   *
   * @var mixed
   * @see \Drupal\Core\Queue\QueueInterface::createItem
   * @see \Drupal\Core\Queue\QueueInterface::claimItem
   */
  private $data;

  /**
   * Timestamp when the item was put into the queue.
   *
   * @var mixed|null
   * @see \Drupal\Core\Queue\QueueInterface::createItem
   * @see \Drupal\Core\Queue\QueueInterface::claimItem
   */
  private $created;

  /**
   * {@inheritdoc}
   */
  public function __construct(InvalidationInterface $invalidation, TxBufferInterface $buffer) {
    $this->invalidation = $invalidation;
    $this->buffer = $buffer;
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    if (!isset($this->properties[$name])) {
      throw new InvalidPropertyException("The property '$name' does not exist.");
    }

    // The 'data' property describes the purge queue item in such a way that
    // \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface is able to recreate it.
    if ($name === 'data') {
      return [
        SELF::DATA_INDEX_TYPE => $this->invalidation->getType(),
        SELF::DATA_INDEX_STATES => $this->invalidation->getStates(),
        SELF::DATA_INDEX_EXPRESSION => $this->invalidation->getExpression(),
        SELF::DATA_INDEX_PROPERTIES => $this->invalidation->getProperties(),
      ];
    }

    // Else look up the properties using the buffer's property store.
    else {
      return $this->buffer->getProperty($this->invalidation, $name, NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    if (!isset($this->properties[$name])) {
      throw new InvalidPropertyException("The property '$name' does not exist.");
    }
    if (!$this->properties[$name]) {
      throw new InvalidPropertyException("The property '$name' is read-only.");
    }
    $this->buffer->setProperty($this->invalidation, $name, $value);
  }

}
