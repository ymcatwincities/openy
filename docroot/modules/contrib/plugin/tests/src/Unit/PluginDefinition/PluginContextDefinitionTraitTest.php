<?php

namespace Drupal\Tests\plugin\Unit\PluginDefinition;

use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginContextDefinitionTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDefinition\PluginContextDefinitionTrait
 *
 * @group Plugin
 */
class PluginContextDefinitionTraitTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDefinition\PluginContextDefinitionTrait
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->sut = $this->getMockForTrait(PluginContextDefinitionTrait::class);
  }

  /**
   * @covers ::setContextDefinitions
   * @covers ::getContextDefinitions
   */
  public function testGetContextDefinitions() {
    $context_definition_name_a = $this->randomMachineName();
    $context_definition_a = $this->getMock(ContextDefinitionInterface::class);
    $context_definition_name_b = $this->randomMachineName();
    $context_definition_b = $this->getMock(ContextDefinitionInterface::class);

    $context_definitions = [
      $context_definition_name_a => $context_definition_a,
      $context_definition_name_b => $context_definition_b,
    ];

    $this->assertSame($this->sut, $this->sut->setContextDefinitions($context_definitions));
    $this->assertSame($context_definitions, $this->sut->getContextDefinitions());
  }

  /**
   * @covers ::setContextDefinitions
   *
   * @depends testGetContextDefinitions
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetContextDefinitionsWithInvalidDefinition() {
    $context_definitions = [
      $this->randomMachineName() => new \stdClass(),
    ];

    $this->sut->setContextDefinitions($context_definitions);
  }

  /**
   * @covers ::setContextDefinition
   * @covers ::getContextDefinition
   * @covers ::hasContextDefinition
   */
  public function testGetContextDefinition() {
    $name = $this->randomMachineName();
    $context_definition = $this->getMock(ContextDefinitionInterface::class);

    $this->assertSame($this->sut, $this->sut->setContextDefinition($name, $context_definition));
    $this->assertSame($context_definition, $this->sut->getContextDefinition($name));
    $this->assertTrue($this->sut->hasContextDefinition($name));
  }

  /**
   * @covers ::getContextDefinition
   * @covers ::hasContextDefinition
   *
   * @depends testGetContextDefinition
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetContextDefinitionWithNonExistentDefinition() {
    $name = $this->randomMachineName();

    $this->assertFalse($this->sut->hasContextDefinition($name));
    $this->sut->getContextDefinition($name);
  }

}
