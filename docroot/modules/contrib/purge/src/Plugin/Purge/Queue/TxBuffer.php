<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Queue\TxBufferInterface;

/**
 * Provides the transaction buffer.
 */
class TxBuffer implements TxBufferInterface {

  /**
   * Instances listing holding copies of each Invalidation object.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[]
   */
  private $instances = [];

  /**
   * Instance<->state map of each object in the buffer.
   *
   * @var int[int]
   */
  private $states = [];

  /**
   * Instance<->property map of each object in the buffer.
   *
   * @var array[int]
   */
  private $properties = [];

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/countable.count.php
   */
  public function count() {
    return count($this->instances);
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/class.iterator.php
   */
  public function current() {
    return current($this->instances);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($invalidations) {
    if (!is_array($invalidations)) {
      $invalidations = [$invalidations];
    }
    foreach ($invalidations as $i) {
      unset($this->instances[$i->getId()]);
      unset($this->states[$i->getId()]);
      unset($this->properties[$i->getId()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEverything() {
    $this->instances = [];
    $this->states = [];
    $this->properties = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getByProperty($property, $value) {
    foreach ($this->properties as $id => $properties) {
      if (isset($properties[$property])) {
        if ($properties[$property] === $value) {
          return $this->instances[$id];
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFiltered($states) {
    if (!is_array($states)) {
      $states = [$states];
    }
    $results = [];
    foreach ($this->states as $id => $state) {
      if (in_array($state, $states)) {
        $results[] = $this->instances[$id];
      }
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getState(InvalidationInterface $invalidation) {
    if (!$this->has($invalidation)) {
      return NULL;
    }
    return $this->states[$invalidation->getId()];
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty(InvalidationInterface $invalidation, $property, $default = NULL) {
    if (!isset($this->properties[$invalidation->getId()][$property])) {
      return $default;
    }
    return $this->properties[$invalidation->getId()][$property];
  }

  /**
   * {@inheritdoc}
   */
  public function has(InvalidationInterface $invalidation) {
    return isset($this->instances[$invalidation->getId()]);
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/iterator.key.php
   */
  public function key() {
    return key($this->instances);
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/iterator.next.php
   */
  public function next() {
    return next($this->instances);
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/iterator.rewind.php
   */
  public function rewind() {
    return reset($this->instances);
  }

  /**
   * {@inheritdoc}
   */
  public function set($invalidations, $state) {
    if (!is_array($invalidations)) {
      $invalidations = [$invalidations];
    }
    foreach ($invalidations as $i) {
      if (!($i instanceof InvalidationInterface)) {
        throw new BadBehaviorException("Item is not a \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface derivative.");
      }
      if (!$this->has($i)) {
        $this->instances[$i->getId()] = $i;
      }
      $this->states[$i->getId()] = $state;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setProperty(InvalidationInterface $invalidation, $property, $value) {
    if ($this->has($invalidation)) {
      $this->properties[$invalidation->getId()][$property] = $value;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/manual/en/iterator.valid.php
   */
  public function valid() {
    return is_null(key($this->instances)) ? FALSE : TRUE;
  }

}
