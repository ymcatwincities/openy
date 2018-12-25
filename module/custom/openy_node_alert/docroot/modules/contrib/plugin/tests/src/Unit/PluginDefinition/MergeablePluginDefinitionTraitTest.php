<?php

namespace Drupal\Tests\plugin\Unit\PluginDefinition;

use Drupal\plugin\PluginDefinition\MergeablePluginDefinitionTrait;
use Drupal\plugin\PluginDefinition\PluginDefinitionInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDefinition\MergeablePluginDefinitionTrait
 * @group Plugin
 */
class MergeablePluginDefinitionTraitTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDefinition\MergeablePluginDefinitionTrait|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->sut = $this->getMockForTrait(MergeablePluginDefinitionTrait::class);
  }

  /**
   * @covers ::mergeDefaultDefinition
   * @covers ::isDefinitionCompatible
   * @covers ::doMergeDefaultDefinition
   */
  public function testMergeDefaultDefinition() {
    $other_definition = $this->getMock(PluginDefinitionInterface::class);

    $this->sut->expects($this->atLeastOnce())
      ->method('isDefinitionCompatible')
      ->willReturnCallback(function ($value) use ($other_definition) {
        return $value == $other_definition;
      });

    $this->assertSame($this->sut, $this->sut->mergeDefaultDefinition($other_definition));
  }

  /**
   * @covers ::mergeDefaultDefinition
   * @covers ::isDefinitionCompatible
   *
   * @depends testMergeDefaultDefinition
   *
   * @expectedException \InvalidArgumentException
   */
  public function testMergeDefaultDefinitionWithInvalidOtherDefinition() {
    $other_definition = $this->getMock(PluginDefinitionInterface::class);

    $this->sut->expects($this->atLeastOnce())
      ->method('isDefinitionCompatible')
      ->willReturn(FALSE);

    $this->sut->mergeDefaultDefinition($other_definition);
  }

  /**
   * @covers ::mergeOverrideDefinition
   * @covers ::isDefinitionCompatible
   * @covers ::doMergeOverrideDefinition
   */
  public function testMergeOverrideDefinition() {
    $other_definition = $this->getMock(PluginDefinitionInterface::class);

    $this->sut->expects($this->atLeastOnce())
      ->method('isDefinitionCompatible')
      ->willReturnCallback(function ($value) use ($other_definition) {
        return $value == $other_definition;
      });

    $this->assertSame($this->sut, $this->sut->mergeOverrideDefinition($other_definition));
  }

  /**
   * @covers ::mergeOverrideDefinition
   * @covers ::isDefinitionCompatible
   *
   * @depends testMergeOverrideDefinition
   *
   * @expectedException \InvalidArgumentException
   */
  public function testMergeOverrideDefinitionWithInvalidOtherDefinition() {
    $other_definition = $this->getMock(PluginDefinitionInterface::class);

    $this->sut->expects($this->atLeastOnce())
      ->method('isDefinitionCompatible')
      ->willReturn(FALSE);

    $this->sut->mergeOverrideDefinition($other_definition);
  }

}
