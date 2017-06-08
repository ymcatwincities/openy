<?php

namespace Drupal\Tests\plugin\Unit\PluginDefinition;

use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginDefinitionValidator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDefinition\PluginDefinitionValidator
 *
 * @group Plugin
 */
class PluginDefinitionValidatorTest extends UnitTestCase {

  /**
   * @covers ::validateClass
   *
   * @dataProvider providerValidateClass
   *
   * @param bool $valid
   *   Whether or not the class is valid.
   * @param string $class
   *   The class to validate.
   */
  public function testValidateClass($valid, $class) {
    if (!$valid) {
      $this->setExpectedException('\InvalidArgumentException');
    }
    $this->assertNull(PluginDefinitionValidator::validateClass($class));
  }

  /**
   * Provides data to self::testValidateClass().
   */
  public function providerValidateClass() {
    return [
      [TRUE, '\stdClass'],
      [TRUE, __CLASS__],
      [FALSE, NULL],
      [FALSE, $this->randomMachineName()],
      [FALSE, '\Foo\Bar\Baz\Qux'],
    ];
  }

  /**
   * @covers ::validateDeriverClass
   * @covers ::validateClass
   *
   * @dataProvider providerValidateDeriverClass
   *
   * @param bool $valid
   *   Whether or not the class is valid.
   * @param string $class
   *   The class to validate.
   */
  public function testValidateDeriverClass($valid, $class) {
    if (!$valid) {
      $this->setExpectedException('\InvalidArgumentException');
    }
    $this->assertNull(PluginDefinitionValidator::validateDeriverClass($class));
  }

  /**
   * Provides data to self::testValidateDeriverClass().
   */
  public function providerValidateDeriverClass() {
    return [
      [TRUE, $this->getMockClass(DeriverInterface::class)],
      [FALSE, NULL],
      [FALSE, '\stdClass'],
      [FALSE, $this->randomMachineName()],
      [FALSE, '\Foo\Bar\Baz\Qux'],
    ];
  }

  /**
   * @covers ::validateContextDefinitions
   *
   * @dataProvider providerValidateContextDefinitions
   *
   * @param bool $valid
   *   Whether or not the class is valid.
   * @param mixed[] $definitions
   *   The context definitions to validate.
   */
  public function testValidateContextDefinitions($valid, array $definitions) {
    if (!$valid) {
      $this->setExpectedException('\InvalidArgumentException');
    }
    $this->assertNull(PluginDefinitionValidator::validateContextDefinitions($definitions));
  }

  /**
   * Provides data to self::testValidateContextDefinitions().
   */
  public function providerValidateContextDefinitions() {
    return [
      [TRUE, []],
      [TRUE, [$this->getMock(ContextDefinitionInterface::class)]],
      [FALSE, [$this->getMockClass(ContextDefinitionInterface::class)]],
      [FALSE, [$this->randomMachineName()]],
      [FALSE, [ContextDefinitionInterface::class]],
    ];
  }

}
