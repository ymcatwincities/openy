<?php

namespace Drupal\Tests\plugin\Unit;

use Drupal\plugin\PluginDefinition\PluginHierarchyDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;
use Drupal\plugin\PluginDiscovery\TypedDiscoveryInterface;
use Drupal\plugin\PluginHierarchyTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginHierarchyTrait
 *
 * @group Plugin
 */
class PluginHierarchyTraitTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginHierarchyTrait
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->sut = $this->getMockForTrait(PluginHierarchyTrait::class);
  }

  /**
   * @covers ::buildPluginHierarchy
   * @covers ::buildPluginHierarchyLevel
   */
  public function testBuildPluginHierarchy() {
    $plugin_definition_id_a = $this->randomMachineName();
    $plugin_definition_label_a = 'foo';
    $plugin_definition_a = $this->getMock(PluginLabelDefinitionInterface::class);
    $plugin_definition_a->expects($this->any())
      ->method('getLabel')
      ->willReturn($plugin_definition_label_a);

    $plugin_definition_id_b = $this->randomMachineName();
    $plugin_definition_label_b = 'Bar';
    $plugin_definition_b = $this->getMock(PluginLabelDefinitionInterface::class);
    $plugin_definition_b->expects($this->any())
      ->method('getLabel')
      ->willReturn($plugin_definition_label_b);

    $plugin_definition_id_c = $this->randomMachineName();
    $plugin_definition_c = $this->getMock(PluginHierarchyDefinitionInterface::class);
    $plugin_definition_c->expects($this->any())
      ->method('getId')
      ->willReturn($plugin_definition_id_c);
    $plugin_definition_c->expects($this->any())
      ->method('getParentId')
      ->willReturn($plugin_definition_id_a);

    $plugin_discovery = $this->getMock(TypedDiscoveryInterface::class);
    $plugin_discovery->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([
        $plugin_definition_id_a => $plugin_definition_a,
        $plugin_definition_id_b => $plugin_definition_b,
        $plugin_definition_id_c => $plugin_definition_c,
      ]);

    $method = new \ReflectionMethod($this->sut, 'buildPluginHierarchy');
    $method->setAccessible(TRUE);
    // We need to suppress errors, because using mocks inside user comparison
    // functions always causes the "Array was modified by the user comparison
    // function" error. Because we check the output, we catch (most) problems
    // anyway.
    $hierarchy = @$method->invokeArgs($this->sut, array($plugin_discovery));

    $expected = [
      $plugin_definition_id_b => [],
      $plugin_definition_id_a => [
        $plugin_definition_id_c => [],
      ],
    ];

    $this->assertSame($expected, $hierarchy);
  }

}
