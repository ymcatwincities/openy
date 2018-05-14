<?php

namespace Drupal\Tests\plugin\Unit\Plugin\Plugin\PluginSelector;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorBase
 *
 * @group Plugin
 */
class PluginSelectorBaseTest extends PluginSelectorBaseTestBase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $configuration = [];
    $this->sut = $this->getMockBuilder(PluginSelectorBase::class)
      ->setConstructorArgs([$configuration, $this->pluginId, $this->pluginDefinition, $this->defaultPluginResolver])
      ->getMockForAbstractClass();
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->getMock(ContainerInterface::class);
    $map = [
      ['plugin.default_plugin_resolver', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->defaultPluginResolver],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    /** @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorBase $class */
    $class = get_class($this->sut);
    $plugin = $class::create($container, [], $this->pluginId, $this->pluginDefinition);
    $this->assertInstanceOf(get_class($this->sut), $plugin);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $configuration = $this->sut->defaultConfiguration();
    $this->assertInternalType('array', $configuration);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $this->assertSame([], $this->sut->calculateDependencies());
  }

  /**
   * @covers ::setConfiguration
   * @covers ::getConfiguration
   */
  public function testGetConfiguration() {
    $configuration = array($this->randomMachineName());
    $this->assertSame($this->sut, $this->sut->setConfiguration($configuration));
    $this->assertSame($configuration, $this->sut->getConfiguration());
  }

  /**
   * @covers ::setLabel
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $label = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setLabel($label));
    $this->assertSame($label, $this->sut->getLabel());
  }

  /**
   * @covers ::setDescription
   * @covers ::getDescription
   */
  public function testGetDescription() {
    $description = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setDescription($description));
    $this->assertSame($description, $this->sut->getDescription());
  }

  /**
   * @covers ::setCollectPluginConfiguration
   * @covers ::getCollectPluginConfiguration
   */
  public function testGetCollectPluginConfiguration() {
    $collect = (bool) mt_rand(0, 1);
    $this->assertSame($this->sut, $this->sut->setCollectPluginConfiguration($collect));
    $this->assertSame($collect, $this->sut->getCollectPluginConfiguration());
  }

  /**
   * @covers ::setPreviouslySelectedPlugins
   * @covers ::getPreviouslySelectedPlugins
   */
  public function testGetPreviouslySelectedPlugins() {
    $plugin = $this->getMock(PluginInspectionInterface::class);
    $this->sut->setPreviouslySelectedPlugins([$plugin]);
    $this->assertSame([$plugin], $this->sut->getPreviouslySelectedPlugins());
  }

  /**
   * @covers ::setKeepPreviouslySelectedPlugins
   * @covers ::getKeepPreviouslySelectedPlugins
   *
   * @depends testGetPreviouslySelectedPlugins
   */
  public function testGetKeepPreviouslySelectedPlugins() {
    $keep = (bool) mt_rand(0, 1);
    $plugin = $this->getMock(PluginInspectionInterface::class);
    $this->sut->setPreviouslySelectedPlugins([$plugin]);
    $this->assertSame($this->sut, $this->sut->setKeepPreviouslySelectedPlugins($keep));
    $this->assertSame($keep, $this->sut->getKeepPreviouslySelectedPlugins());

    // Confirm that all previously selected plugins are removed.
    $this->sut->setPreviouslySelectedPlugins([$plugin]);
    $this->sut->setKeepPreviouslySelectedPlugins(FALSE);
    $this->assertEmpty($this->sut->getPreviouslySelectedPlugins());
  }

  /**
   * @covers ::setSelectedPlugin
   * @covers ::getSelectedPlugin
   */
  public function testGetSelectedPlugin() {
    $this->sut->setSelectablePluginType($this->selectablePluginType);
    $plugin = $this->getMock(PluginInspectionInterface::class);
    $this->assertSame($this->sut, $this->sut->setSelectedPlugin($plugin));
    $this->assertSame($plugin, $this->sut->getSelectedPlugin());
  }

  /**
   * @covers ::setRequired
   * @covers ::isRequired
   */
  public function testGetRequired() {
    $this->assertFalse($this->sut->isRequired());
    $this->assertSame($this->sut, $this->sut->setRequired());
    $this->assertTrue($this->sut->isRequired());
    $this->sut->setRequired(FALSE);
    $this->assertFalse($this->sut->isRequired());
  }

  /**
   * @covers ::buildSelectorForm
   * @covers ::setSelectablePluginType
   */
  public function testBuildSelectorForm() {
    $this->sut->setSelectablePluginType($this->selectablePluginType);

    $plugin_selector_form = [];
    $plugin_selector_form_state = $this->getMock(SubformStateInterface::class);

    $plugin_selector_form = $this->sut->buildSelectorForm($plugin_selector_form, $plugin_selector_form_state);

    $this->assertInternalType('array', $plugin_selector_form);
  }

}
