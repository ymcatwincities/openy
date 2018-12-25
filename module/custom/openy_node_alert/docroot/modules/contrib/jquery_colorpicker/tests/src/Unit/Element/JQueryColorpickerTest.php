<?php

/**
 * @file Contains Drupal\Test\jquery_colorpicker\Element\JQueryColorpickerTest
 */

namespace Drupal\Test\jquery_colorpicker\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\jquery_colorpicker\Element\JQueryColorpicker;

/**
 * @coversDefaultClass \Drupal\jquery_colorpicker\Element\JQueryColorpicker
 * @group jquery_colorpicker
 */
class JQueryColorpickerTest extends UnitTestCase
{
	/**
	 * @covers ::valueCallback
	 *
	 * @dataProvider providerTestValueCallback
	 */
	public function testValueCallback($expected, $input)
	{
		$element = [];
		$form_state = $this->prophesize(FormStateInterface::class)->reveal();
		$this->assertSame($expected, JQueryColorpicker::valueCallback($element, $input, $form_state));
	}

	/**
	 * Data provider for testValueCallback()
	 */
	public function providerTestValueCallback()
	{
		$data = [];

		$data[] = [NULL, FALSE];
		$data[] = [NULL, NULL];
		$data[] = ['', ['test']];
		$test = new \stdClass;
		$test->value = 'test';
		$data[] = ['', $test];
		$data[] = ["123", 123];
		$data[] = ["1.23", 1.23];
		$data[] = ["123", "123"];
		$data[] = ["1", TRUE];

		return $data;
	}

	/**
	 * @covers ::valueCallback
	 */
	public function testValidateElementEmpty()
	{
		$element = ['#value' => ''];
		$form_state = $this->prophesize(FormStateInterface::class)->reveal();
		$this->assertSame(NULL, JQueryColorpicker::validateElement($element, $form_state));
	}
}
