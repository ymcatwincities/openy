<?php

namespace Drupal\purge\Counter;

use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Counter\CounterInterface;

/**
 * Provides a numeric counter.
 */
class Counter implements CounterInterface {

  /**
   * Whether it is possible to call ::decrement() or not.
   *
   * @var bool
   */
  protected $permission_decrement = TRUE;

  /**
   * Whether it is possible to call ::increment() or not.
   *
   * @var bool
   */
  protected $permission_increment = TRUE;

  /**
   * Whether it is possible to call ::set() or not.
   *
   * @var bool
   */
  protected $permission_set = TRUE;

  /**
   * The value of the counter.
   *
   * @var float
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function __construct($value = 0.0) {
    $this->set($value);
  }

  /**
   * {@inheritdoc}
   */
  public function disableDecrement() {
    $this->permission_decrement = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function disableIncrement() {
    $this->permission_increment = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function disableSet() {
    $this->permission_set = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getInteger() {
    return (int) $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function set($value) {
    if (!$this->permission_set) {
      throw new \LogicException('No ::set() permission on this object.');
    }
    $this->setDirectly($value);
  }

  /**
   * Overwrite the counter value (permission bypass).
   *
   * @param int|float $value
   *   The new value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $value is not a integer, float or when it is negative.
   * @throws \LogicException
   *   Thrown when the object got created without set permission.
   */
  protected function setDirectly($value) {
    if (!(is_float($value) || is_int($value))) {
      throw new BadBehaviorException('Given $value is not a integer or float.');
    }
    if (is_int($value)) {
      $value = (float) $value;
    }
    if ($value < 0.0) {
      throw new BadBehaviorException('Given $value can only be zero or positive.');
    }
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function decrement($amount = 1.0) {
    if (!$this->permission_decrement) {
      throw new \LogicException('No ::decrement() permission on this object.');
    }
    if (!(is_float($amount) || is_int($amount))) {
      throw new BadBehaviorException('Given $amount is not a integer or float.');
    }
    if (is_int($amount)) {
      $amount = (float) $amount;
    }
    if (!($amount > 0.0)) {
      throw new BadBehaviorException('Given $amount is zero or negative.');
    }
    $new = $this->value - $amount;
    if ($new < 0.0) {
      $new = 0.0;
    }
    $this->setDirectly($new);
  }

  /**
   * {@inheritdoc}
   */
  public function increment($amount = 1.0) {
    if (!$this->permission_increment) {
      throw new \LogicException('No ::increment() permission on this object.');
    }
    if (!(is_float($amount) || is_int($amount))) {
      throw new BadBehaviorException('Given $amount is not a integer or float.');
    }
    if (is_int($amount)) {
      $amount = (float) $amount;
    }
    if (!($amount > 0.0)) {
      throw new BadBehaviorException('Given $amount is zero or negative.');
    }
    $this->setDirectly($this->value + $amount);
  }

}
