<?php

namespace Drupal\Tests\plugin\Unit\Plugin\views\filter;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\plugin\Plugin\views\filter\PluginId;
use Drupal\plugin\PluginDefinition\PluginDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\plugin\Plugin\views\filter\PluginId
 *
 * @group Plugin
 */
class PluginIdTest extends UnitTestCase {

  /**
   * The plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $pluginType;

  /**
   * The system under test.
   *
   * @var \Drupal\plugin\Plugin\views\filter\PluginId
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $plugin_type_id = 'foo_bar';
    $plugin_id = '';
    $plugin_definition = [
      'id' => $plugin_id,
    ];
    $configuration = [
      'plugin_type_id' => $plugin_type_id,
    ];

    $this->pluginType = $this->prophesize(PluginTypeInterface::class);

    $this->sut = new PluginId($configuration, $plugin_id, $plugin_definition, $this->pluginType->reveal());
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  public function testCreate() {
    $plugin_type_id = 'foo_bar';
    $plugin_id = '';
    $plugin_definition = [
      'id' => $plugin_id,
    ];
    $configuration = [
      'plugin_type_id' => $plugin_type_id,
    ];

    $plugin_type_manager = $this->prophesize(PluginTypeManagerInterface::class);
    $plugin_type_manager->getPluginType($plugin_type_id)->wilLReturn($this->pluginType->reveal());

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('plugin.plugin_type_manager')->willReturn($plugin_type_manager->reveal());

    $this->sut = PluginId::create($container->reveal(), $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(PluginId::class, $this->sut);
  }

  /**
   * @covers ::getCacheContexts
   * @covers ::getCacheableMetadata
   */
  public function testCacheContexts() {
    $plugin_manager_cache_contexts = ['dog', 'ball'];

    $plugin_manager = $this->prophesize(CacheableDependencyPluginManagerInterface::class);
    $plugin_manager->getCacheContexts()->willReturn($plugin_manager_cache_contexts);
    $plugin_manager->getCacheTags()->willReturn([]);
    $plugin_manager->getCacheMaxAge()->willReturn(0);

    $this->pluginType->getPluginManager()->willReturn($plugin_manager->reveal());

    // Temporarily disable asserts, because Cache::mergeContexts() calls
    // \Drupal::service(). See https://www.drupal.org/node/2720947.
    assert_options(ASSERT_ACTIVE, FALSE);
    $cache_contexts = $this->sut->getCacheContexts();
    $this->assertInternalType('array', $cache_contexts);
    foreach ($plugin_manager_cache_contexts as $plugin_manager_cache_context) {
      $this->assertTrue(in_array($plugin_manager_cache_context, $cache_contexts));
    }
    assert_options(ASSERT_ACTIVE, TRUE);
  }

  /**
   * @covers ::getCacheTags
   * @covers ::getCacheableMetadata
   */
  public function testCacheTags() {
    $plugin_manager_cache_tags = ['bar', 'foo'];

    $plugin_manager = $this->prophesize(CacheableDependencyPluginManagerInterface::class);
    $plugin_manager->getCacheContexts()->willReturn([]);
    $plugin_manager->getCacheTags()->willReturn($plugin_manager_cache_tags);
    $plugin_manager->getCacheMaxAge()->willReturn(0);

    $this->pluginType->getPluginManager()->willReturn($plugin_manager->reveal());

    $this->assertArraySubset($plugin_manager_cache_tags, $this->sut->getCacheTags());
  }

  /**
   * @covers ::getCacheMaxAge
   * @covers ::getCacheableMetadata
   *
   * @dataProvider provideCacheMaxAge
   */
  public function testCacheMaxAge($expected, $plugin_manager_max_age) {
    $plugin_manager = $this->prophesize(CacheableDependencyPluginManagerInterface::class);
    $plugin_manager->getCacheContexts()->willReturn([]);
    $plugin_manager->getCacheTags()->willReturn([]);
    $plugin_manager->getCacheMaxAge()->willReturn($plugin_manager_max_age);

    $this->pluginType->getPluginManager()->willReturn($plugin_manager->reveal());

    $this->assertSame($expected, $this->sut->getCacheMaxAge());
  }

  /**
   * Provides data to self::testCacheMaxAge().
   */
  public function provideCacheMaxAge() {
    $data = [];

    $data['plugin-manager-permanent'] = [Cache::PERMANENT, Cache::PERMANENT];
    $data['plugin-manager-never'] = [0, 0];
    $data['plugin-manager-limited'] = [7, 7];

    return $data;
  }

  /**
   * @covers ::getValueOptions
   * @covers ::getCacheableMetadata
   */
  public function testGetValueOptions() {
    $plugin_type_label = 'Foo to the bar';
    $this->pluginType->getLabel()->willReturn($plugin_type_label);

    $plugin_label_1 = 'Foo';
    $plugin_id_1 = 'aaa_foo';
    $plugin_id_2 = 'baz';
    $plugin_id_3 = 'qux';
    $plugin_label_4 = 'Bar';
    $plugin_id_4 = 'zzz_bar';

    // Values must be sorted naturally.
    $expected = [
      $plugin_id_4 => $plugin_label_4,
      $plugin_id_2 => $plugin_id_2,
      $plugin_id_1 => $plugin_label_1,
      $plugin_id_3 => $plugin_id_3,
    ];

    $plugin_definition_1 = $this->prophesize(PluginLabelDefinitionInterface::class);
    $plugin_definition_1->getId()->willReturn($plugin_id_1);
    $plugin_definition_1->getLabel()->willReturn($plugin_label_1);
    $plugin_definition_2 = $this->prophesize(PluginDefinitionInterface::class);
    $plugin_definition_2->getId()->willReturn($plugin_id_2);
    $plugin_definition_3 = $this->prophesize(PluginDefinitionInterface::class);
    $plugin_definition_3->getId()->willReturn($plugin_id_3);
    $plugin_definition_4 = $this->prophesize(PluginLabelDefinitionInterface::class);
    $plugin_definition_4->getId()->willReturn($plugin_id_4);
    $plugin_definition_4->getLabel()->willReturn($plugin_label_4);

    $plugin_definitions = [
      $plugin_id_1 => $plugin_definition_1,
      $plugin_id_2 => $plugin_definition_2,
      $plugin_id_3 => $plugin_definition_3,
      $plugin_id_4 => $plugin_definition_4,
    ];

    $plugin_manager = $this->prophesize(PluginManagerInterface::class);
    $plugin_manager->getDefinitions()->willReturn($plugin_definitions);

    $this->pluginType->ensureTypedPluginDefinition(Argument::any())->willReturnArgument();
    $this->pluginType->getPluginManager()->willReturn($plugin_manager);

    $this->assertSame($expected, $this->sut->getValueOptions());
  }


}

/**
 * Defines a plugin manager which is also a cacheable dependency.
 */
interface CacheableDependencyPluginManagerInterface extends PluginManagerInterface, CacheableDependencyInterface {
}
