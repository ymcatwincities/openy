<?php

namespace Drupal\Tests\plugin\Unit\PluginDiscovery;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\plugin\PluginDefinition\PluginDefinitionInterface;
use Drupal\plugin\PluginDiscovery\TypedDefinitionEnsuringPluginDiscoveryDecorator;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDiscovery\TypedDefinitionEnsuringPluginDiscoveryDecorator
 *
 * @group Plugin
 */
class TypedDefinitionEnsuringPluginDiscoveryDecoratorTest extends UnitTestCase {

  /**
   * The original plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginManager;

  /**
   * The type of the plugin definitions to decorate.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginType;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDiscovery\TypedDefinitionEnsuringPluginDiscoveryDecorator
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->pluginManager = $this->getMock(PluginManagerInterface::class);

    $this->pluginType = $this->getMock(PluginTypeInterface::class);

    $this->sut = new TypedDefinitionEnsuringPluginDiscoveryDecorator($this->pluginType, $this->pluginManager);
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct() {
    $this->sut = new TypedDefinitionEnsuringPluginDiscoveryDecorator($this->pluginType, $this->pluginManager);
    $this->assertInstanceOf(TypedDefinitionEnsuringPluginDiscoveryDecorator::class, $this->sut);
  }

  /**
   * @covers ::getDefinitions
   * @covers ::processDecoratedDefinitions
   */
  public function testGetDefinitions() {
    $decorated_plugin_id_a = $this->randomMachineName();
    $decorated_plugin_definition_a = [
      'id' => $decorated_plugin_id_a,
    ];
    $decorated_plugin_id_b = $this->randomMachineName();
    $decorated_plugin_definition_b = [
      'id' => $decorated_plugin_id_b,
    ];

    $decorated_plugin_definitions = [
      $decorated_plugin_id_a => $decorated_plugin_definition_a,
      $decorated_plugin_id_b => $decorated_plugin_definition_b,
    ];

    $this->pluginManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($decorated_plugin_definitions);

    $typed_plugin_definition_a = $this->getMock(PluginDefinitionInterface::class);
    $typed_plugin_definition_b = $this->getMock(PluginDefinitionInterface::class);

    $map = [
      [$decorated_plugin_definition_a, $typed_plugin_definition_a],
      [$decorated_plugin_definition_b, $typed_plugin_definition_b],
    ];
    $this->pluginType->expects($this->atLeastOnce())
      ->method('ensureTypedPluginDefinition')
      ->willReturnMap($map);

    $expected_plugin_definitions = [
      $decorated_plugin_id_a => $typed_plugin_definition_a,
      $decorated_plugin_id_b => $typed_plugin_definition_b,
    ];
    $this->assertSame($expected_plugin_definitions, $this->sut->getDefinitions());
  }

}
