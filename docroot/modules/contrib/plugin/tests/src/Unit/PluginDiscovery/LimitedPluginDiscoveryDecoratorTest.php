<?php

namespace Drupal\Tests\plugin\Unit\PluginDiscovery;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\plugin\PluginDiscovery\LimitedPluginDiscoveryDecorator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDiscovery\LimitedPluginDiscoveryDecorator
 *
 * @group Plugin
 */
class LimitedPluginDiscoveryDecoratorTest extends UnitTestCase {

  /**
   * The original plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginManager;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDiscovery\LimitedPluginDiscoveryDecorator
   */
  protected $sut;

  public function setUp() {
    $this->pluginManager = $this->getMock(PluginManagerInterface::class);

    $this->sut = new LimitedPluginDiscoveryDecorator($this->pluginManager);
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct() {
    $this->sut = new LimitedPluginDiscoveryDecorator($this->pluginManager);
    $this->assertInstanceOf(LimitedPluginDiscoveryDecorator::class, $this->sut);
  }

  /**
   * @covers ::getDefinitions
   * @covers ::processDecoratedDefinitions
   * @covers ::setDiscoveryLimit
   * @covers ::resetDiscoveryLimit
   */
  public function testGetDefinitions() {
    $plugin_id_a = $this->randomMachineName();
    $plugin_definition_a = [
      'id' => $plugin_id_a,
    ];
    $plugin_id_b = $this->randomMachineName();
    $plugin_definition_b = [
      'id' => $plugin_id_b,
    ];
    $plugin_id_c = $this->randomMachineName();
    $plugin_definition_c = [
      'id' => $plugin_id_c,
    ];

    $plugin_definitions = [
      $plugin_id_a => $plugin_definition_a,
      $plugin_id_b => $plugin_definition_b,
      $plugin_id_c => $plugin_definition_c,
    ];

    $this->pluginManager->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);

    $this->sut->setDiscoveryLimit([$plugin_id_a, $plugin_id_c]);

    $expected_plugin_definitions = [
      $plugin_id_a => $plugin_definition_a,
      $plugin_id_c => $plugin_definition_c,
    ];
    $this->assertEquals($expected_plugin_definitions, $this->sut->getDefinitions());

    $this->sut->resetDiscoveryLimit();

    $this->assertEquals($plugin_definitions, $this->sut->getDefinitions());
  }

  /**
   * @covers ::getDefinitions
   * @covers ::processDecoratedDefinitions
   * @covers ::setDiscoveryLimit
   * @covers ::resetDiscoveryLimit
   */
  public function testGetDefinitionsWithoutAllowedPlugins() {
    $plugin_id_a = $this->randomMachineName();
    $plugin_definition_a = [
      'id' => $plugin_id_a,
    ];
    $plugin_id_b = $this->randomMachineName();
    $plugin_definition_b = [
      'id' => $plugin_id_b,
    ];
    $plugin_id_c = $this->randomMachineName();
    $plugin_definition_c = [
      'id' => $plugin_id_c,
    ];

    $plugin_definitions = [
      $plugin_id_a => $plugin_definition_a,
      $plugin_id_b => $plugin_definition_b,
      $plugin_id_c => $plugin_definition_c,
    ];

    $this->pluginManager->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);

    $this->sut->setDiscoveryLimit([]);

    $this->assertEquals([], $this->sut->getDefinitions());
  }

}

/**
 * Provides a dummy plugin manager that caches definitions.
 */
abstract class LimitedPluginDiscoveryDecoratorTestCachedDiscovery implements DiscoveryInterface, CachedDiscoveryInterface {
}
