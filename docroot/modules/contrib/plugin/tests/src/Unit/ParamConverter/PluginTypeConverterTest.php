<?php

namespace Drupal\Tests\plugin\Unit\ParamConverter;

use Drupal\plugin\ParamConverter\PluginTypeConverter;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\plugin\ParamConverter\PluginTypeConverter
 *
 * @group Plugin
 */
class PluginTypeConverterTest extends UnitTestCase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $pluginTypeManager;

  /**
   * The system under test.
   *
   * @var \Drupal\plugin\ParamConverter\PluginTypeConverter
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->pluginTypeManager = $this->prophesize(PluginTypeManagerInterface::class);

    $this->sut = new PluginTypeConverter($this->pluginTypeManager->reveal());
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
      'plugin.plugin_type' => [],
    ]];
    $data['applies-because-explicitly-enabled'] = [TRUE, [
      'plugin.plugin_type' => [
        'enabled' => TRUE,
      ],
    ]];
    $data['applies-not-because-disabled'] = [FALSE, [
      'plugin.plugin_type' => [
        'enabled' => FALSE,
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
      'plugin.plugin_type' => [],
    ];
    $name = 'foo_bar';
    $defaults = [];

    $plugin_type = $this->prophesize(PluginTypeInterface::class);

    $this->pluginTypeManager->getPluginType($plugin_type_id)->willReturn($plugin_type);

    $original_error_reporting = error_reporting();
    error_reporting($original_error_reporting & ~E_USER_WARNING);
    $this->assertNull($this->sut->convert($plugin_type_id, $definition, $name, $defaults));
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
  public function testConvertWithKnownPluginType() {
    $plugin_type_id = 'foo_bar.baz';
    $definition = [
      'plugin.plugin_type' => [],
    ];
    $name = 'foo_bar';
    $defaults = [];

    $plugin_type = $this->prophesize(PluginTypeInterface::class);;

    $this->pluginTypeManager->hasPluginType($plugin_type_id)->willReturn(TRUE);
    $this->pluginTypeManager->getPluginType($plugin_type_id)->willReturn($plugin_type->reveal());

    $this->assertSame($plugin_type->reveal(), $this->sut->convert($plugin_type_id, $definition, $name, $defaults));
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
  public function testConvertWithUnknownPluginType() {
    $plugin_type_id = 'foo_bar.baz';
    $definition = [
      'plugin.plugin_type' => [],
    ];
    $name = 'foo_bar';
    $defaults = [];

    $this->pluginTypeManager->hasPluginType($plugin_type_id)->willReturn(FALSE);

    $this->assertNull($this->sut->convert($plugin_type_id, $definition, $name, $defaults));
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
    // Leave out the "plugin.plugin_type" key.
    $definition = [];
    $plugin_type_id = 'foozaar.bazaar';
    $name = 'foo_bar';
    $defaults = [];

    $this->assertNull($this->sut->convert($plugin_type_id, $definition, $name, $defaults));
  }

}
