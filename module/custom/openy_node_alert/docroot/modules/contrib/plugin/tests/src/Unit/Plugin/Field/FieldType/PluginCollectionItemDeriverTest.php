<?php

namespace Drupal\Tests\plugin\Unit\Plugin\Field\FieldType;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemDeriver;
use Drupal\plugin\PluginType\PluginType;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemDeriver
 *
 * @group Plugin
 */
class PluginCollectionItemDeriverTest extends UnitTestCase {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $container;

  /**
   * The plugin type manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginTypeManager;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemDeriver
   */
  protected $sut;

  public function setUp() {
    parent::setUp();

    $this->container = $this->prophesize(ContainerInterface::class);

    $this->pluginTypeManager = $this->getMock(PluginTypeManagerInterface::class);

    $this->sut = new PluginCollectionItemDeriver($this->pluginTypeManager);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->getMock(ContainerInterface::class);
    $map = [
      ['plugin.plugin_type_manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->pluginTypeManager],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = PluginCollectionItemDeriver::create($container, $this->randomMachineName());
    $this->assertInstanceOf(PluginCollectionItemDeriver::class, $sut);
  }

  /**
   * @covers ::getDerivativeDefinitions
   */
  function testGetDerivativeDefinitions() {
    $string_translation = $this->getStringTranslationStub();

    $class_resolver = $this->getMock(ClassResolverInterface::class);

    $typed_config_manager = $this->getMock(TypedConfigManagerInterface::class);
    $typed_config_manager->expects($this->atLeastOnce())
      ->method('hasConfigSchema')
      ->willReturn(TRUE);

    $provider = $this->randomMachineName();

    $plugin_type_id_a = $this->randomMachineName();
    $plugin_type_label_a = $this->randomMachineName();
    $plugin_type_description_a = $this->randomMachineName();
    $plugin_type_definition_a = [
      'id' => $plugin_type_id_a,
      'label' => $plugin_type_label_a,
      'description' => $plugin_type_description_a,
      'provider' => $this->randomMachineName(),
      'plugin_manager_service_id' => 'foo.bar',
    ];
    $plugin_type_a = new PluginType($plugin_type_definition_a, $this->container->reveal(), $string_translation, $class_resolver, $typed_config_manager);
    $plugin_type_id_b = $this->randomMachineName();
    $plugin_type_label_b = $this->randomMachineName();
    $plugin_type_description_b = '';
    $plugin_type_definition_b = [
      'id' => $plugin_type_id_b,
      'label' => $plugin_type_label_b,
      'description' => $plugin_type_description_b,
      'provider' => $this->randomMachineName(),
      'plugin_manager_service_id' => 'foo.bar',
    ];
    $plugin_type_b = new PluginType($plugin_type_definition_b, $this->container->reveal(), $string_translation, $class_resolver, $typed_config_manager);

    $plugin_types = [$plugin_type_a, $plugin_type_b];

    $this->pluginTypeManager->expects($this->atLeastOnce())
      ->method('getPluginTypes')
      ->willReturn($plugin_types);

    $base_plugin_definition = [
      'provider' => $provider,
    ];

    $derivative_definitions = $this->sut->getDerivativeDefinitions($base_plugin_definition);

    $this->assertSame($plugin_type_label_a, (string) $derivative_definitions[$plugin_type_id_a]['label']);
    $this->assertSame($plugin_type_description_a, (string) $derivative_definitions[$plugin_type_id_a]['description']);
    $this->assertSame($provider, $derivative_definitions[$plugin_type_id_a]['provider']);
    $this->assertSame($plugin_type_id_a, $derivative_definitions[$plugin_type_id_a]['plugin_type_id']);
    $this->assertSame($plugin_type_label_b, (string) $derivative_definitions[$plugin_type_id_b]['label']);
    $this->assertSame($plugin_type_description_b, (string) $derivative_definitions[$plugin_type_id_b]['description']);
    $this->assertSame($provider, $derivative_definitions[$plugin_type_id_b]['provider']);
    $this->assertSame($plugin_type_id_b, $derivative_definitions[$plugin_type_id_b]['plugin_type_id']);
  }

}
