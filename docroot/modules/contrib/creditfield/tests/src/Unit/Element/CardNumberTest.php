<?php

/**
 * @file
 * Contains \Drupal\Tests\creditfield\Unit\Element\CardNumberTest.
 */

namespace Drupal\Tests\creditfield\Unit\Element;

use Drupal\creditfield\Element\CardNumber;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\creditfield\Element\CardNumber
 * @group creditfield
 */
class CardNumberTest extends UnitTestCase {

  /**
   * @covers ::numberIsValid
   * @dataProvider providerValidCardNumbers
   */
  public function testGoodNumberValidation($number) {
    return $this->assertTrue(CardNumber::numberIsValid($number), 'Number "' . $number . '" should have passed validation, but did not.');
  }

  /**
   * @covers ::numberIsValid
   * @dataProvider providerInvalidCardNumbers
   */
  public function testBadNumberValidation($number) {
    return $this->assertFalse(CardNumber::numberIsValid($number), 'Number "' . $number . '" should not have passed validation, but did.');
  }

  /**
   * Data provider of valid test numbers. Includes variants that should pass validation.
   * @return array
   */
  public function providerValidCardNumbers() {
    return array(
      array('4242424242424242'),
      array('4012888888881881'),
      array('4000056655665556'),
      array('5555555555554444'),
      array('5200828282828210'),
      array('5105105105105100'),
      array('378282246310005'),
      array('371449635398431'),
      array('6011111111111117'),
      array('6011000990139424'),
      array('30569309025904'),
      array('38520000023237'),
      array('3530111333300000'),
      array('3566002020360505')
    );
  }

  /**
   * Data provider of valid test numbers. Includes variants that should fail, like negative numbers, alphanumeric characters, values that are too short, or too long.
   * @return array
   */
  public function providerInvalidCardNumbers() {
    return array(
      array('424224242'),
      array('4012888888881881445353'),
      array('-4242424242424242'),
      array('40128888.10'),
      array('4242aBcD24244242'),
      array('ABCDEFGHIJKL'),
      array('1234828282828210'),
      array(''),
    );
  }
}