<?php

namespace Drupal\Tests\plugin\Unit\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\plugin\Controller\ListPlugins;
use Drupal\plugin\PluginDefinition\PluginDescriptionDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginOperationsProviderDefinitionInterface;
use Drupal\plugin\PluginOperationsProviderInterface;
use Drupal\plugin\PluginType\PluginType;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\plugin\Controller\ListPlugins
 *
 * @group Plugin
 */
class ListPluginsTest extends UnitTestCase {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $container;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\Controller\ListPlugins
   */
  protected $sut;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $classResolver;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = $this->prophesize(ContainerInterface::class);

    $this->classResolver = $this->getMock(ClassResolverInterface::class);

    $this->moduleHandler = $this->getMock(ModuleHandlerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new ListPlugins($this->stringTranslation, $this->moduleHandler, $this->classResolver);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->getMock(ContainerInterface::class);
    $map = [
      ['class_resolver', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->classResolver],
      ['module_handler', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->moduleHandler],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = ListPlugins::create($container);
    $this->assertInstanceOf(ListPlugins::class, $sut);
  }

  /**
   * @covers ::title
   */
  public function testTitle() {
    $class_resolver = $this->getMock(ClassResolverInterface::class);

    $typed_config_manager = $this->getMock(TypedConfigManagerInterface::class);
    $typed_config_manager->expects($this->atLeastOnce())
      ->method('hasConfigSchema')
      ->willReturn(TRUE);

    $plugin_type_id = $this->randomMachineName();
    $plugin_type_label = $this->randomMachineName();

    $plugin_type_definition = [
      'id' => $plugin_type_id,
      'label' => $plugin_type_label,
      'provider' => $this->randomMachineName(),
      'plugin_manager_service_id' => 'foo.bar',
    ];
    $plugin_type = new PluginType($plugin_type_definition, $this->container->reveal(), $this->stringTranslation, $class_resolver, $typed_config_manager);

    $title = $this->sut->title($plugin_type);
    $this->assertContains($plugin_type_label, (string) $title);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $plugin_manager = $this->getMock(PluginManagerInterface::class);

    $plugin_definition_id_a = $this->randomMachineName();
    $plugin_definition_label_a = $this->randomMachineName();
    $plugin_definition_a = $this->getMock(PluginLabelDefinitionInterface::class);
    $plugin_definition_a->expects($this->atLeastOnce())
      ->method('getId')
      ->willReturn($plugin_definition_id_a);
    $plugin_definition_a->expects($this->atLeastOnce())
      ->method('getLabel')
      ->willReturn($plugin_definition_label_a);

    $plugin_definition_id_b = $this->randomMachineName();
    $plugin_definition_description_b = $this->randomMachineName();
    $plugin_definition_b = $this->getMock(PluginDescriptionDefinitionInterface::class);
    $plugin_definition_b->expects($this->atLeastOnce())
      ->method('getId')
      ->willReturn($plugin_definition_id_b);
    $plugin_definition_b->expects($this->atLeastOnce())
      ->method('getDescription')
      ->willReturn($plugin_definition_description_b);

    $plugin_definition_id_c = $this->randomMachineName();
    $plugin_definition_operations_c = [
      'foo' => [
        'title' => 'Foo',
        'url' => new Url('foo'),
      ],
    ];
    $plugin_definition_operations_provider_c = $this->getMock(PluginOperationsProviderInterface::class);
    $plugin_definition_operations_provider_c->expects($this->atLeastOnce())
      ->method('getOperations')
      ->with($plugin_definition_id_c)
      ->willReturn($plugin_definition_operations_c);
    $plugin_definition_c = $this->getMock(PluginOperationsProviderDefinitionInterface::class);
    $plugin_definition_c->expects($this->atLeastOnce())
      ->method('getId')
      ->willReturn($plugin_definition_id_c);
    $plugin_definition_c->expects($this->atLeastOnce())
      ->method('getOperationsProviderClass')
      ->willReturn(get_class($plugin_definition_operations_provider_c));

    $plugin_definitions = [
      $plugin_definition_id_a => $plugin_definition_a,
      $plugin_definition_id_b => $plugin_definition_b,
      $plugin_definition_id_c => $plugin_definition_c,
    ];

    $this->classResolver->expects($this->atLeastOnce())
      ->method('getInstanceFromDefinition')
      ->with(get_class($plugin_definition_operations_provider_c))
      ->willReturn($plugin_definition_operations_provider_c);

    $plugin_manager->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);

    $plugin_type = $this->getMock(PluginTypeInterface::class);
    $plugin_type->expects($this->atLeastOnce())
      ->method('ensureTypedPluginDefinition')
      ->willReturnArgument(0);
    $plugin_type->expects($this->atLeastOnce())
      ->method('getPluginManager')
      ->willReturn($plugin_manager);

    $build = $this->sut->execute($plugin_type);

    $this->assertSame($plugin_definition_id_a, $build[$plugin_definition_id_a]['id']['#markup']);
    $this->assertSame($plugin_definition_label_a, (string) $build[$plugin_definition_id_a]['label']['#markup']);
    $this->assertNull($build[$plugin_definition_id_a]['description']['#markup']);
    $this->assertSame([], $build[$plugin_definition_id_a]['operations']['#links']);

    $this->assertSame($plugin_definition_id_b, $build[$plugin_definition_id_b]['id']['#markup']);
    $this->assertEmpty($build[$plugin_definition_id_b]['label']['#markup']);
    $this->assertSame($plugin_definition_description_b, (string) $build[$plugin_definition_id_b]['description']['#markup']);
    $this->assertSame([], $build[$plugin_definition_id_b]['operations']['#links']);

    $this->assertSame($plugin_definition_id_b, $build[$plugin_definition_id_b]['id']['#markup']);
    $this->assertEmpty($build[$plugin_definition_id_b]['label']['#markup']);
    $this->assertSame($plugin_definition_description_b, (string) $build[$plugin_definition_id_b]['description']['#markup']);
    $this->assertSame($plugin_definition_operations_c, $build[$plugin_definition_id_c]['operations']['#links']);
  }

}
