<?php

namespace Drupal\Tests\plugin\Unit\Plugin\Field\FieldWidget;

use Drupal\plugin\Plugin\Field\FieldWidget\PluginSelectorDeriver;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\plugin\Plugin\Field\FieldWidget\PluginSelectorDeriver
 *
 * @group Plugin
 */
class PluginSelectorDeriverTest extends UnitTestCase {

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginSelectorManager;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\Plugin\Field\FieldWidget\PluginSelectorDeriver
   */
  protected $sut;

  public function setUp() {
    parent::setUp();

    $this->pluginSelectorManager = $this->getMock(PluginSelectorManagerInterface::class);

    $this->sut = new PluginSelectorDeriver($this->pluginSelectorManager);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->getMock(ContainerInterface::class);
    $map = [
      ['plugin.manager.plugin.plugin_selector', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->pluginSelectorManager],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = PluginSelectorDeriver::create($container, $this->randomMachineName());
    $this->assertInstanceOf(PluginSelectorDeriver::class, $sut);
  }

  /**
   * @covers ::getDerivativeDefinitions
   */
  function testGetDerivativeDefinitions() {
    $provider = $this->randomMachineName();

    $plugin_selector_id_a = $this->randomMachineName();
    $plugin_selector_label_a = $this->randomMachineName();
    $plugin_selector_description_a = $this->randomMachineName();
    $plugin_selector_definition_a = [
      'id' => $plugin_selector_id_a,
      'label' => $plugin_selector_label_a,
      'description' => $plugin_selector_description_a,
    ];
    $plugin_selector_id_b = $this->randomMachineName();
    $plugin_selector_label_b = $this->randomMachineName();
    $plugin_selector_description_b = '';
    $plugin_selector_definition_b = [
      'id' => $plugin_selector_id_b,
      'label' => $plugin_selector_label_b,
      'description' => $plugin_selector_description_b,
    ];

    $plugin_selector_definitions = [
      $plugin_selector_id_a => $plugin_selector_definition_a,
      $plugin_selector_id_b => $plugin_selector_definition_b,
    ];

    $this->pluginSelectorManager->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->willReturn($plugin_selector_definitions);

    $base_plugin_definition = [
      'provider' => $provider,
    ];

    $derivative_definitions = $this->sut->getDerivativeDefinitions($base_plugin_definition);

    $this->assertSame($plugin_selector_label_a, (string) $derivative_definitions[$plugin_selector_id_a]['label']);
    $this->assertSame($plugin_selector_description_a, (string) $derivative_definitions[$plugin_selector_id_a]['description']);
    $this->assertSame($provider, $derivative_definitions[$plugin_selector_id_a]['provider']);
    $this->assertSame($plugin_selector_id_a, $derivative_definitions[$plugin_selector_id_a]['plugin_selector_id']);
    $this->assertSame($plugin_selector_label_b, (string) $derivative_definitions[$plugin_selector_id_b]['label']);
    $this->assertSame($plugin_selector_description_b, (string) $derivative_definitions[$plugin_selector_id_b]['description']);
    $this->assertSame($provider, $derivative_definitions[$plugin_selector_id_b]['provider']);
    $this->assertSame($plugin_selector_id_b, $derivative_definitions[$plugin_selector_id_b]['plugin_selector_id']);
  }

}
