<?php

namespace Drupal\Tests\plugin\Unit\Plugin;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\plugin\Plugin\PluginOperationsProviderPluginManagerTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\Plugin\PluginOperationsProviderPluginManagerTrait
 *
 * @group Plugin
 */
class PluginOperationsProviderPluginManagerTraitTest extends UnitTestCase {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $classResolver;

  /**
   * The trait under test.
   *
   * @var \Drupal\plugin\Plugin\PluginOperationsProviderPluginManagerTrait
   */
  public $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->classResolver = $this->getMock(ClassResolverInterface::class);
  }

  /**
   * @covers ::getOperationsProvider
   */
  public function testGetOperationsProvider() {
    $plugin_definitions = array(
      'foo' => array(
        'id' => 'foo',
        'operations_provider' => PluginOperationsProviderPluginManagerTraitUnitTestOperationsProvider::class,
      ),
      'bar' => array(
        'id' => 'bar',
      ),
    );

    $operations_provider = new \stdClass();

    $this->sut = new PluginOperationsProviderPluginManagerTraitUnitTestOperationsProvider($this->classResolver, $plugin_definitions);

    $this->classResolver->expects($this->any())
      ->method('getInstanceFromDefinition')
      ->with($plugin_definitions['foo']['operations_provider'])
      ->willReturn($operations_provider);

    $this->assertSame($operations_provider, $this->sut->getOperationsProvider('foo'));
    $this->assertNull($this->sut->getOperationsProvider('bar'));
  }

}

class PluginOperationsProviderPluginManagerTraitUnitTestOperationsProvider {

  use PluginOperationsProviderPluginManagerTrait;

  /**
   * The plugin definitions.
   *
   * @var array
   */
  protected $pluginDefinitions = [];

  /**
   * Creates a new class instance.
   */
  public function __construct(ClassResolverInterface $class_resolver, array $plugin_definitions) {
    $this->classResolver = $class_resolver;
    $this->pluginDefinitions = $plugin_definitions;
  }

  /**
   * Returns a plugin definition.
   */
  protected function getDefinition($plugin_id) {
    return $this->pluginDefinitions[$plugin_id];
  }
}
