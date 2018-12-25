<?php

/**
 * @file Contains Drupal\Test\jquery_colorpicker\Service\JQueryColorpickerServiceTest
 */

namespace Drupal\Test\jquery_colorpicker\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\jquery_colorpicker\Service\JQueryColorpickerService;

/**
 * @coversDefaultClass \Drupal\jquery_colorpicker\Service\JQueryColorpickerService
 * @group jquery_colorpicker
 */
class JQueryColorpickerServiceTest extends UnitTestCase
{
	use StringTranslationTrait;

	/**
	 * The JQuery Colorpicker Service
	 *
	 * @var \Drupal\jquery_colorpicker\Service\JQueryColorpickerService
	 */
	protected $JQueryColorpickerService;

	public function setUp()
	{
		$container = new ContainerBuilder();
		$container->set('string_translation', $this->getStringTranslationStub());
		\Drupal::setContainer($container);

		$this->JQueryColorpickerService = new JQueryColorpickerService();
	}

	/**
	 * @covers ::formatColor
	 *
	 * @dataProvider providerTestFormatColor
	 */
	public function testFormatColor($expected, $color)
	{
		$this->assertSame($expected, $this->JQueryColorpickerService->formatColor($color));
	}

	public function providerTestFormatcolor()
	{
		$data = [];
		$data[] = ['', NULL];
		$data[] = ['', ''];
		$data[] = ["1", TRUE];
		$data[] = ["", FALSE];
		$data[] = ["123", 123];
		$data[] = ["1.23", 1.23];
		$data[] = ["123456", "123456"];
		$data[] = ["123456", "#123456"];
		$data[] = ["", []];
		$test_class = new \stdClass;
		$data[] = ["", $test_class];

		return $data;
	}

	/**
	 * @covers ::validateColor
	 *
	 * @dataProvider providerTestValidateColor
	 */
	public function testValidateColor($expected, $color)
	{
		$this->assertEquals($expected, $this->JQueryColorpickerService->validateColor($color));
	}

	public function providerTestValidateColor()
	{
		$container = new ContainerBuilder();
		$container->set('string_translation', $this->getStringTranslationStub());
		\Drupal::setContainer($container);

		$type_error = $this->t('Color must be a string or an integer');
		$length_error = $this->t('Color values must be exactly six characters in length');
		$hex_error = $this->t("You entered an invalid value for the color. Colors must be hexadecimal, and can only contain the characters '0-9', 'a-f' and/or 'A-F'.");

		$data = [];
		$data[] = [$type_error, TRUE];
		$data[] = [$type_error, FALSE];
		$data[] = [$type_error, []];
		$test = new \stdClass;
		$data[] = [$type_error, $test];
		$data[] = [$type_error, 1.23];
		$data[] = [$length_error, 12345];
		$data[] = [$length_error, "12345"];
		$data[] = [$hex_error, "11111g"];
		$data[] = [$hex_error, "11111G"];
		$data[] = [$hex_error, "fffffg"];
		$data[] = [$hex_error, "fffffG"];
		$data[] = [$hex_error, "FFFFFg"];
		$data[] = [$hex_error, "FFFFFG"];

		// Valid submissions
		$data[] = [FALSE, 123456];
		$data[] = [FALSE, "123456"];
		$data[] = [FALSE, "11111f"];
		$data[] = [FALSE, "11111F"];
		$data[] = [FALSE, "FFFFF1"];
		$data[] = [FALSE, "fffff1"];

		return $data;
	}
}
