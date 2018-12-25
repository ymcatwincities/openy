<?php

namespace Drupal\Tests\plugin\Unit\Event;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\plugin\Event\ResolveDefaultPlugin;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\Event\ResolveDefaultPlugin
 *
 * @group Plugin
 */
class ResolveDefaultPluginTest extends UnitTestCase {

  /**
   * The plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginType;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\Event\ResolveDefaultPlugin
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->pluginType = $this->getMock(PluginTypeInterface::class);

    $this->sut = new ResolveDefaultPlugin($this->pluginType);
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct() {
    $this->sut = new ResolveDefaultPlugin($this->pluginType);
    $this->assertInstanceOf(ResolveDefaultPlugin::class, $this->sut);
  }

  /**
   * @covers ::getPluginType
   */
  public function testGetPluginType() {
    $this->assertSame($this->pluginType, $this->sut->getPluginType());
  }

  /**
   * @covers ::getDefaultPluginInstance
   * @covers ::setDefaultPluginInstance
   */
  public function testGetDefaultPluginInstance() {
    $default_plugin_instance = $this->getMock(PluginInspectionInterface::class);
    $this->assertNull($this->sut->getDefaultPluginInstance());
    $this->assertSame($this->sut, $this->sut->setDefaultPluginInstance($default_plugin_instance));
    $this->assertSame($default_plugin_instance, $this->sut->getDefaultPluginInstance());
  }

}
