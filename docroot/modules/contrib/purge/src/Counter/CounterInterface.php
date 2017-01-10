<?php

namespace Drupal\purge\Counter;

/**
 * Describes a numeric counter.
 */
interface CounterInterface {

  /**
   * Construct a counter object.
   *
   * @param int|float $value
   *   The initial positive number the counter starts its life with.
   */
  public function __construct($value = 0.0);

  /**
   * Disable the possibility to decrement the counter.
   *
   * @warning
   *   This works self-destructive! Once called, it cannot be enabled again.
   */
  public function disableDecrement();

  /**
   * Disable the possibility to increment the counter.
   *
   * @warning
   *   This works self-destructive! Once called, it cannot be enabled again.
   */
  public function disableIncrement();

  /**
   * Disable the possibility of setting counter.
   *
   * @warning
   *   This works self-destructive! Once called, it cannot be enabled again.
   */
  public function disableSet();

  /**
   * Get the current value.
   *
   * @return float
   *   The numeric value of the counter.
   */
  public function get();

  /**
   * Get the current value as integer.
   *
   * @return int
   *   The numeric value of the counter, typecasted as int.
   */
  public function getInteger();

  /**
   * Overwrite the counter value.
   *
   * @param int|float $value
   *   The new value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $value is not a integer, float or when it is negative.
   * @throws \LogicException
   *   Thrown when the object got created without set permission.
   */
  public function set($value);

  /**
   * Decrease the counter.
   *
   * @param int|float $amount
   *   Numeric amount to subtract from the current counter value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $amount is not a float, integer or when it is zero/negative.
   * @throws \LogicException
   *   Thrown when the object got created without decrement permission.
   */
  public function decrement($amount = 1.0);

  /**
   * Increase the counter.
   *
   * @param int|float $amount
   *   Numeric amount to add up to the current counter value.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $amount is not a float, integer, when it is zero/negative.
   * @throws \LogicException
   *   Thrown when the object got created without increment permission.
   */
  public function increment($amount = 1.0);

}
