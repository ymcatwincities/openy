<?php

namespace Drupal\Tests\plugin\Unit\PluginType;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator;
use Drupal\plugin\PluginDefinition\PluginDefinitionInterface;
use Drupal\plugin\PluginType\PluginType;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\plugin\PluginType\PluginType
 *
 * @group Plugin
 */
class PluginTypeTest extends UnitTestCase {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $container;

  /**
   * The plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginManager;

  /**
   * The plugin type definition.
   *
   * @var mixed[]
   */
  protected $pluginTypeDefinition;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginType\PluginType
   */
  protected $sut;

  public function setUp() {
    $this->pluginTypeDefinition = [
      'id' => $this->randomMachineName(),
      'label' => $this->getRandomGenerator()->string(),
      'description' => $this->getRandomGenerator()->string(),
      'provider' => $this->randomMachineName(),
      'plugin_manager_service_id' => $this->randomMachineName(),
      'field_type' => (bool) mt_rand(0, 1),
    ];

    $class_resolver = $this->getMock(ClassResolverInterface::class);

    $typed_config_manager = $this->getMock(TypedConfigManagerInterface::class);
    $typed_config_manager->expects($this->atLeastOnce())
      ->method('hasConfigSchema')
      ->willReturn(TRUE);

    $this->pluginManager = $this->getMock(PluginManagerInterface::class);

    $this->container = $this->getMock(ContainerInterface::class);
    $map = [
      ['class_resolver', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $class_resolver],
      ['config.typed', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $typed_config_manager],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->getStringTranslationStub()],
      [$this->pluginTypeDefinition['plugin_manager_service_id'], ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->pluginManager],
    ];
    $this->container->expects($this->atLeastOnce())
      ->method('get')
      ->willReturnMap($map);

    $this->sut = PluginType::createFromDefinition($this->container, $this->pluginTypeDefinition);
  }

  /**
   * @covers ::createFromDefinition
   * @covers ::__construct
   */
  public function testCreateFromDefinition() {
    $this->sut = PluginType::createFromDefinition($this->container, $this->pluginTypeDefinition);
  }

  /**
   * @covers ::getId
   */
  public function testGetPluginId() {
    $this->assertSame($this->pluginTypeDefinition['id'], $this->sut->getId());
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $this->assertSame($this->pluginTypeDefinition['label'], $this->sut->getLabel()->getUntranslatedString());
  }

  /**
   * @covers ::getDescription
   */
  public function testGetDescription() {
    $this->assertSame($this->pluginTypeDefinition['description'], $this->sut->getDescription()->getUntranslatedString());
  }

  /**
   * @covers ::getProvider
   */
  public function testGetProvider() {
    $this->assertSame($this->pluginTypeDefinition['provider'], $this->sut->getProvider());
  }

  /**
   * @covers ::getPluginManager
   */
  public function testGetPluginManager() {
    $this->assertSame($this->pluginManager, $this->sut->getPluginManager());
  }

  /**
   * @covers ::isFieldType
   */
  public function testGetFieldType() {
    $this->assertSame($this->pluginTypeDefinition['field_type'], $this->sut->isFieldType());
  }

  /**
   * @covers ::getPluginConfigurationSchemaId
   */
  public function testGetPluginConfigurationSchemaIdWithDefaultId() {
    $plugin_id = 'FooBarQux';
    $expected_schema_id = sprintf('plugin.plugin_configuration.%s.%s', $this->pluginTypeDefinition['id'], $plugin_id);
    $this->assertSame($expected_schema_id, $this->sut->getPluginConfigurationSchemaId($plugin_id));
  }

  /**
   * @covers ::getPluginConfigurationSchemaId
   */
  public function testGetPluginConfigurationSchemaIdWithDefinedId() {
    $plugin_id = 'FooBarQux';
    $schema_id = 'foo_bar.qux.[plugin_id]';
    $this->pluginTypeDefinition['plugin_configuration_schema_id'] = $schema_id;
    $this->sut = PluginType::createFromDefinition($this->container, $this->pluginTypeDefinition);
    $expected_schema_id = 'foo_bar.qux.' . $plugin_id;
    $this->assertSame($expected_schema_id, $this->sut->getPluginConfigurationSchemaId($plugin_id));
  }

  /**
   * @covers ::ensureTypedPluginDefinition
   * @covers ::createFromDefinition
   * @covers ::__construct
   */
  public function testEnsureTypedPluginDefinition() {
    $decorated_plugin_definition = [
      'foo' => $this->randomMachineName(),
    ];

    $this->pluginTypeDefinition['plugin_definition_decorator_class'] = ArrayPluginDefinitionDecorator::class;

    $this->sut = PluginType::createFromDefinition($this->container, $this->pluginTypeDefinition);

    $typed_plugin_definition = $this->sut->ensureTypedPluginDefinition($decorated_plugin_definition);

    $this->assertInstanceOf(PluginDefinitionInterface::class, $typed_plugin_definition);
    // We use ArrayPluginDefinitionDecorator for testing. The following
    // assertion makes sure the method under test correctly passes on the
    // decorated plugin definition to the decorator. The array handling is not
    // part of this test.
    /** @var \Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator $typed_plugin_definition */
    $this->assertSame($decorated_plugin_definition, $typed_plugin_definition->getArrayDefinition());
  }

  /**
   * @covers ::ensureTypedPluginDefinition
   * @covers ::createFromDefinition
   * @covers ::__construct
   */
  public function testEnsureTypedPluginDefinitionWithAlreadyTypedDefinition() {
    $decorated_plugin_definition = $this->getMock(PluginDefinitionInterface::class);

    $typed_plugin_definition = $this->sut->ensureTypedPluginDefinition($decorated_plugin_definition);

    $this->assertInstanceOf(PluginDefinitionInterface::class, $typed_plugin_definition);
  }

  /**
   * @covers ::ensureTypedPluginDefinition
   *
   * @expectedException \Exception
   */
  public function testEnsureTypedPluginDefinitionWithoutDecorator() {
    $decorated_plugin_definition = [
      'foo' => $this->randomMachineName(),
    ];

    $this->sut->ensureTypedPluginDefinition($decorated_plugin_definition);
  }

}
