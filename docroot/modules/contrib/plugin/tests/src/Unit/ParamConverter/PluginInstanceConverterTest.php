<?php

namespace Drupal\Tests\plugin\Unit\ParamConverter;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\plugin\ParamConverter\PluginInstanceConverter;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\plugin\ParamConverter\PluginInstanceConverter
 *
 * @group Plugin
 */
class PluginInstanceConverterTest extends UnitTestCase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $pluginTypeManager;

  /**
   * The system under test.
   *
   * @var \Drupal\plugin\ParamConverter\PluginInstanceConverter
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->pluginTypeManager = $this->prophesize(PluginTypeManagerInterface::class);

    $this->sut = new PluginInstanceConverter($this->pluginTypeManager->reveal());
  }

  /**
   * @covers ::applies
   * @covers ::validateParameterDefinition
   * @covers ::getConverterDefinitionConstraint
   * @covers ::getConverterDefinition
   * @covers ::getConverterDefinitionKey
   * @covers ::__construct
   *
   * @dataProvider provideApplies
   */
  public function testApplies($expected, $definition) {
    $name = 'foo_bar';
    $route = $this->prophesize(Route::class);


    $this->assertSame($expected, $this->sut->applies($definition, $name, $route->reveal()));
  }

  /**
   * Provides data to self::testApplies().
   */
  public function provideApplies() {
    $data = [];

    $data['applies-because-implicitly-enabled'] = [TRUE, [
      'plugin.plugin_instance' => [
        'plugin_type_id' => 'foo.bar',
      ],
    ]];
    $data['applies-because-explicitly-enabled'] = [TRUE, [
      'plugin.plugin_instance' => [
        'enabled' => TRUE,
        'plugin_type_id' => 'foo.bar',
      ],
    ]];
    $data['applies-not-because-disabled'] = [FALSE, [
      'plugin.plugin_instance' => [
        'enabled' => FALSE,
        'plugin_type_id' => 'foo.bar',
      ],
    ]];
    $data['applies-not-because-non-existent'] = [FALSE, []];

    return $data;
  }

  /**
   * @covers ::convert
   * @covers ::doConvert
   * @covers ::validateParameterDefinition
   * @covers ::getConverterDefinitionConstraint
   * @covers ::getConverterDefinition
   * @covers ::getConverterDefinitionKey
   * @covers ::__construct
   */
  public function testConvertWithExceptionReturnsNull() {
    $plugin_type_id = 'foo_bar.baz';
    $definition = [
      'plugin.plugin_instance' => [
        'plugin_type_id' => $plugin_type_id,
      ],
    ];
    $plugin_id = 'foozaar.bazaar';
    $name = 'foo_bar';
    $defaults = [];

    $plugin_manager = $this->prophesize(PluginManagerInterface::class);
    $plugin_manager->hasDefinition($plugin_id)->willReturn(TRUE);
    $plugin_manager->createInstance($plugin_id)->willThrow(new PluginNotFoundException($plugin_id));

    $plugin_type = $this->prophesize(PluginTypeInterface::class);
    $plugin_type->getPluginManager()->willReturn($plugin_manager);

    $this->pluginTypeManager->getPluginType($plugin_type_id)->willReturn($plugin_type);

    $original_error_reporting = error_reporting();
    error_reporting($original_error_reporting & ~E_USER_WARNING);
    $this->assertNull($this->sut->convert($plugin_id, $definition, $name, $defaults));
    error_reporting($original_error_reporting);
  }

  /**
   * @covers ::convert
   * @covers ::doConvert
   * @covers ::validateParameterDefinition
   * @covers ::getConverterDefinitionConstraint
   * @covers ::getConverterDefinition
   * @covers ::getConverterDefinitionKey
   * @covers ::__construct
   */
  public function testConvertWithKnownPlugin() {
    $plugin_type_id = 'foo_bar.baz';
    $definition = [
      'plugin.plugin_instance' => [
        'plugin_type_id' => $plugin_type_id,
      ],
    ];
    $plugin_id = 'foozaar.bazaar';
    $name = 'foo_bar';
    $defaults = [];

    $plugin_instance = new \stdClass();

    $plugin_manager = $this->prophesize(PluginManagerInterface::class);
    $plugin_manager->hasDefinition($plugin_id)->willReturn(TRUE);
    $plugin_manager->createInstance($plugin_id)->willReturn($plugin_instance);

    $plugin_type = $this->prophesize(PluginTypeInterface::class);
    $plugin_type->getPluginManager()->willReturn($plugin_manager);

    $this->pluginTypeManager->getPluginType($plugin_type_id)->willReturn($plugin_type);

    $this->assertSame($plugin_instance, $this->sut->convert($plugin_id, $definition, $name, $defaults));
  }

  /**
   * @covers ::convert
   * @covers ::doConvert
   * @covers ::validateParameterDefinition
   * @covers ::getConverterDefinitionConstraint
   * @covers ::getConverterDefinition
   * @covers ::getConverterDefinitionKey
   * @covers ::__construct
   */
  public function testConvertWithUnknownPlugin() {
    $plugin_type_id = 'foo_bar.baz';
    $definition = [
      'plugin.plugin_instance' => [
        'plugin_type_id' => $plugin_type_id,
      ],
    ];
    $plugin_id = 'foozaar.bazaar';
    $name = 'foo_bar';
    $defaults = [];

    $plugin_manager = $this->prophesize(PluginManagerInterface::class);
    $plugin_manager->hasDefinition($plugin_id)->willReturn(FALSE);

    $plugin_type = $this->prophesize(PluginTypeInterface::class);
    $plugin_type->getPluginManager()->willReturn($plugin_manager);

    $this->pluginTypeManager->getPluginType($plugin_type_id)->willReturn($plugin_type);

    $original_error_reporting = error_reporting();
    error_reporting($original_error_reporting & ~E_USER_WARNING);
    $this->assertNull($this->sut->convert($plugin_id, $definition, $name, $defaults));
    error_reporting($original_error_reporting);
  }

  /**
   * @covers ::convert
   * @covers ::doConvert
   * @covers ::validateParameterDefinition
   * @covers ::getConverterDefinitionConstraint
   * @covers ::getConverterDefinition
   * @covers ::getConverterDefinitionKey
   * @covers ::__construct
   */
  public function testConvertWithInvalidDefinition() {
    // Leave out the "plugin.plugin_instance" key.
    $definition = [];
    $plugin_id = 'foozaar.bazaar';
    $name = 'foo_bar';
    $defaults = [];

    $this->assertNull($this->sut->convert($plugin_id, $definition, $name, $defaults));
  }

}
