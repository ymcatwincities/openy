<?php

/**
 * @file
 * Contains \Drupal\Tests\creditfield\Unit\Element\CardCodeTest.
 */

namespace Drupal\Tests\creditfield\Unit\Element;

use Drupal\creditfield\Element\CardCode;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\creditfield\Element\CardCode
 * @group creditfield
 */
class CardCodeTest extends UnitTestCase {

  /**
   * @covers ::numberIsValid
   * @dataProvider providerValidCardCodeNumbers
   */
  public function testGoodCodeValidation($number) {
    return $this->assertTrue(CardCode::numberIsValid($number), 'Number code "' . $number . '" should have passed validation, but did not.');
  }

  /**
   * @covers ::numberIsValid
   * @dataProvider providerInvalidCardCodeNumbers
   */
  public function testBadCodeValidation($number) {
    return $this->assertFalse(CardCode::numberIsValid($number), 'Number code "' . $number . '" should not have passed validation, but did.');
  }

  /**
   * Data provider of valid test codes. Includes variants that should pass validation.
   * @return array
   */
  public function providerValidCardCodeNumbers() {
    return array(
      array('012'),
      array('123'),
      array('555'),
      array('0123'),
      array('1234'),
    );
  }

  /**
   * Data provider of valid test codes. Includes variants that should fail, like negative numbers, alphanumeric characters, values that are too short, or too long.
   * @return array
   */
  public function providerInvalidCardCodeNumbers() {
    return array(
      array('1.1'),
      array('4af'),
      array('8724372'),
      array('3'),
      array('-134'),
      array(''),
    );
  }
}