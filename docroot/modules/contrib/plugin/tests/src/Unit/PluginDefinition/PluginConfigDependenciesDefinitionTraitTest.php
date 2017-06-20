<?php

namespace Drupal\Tests\plugin\Unit\PluginDefinition;

use Drupal\plugin\PluginDefinition\PluginConfigDependenciesDefinitionTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDefinition\PluginConfigDependenciesDefinitionTrait
 *
 * @group Plugin
 */
class PluginConfigDependenciesDefinitionTraitTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDefinition\PluginConfigDependenciesDefinitionTrait
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->sut = $this->getMockForTrait(PluginConfigDependenciesDefinitionTrait::class);
  }

  /**
   * @covers ::setConfigDependencies
   * @covers ::getConfigDependencies
   */
  public function testGetConfigDependencies() {
    $dependencies = [
      'module' => [$this->randomMachineName()],
    ];

    $this->assertSame($this->sut, $this->sut->setConfigDependencies($dependencies));
    $this->assertSame($dependencies, $this->sut->getConfigDependencies());
  }

}
