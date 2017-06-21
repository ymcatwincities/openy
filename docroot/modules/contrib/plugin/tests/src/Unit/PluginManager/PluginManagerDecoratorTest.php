<?php

namespace Drupal\Tests\plugin\Unit\PluginManager;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\plugin\PluginManager\PluginManagerDecorator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginManager\PluginManagerDecorator
 *
 * @group Plugin
 */
class PluginManagerDecoratorTest extends UnitTestCase {

  /**
   * The decorated plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $decoratedDiscovery;

  /**
   * The decorated plugin factory.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $decoratedFactory;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginManager\PluginManagerDecorator
   */
  protected $sut;

  /**
   * @covers ::__construct
   * @covers ::createInstance
   */
  public function testCreateInstanceWithExistingPlugin() {
    $plugin_manager = $this->getMock(PluginManagerInterface::class);

    $this->decoratedDiscovery = $plugin_manager;

    $this->decoratedFactory = $plugin_manager;

    $this->sut = new PluginManagerDecorator($plugin_manager);

    $plugin_id = $this->randomMachineName();

    $plugin = $this->getMock(PluginInspectionInterface::class);

    $plugin_definitions = [
      $plugin_id => [
        'id' => $plugin_id,
      ],
    ];

    $this->decoratedDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);

    $this->decoratedFactory->expects($this->once())
      ->method('createInstance')
      ->with($plugin_id)
      ->willReturn($plugin);

    $this->assertSame($plugin, $this->sut->createInstance($plugin_id));
  }

  /**
   * @covers ::__construct
   * @covers ::createInstance
   */
  public function testCreateInstanceWithExistingPluginAndOverriddenDiscovery() {
    $plugin_manager = $this->getMock(PluginManagerInterface::class);

    $this->decoratedDiscovery = $this->getMock(DiscoveryInterface::class);

    $this->decoratedFactory = $plugin_manager;

    $this->sut = new PluginManagerDecorator($plugin_manager, $this->decoratedDiscovery);

    $plugin_id = $this->randomMachineName();

    $plugin = $this->getMock(PluginInspectionInterface::class);

    $plugin_definitions = [
      $plugin_id => [
        'id' => $plugin_id,
      ],
    ];

    $this->decoratedDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);

    $this->decoratedFactory->expects($this->once())
      ->method('createInstance')
      ->with($plugin_id)
      ->willReturn($plugin);

    $this->assertSame($plugin, $this->sut->createInstance($plugin_id));
  }

  /**
   * @covers ::__construct
   * @covers ::createInstance
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testCreateInstanceWithNonExistingPlugin() {
    $plugin_manager = $this->getMock(PluginManagerInterface::class);

    $this->decoratedDiscovery = $plugin_manager;

    $this->decoratedFactory = $plugin_manager;

    $this->sut = new PluginManagerDecorator($plugin_manager);

    $plugin_id = $this->randomMachineName();

    $this->decoratedDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([]);

    $this->decoratedFactory->expects($this->never())
      ->method('createInstance');

    $this->sut->createInstance($plugin_id);
  }

  /**
   * @covers ::__construct
   * @covers ::createInstance
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testCreateInstanceWithNonExistingPluginAndOverriddenDiscovery() {
    $plugin_manager = $this->getMock(PluginManagerInterface::class);

    $this->decoratedDiscovery = $this->getMock(DiscoveryInterface::class);

    $this->decoratedFactory = $plugin_manager;

    $this->sut = new PluginManagerDecorator($plugin_manager, $this->decoratedDiscovery);

    $plugin_id = $this->randomMachineName();

    $this->decoratedDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([]);

    $this->decoratedFactory->expects($this->never())
      ->method('createInstance');

    $this->sut->createInstance($plugin_id);
  }

  /**
   * @covers ::getInstance
   *
   * @expectedException \BadMethodCallException
   */
  public function testGetInstance() {
    $plugin_manager = $this->getMock(PluginManagerInterface::class);

    $this->sut = new PluginManagerDecorator($plugin_manager);

    $this->sut->getInstance([]);
  }

}
