<?php

namespace Drupal\Tests\plugin\Unit\PluginDefinition;

use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\plugin\PluginDefinition\PluginDeriverDefinitionTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDefinition\PluginDeriverDefinitionTrait
 *
 * @group Plugin
 */
class PluginDeriverDefinitionTraitTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDefinition\PluginDeriverDefinitionTrait
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->sut = $this->getMockForTrait(PluginDeriverDefinitionTrait::class);
  }


  /**
   * @covers ::setDeriverClass
   * @covers ::getDeriverClass
   */
  public function testGetDeriverClass() {
    $class = get_class($this->getMock(DeriverInterface::class));

    $this->assertSame($this->sut, $this->sut->setDeriverClass($class));
    $this->assertSame($class, $this->sut->getDeriverClass());
  }

}
