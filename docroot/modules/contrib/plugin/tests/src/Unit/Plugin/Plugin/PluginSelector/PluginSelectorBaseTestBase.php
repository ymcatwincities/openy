<?php

namespace Drupal\Tests\plugin\Unit\Plugin\Plugin\PluginSelector;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\plugin\DefaultPluginResolver\DefaultPluginResolverInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Provides a base for tests for classes that extend
 * \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorBase.
 */
abstract class PluginSelectorBaseTestBase extends UnitTestCase {

  /**
   * The default plugin resolver.
   *
   * @var \Drupal\plugin\DefaultPluginResolver\DefaultPluginResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $defaultPluginResolver;

  /**
   * The plugin definition of the class under test.
   *
   * @var array
   */
  protected $pluginDefinition = [];

  /**
   * The plugin ID of the class plugin under test.
   *
   * @var array
   */
  protected $pluginId;

  /**
   * The plugin manager through which to select plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $selectablePluginManager;

  /**
   * The plugin type of which to select plugins.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $selectablePluginType;

  /**
   * The selected plugin.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $selectedPlugin;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $sut;

  /**
   * {@inheritdoc}
   *
   */
  public function setUp() {
    $this->defaultPluginResolver = $this->getMock(DefaultPluginResolverInterface::class);

    $this->pluginId = $this->randomMachineName();

    $this->selectablePluginManager = $this->getMock(PluginManagerInterface::class);

    $this->selectablePluginType = $this->getMock(PluginTypeInterface::class);
    $this->selectablePluginType->expects($this->any())
      ->method('getPluginManager')
      ->willReturn($this->selectablePluginManager);

    $this->selectedPlugin = $this->getMock(PluginInspectionInterface::class);
  }

}
