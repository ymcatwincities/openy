<?php

namespace Drupal\Tests\purge\Unit\Counter;

use Drupal\purge\Counter\Counter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\purge\Counter\Counter
 * @group purge
 */
class CounterTest extends UnitTestCase {

  /**
   * @covers ::disableDecrement
   * @expectedException \LogicException
   * @expectedExceptionMessage No ::decrement() permission on this object.
   */
  public function testDisableDecrement() {
    $counter = new Counter();
    $counter->disableDecrement();
    $counter->decrement();
  }

  /**
   * @covers ::disableIncrement
   * @expectedException \LogicException
   * @expectedExceptionMessage No ::increment() permission on this object.
   */
  public function testDisableIncrement() {
    $counter = new Counter();
    $counter->disableIncrement();
    $counter->increment();
  }

  /**
   * @covers ::disableSet
   * @expectedException \LogicException
   * @expectedExceptionMessage No ::set() permission on this object.
   */
  public function testDisableSet() {
    $counter = new Counter();
    $counter->disableSet();
    $counter->set(5);
  }

  /**
   * @covers ::get
   *
   * @dataProvider providerTestGet()
   */
  public function testGet($value) {
    $counter = new Counter($value);
    $this->assertEquals($value, $counter->get());
    $this->assertTrue(is_float($counter->get()));
    $this->assertFalse(is_int($counter->get()));
  }

  /**
   * Provides test data for testGet().
   */
  public function providerTestGet() {
    return [
      [0],
      [5],
      [1.3],
      [8.9],
    ];
  }

  /**
   * @covers ::getInteger
   *
   * @dataProvider providerTestGetInteger()
   */
  public function testGetInteger($value) {
    $counter = new Counter($value);
    $this->assertEquals((int) $value, $counter->getInteger());
    $this->assertFalse(is_float($counter->getInteger()));
    $this->assertTrue(is_int($counter->getInteger()));
  }

  /**
   * Provides test data for testGetInteger().
   */
  public function providerTestGetInteger() {
    return [
      [0],
      [5],
      [1.3],
      [8.9],
    ];
  }

  /**
   * @covers ::disableSet
   * @expectedException \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   * @expectedExceptionMessage Given $value is not a integer or float.
   * @dataProvider providerTestSetNotFloatOrInt()
   */
  public function testSetNotFloatOrInt($value) {
    $counter = new Counter();
    $counter->set($value);
  }

  /**
   * Provides test data for testSetNotFloatOrInt().
   */
  public function providerTestSetNotFloatOrInt() {
    return [
      [FALSE],
      ["0"],
      [NULL],
    ];
  }

  /**
   * @covers ::disableSet
   * @expectedException \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   * @expectedExceptionMessage Given $value can only be zero or positive.
   */
  public function testSetNegative() {
    $counter = new Counter();
    $counter->set(-0.000001);
  }

  /**
   * @covers ::set
   *
   * @dataProvider providerTestSet()
   */
  public function testSet($value) {
    $counter = new Counter();
    $counter->set($value);
    $this->assertEquals($value, $counter->get());
  }

  /**
   * Provides test data for testSet().
   */
  public function providerTestSet() {
    return [
      [0],
      [5],
      [1.3],
      [8.9],
    ];
  }

  /**
   * @covers ::decrement
   *
   * @dataProvider providerTestDecrement()
   */
  public function testDecrement($start, $subtract, $result) {
    $counter = new Counter($start);
    $counter->decrement($subtract);
    $this->assertEquals($result, $counter->get());
  }

  /**
   * Provides test data for testDecrement().
   */
  public function providerTestDecrement() {
    return [
      [4.0, 0.2, 3.8],
      [2, 1, 1],
      [1, 1, 0],
    ];
  }

  /**
   * @covers ::decrement
   * @expectedException \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   * @expectedExceptionMessage Given $amount is zero or negative.
   * @dataProvider providerTestDecrementInvalidValue()
   */
  public function testDecrementInvalidValue($value) {
    $counter = new Counter(10);
    $counter->decrement($value);
  }

  /**
   * Provides test data for testDecrementInvalidValue().
   */
  public function providerTestDecrementInvalidValue() {
    return [
      [0],
      [0.0],
      [-1],
    ];
  }

  /**
   * @covers ::decrement
   * @expectedException \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   * @expectedExceptionMessage Given $amount is not a integer or float.
   * @dataProvider providerTestDecrementNotFloatOrInt()
   */
  public function testDecrementNotFloatOrInt($value) {
    $counter = new Counter(10);
    $counter->decrement($value);
  }

  /**
   * Provides test data for testDecrementNotFloatOrInt().
   */
  public function providerTestDecrementNotFloatOrInt() {
    return [
      [FALSE],
      ["0"],
      [NULL],
    ];
  }

  /**
   * @covers ::increment
   *
   * @dataProvider providerTestIncrement()
   */
  public function testIncrement($start, $add, $result) {
    $counter = new Counter($start);
    $counter->increment($add);
    $this->assertEquals($result, $counter->get());
  }

  /**
   * Provides test data for testIncrement().
   */
  public function providerTestIncrement() {
    return [
      [4.0, 0.2, 4.2],
      [0.1, 1, 1.1],
      [2, 1, 3],
    ];
  }

  /**
   * @covers ::increment
   * @expectedException \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   * @expectedExceptionMessage Given $amount is zero or negative.
   * @dataProvider providerTestIncrementInvalidValue()
   */
  public function testIncrementInvalidValue($value) {
    $counter = new Counter(10);
    $counter->increment($value);
  }

  /**
   * Provides test data for testIncrementInvalidValue().
   */
  public function providerTestIncrementInvalidValue() {
    return [
      [0],
      [0.0],
      [-1],
    ];
  }

  /**
   * @covers ::increment
   * @expectedException \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   * @expectedExceptionMessage Given $amount is not a integer or float.
   * @dataProvider providerTestIncrementNotFloatOrInt()
   */
  public function testIncrementNotFloatOrInt($value) {
    $counter = new Counter(10);
    $counter->increment($value);
  }

  /**
   * Provides test data for testIncrementNotFloatOrInt().
   */
  public function providerTestIncrementNotFloatOrInt() {
    return [
      [FALSE],
      ["0"],
      [NULL],
    ];
  }

}
