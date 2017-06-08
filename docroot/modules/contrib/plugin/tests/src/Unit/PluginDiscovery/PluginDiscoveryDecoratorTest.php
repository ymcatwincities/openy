<?php

namespace Drupal\Tests\plugin\Unit\PluginDiscovery;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\plugin\PluginDiscovery\PluginDiscoveryDecorator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDiscovery\PluginDiscoveryDecorator
 *
 * @group Plugin
 */
class PluginDiscoveryDecoratorTest extends UnitTestCase {

  /**
   * The decorated discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $decoratedDiscovery;

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDiscovery\PluginDiscoveryDecorator
   */
  protected $sut;

  public function setUp() {
    $this->decoratedDiscovery = $this->getMock(DiscoveryInterface::class);

    $this->sut = new PluginDiscoveryDecorator($this->decoratedDiscovery);
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct() {
    $this->sut = new PluginDiscoveryDecorator($this->decoratedDiscovery);
    $this->assertInstanceOf(PluginDiscoveryDecorator::class, $this->sut);
  }

  /**
   * @covers ::getDefinitions
   * @covers ::processDecoratedDefinitions
   */
  public function testGetDefinitions() {
    $plugin_id_a = $this->randomMachineName();
    $plugin_definition_a = [
      'id' => $plugin_id_a,
    ];
    $plugin_id_b = $this->randomMachineName();
    $plugin_definition_b = [
      'id' => $plugin_id_b,
    ];
    $plugin_id_c = $this->randomMachineName();
    $plugin_definition_c = [
      'id' => $plugin_id_c,
    ];

    $plugin_definitions = [
      $plugin_id_a => $plugin_definition_a,
      $plugin_id_b => $plugin_definition_b,
      $plugin_id_c => $plugin_definition_c,
    ];

    $this->decoratedDiscovery->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);

    $this->assertEquals($plugin_definitions, $this->sut->getDefinitions());
  }

  /**
   * @covers ::clearCachedDefinitions
   */
  public function testClearCachedDefinitionsWithUncachedDecoratedDiscovery() {
    $this->decoratedDiscovery->expects($this->never())
      ->method('clearCachedDefinitions');
    $this->decoratedDiscovery->expects($this->exactly(2))
      ->method('getDefinitions')
      ->willReturn([]);

    // There are no cached definitions yet.
    $this->sut->getDefinitions();
    // This should return the cached definitions.
    $this->sut->getDefinitions();

    $this->sut->clearCachedDefinitions();
    // This should return newly built definitions.
    $this->sut->getDefinitions();
  }

  /**
   * @covers ::clearCachedDefinitions
   */
  public function testClearCachedDefinitionsWithCachedDecoratedDiscovery() {
    $this->decoratedDiscovery = $this->getMockForAbstractClass(PluginDiscoveryDecoratorTestCachedDiscovery::class);

    $this->sut = new PluginDiscoveryDecorator($this->decoratedDiscovery);

    $this->decoratedDiscovery->expects($this->once())
      ->method('clearCachedDefinitions');
    $this->decoratedDiscovery->expects($this->exactly(2))
      ->method('getDefinitions')
      ->willReturn([]);

    // There are no cached definitions yet.
    $this->sut->getDefinitions();
    // This should return the cached definitions.
    $this->sut->getDefinitions();

    $this->sut->clearCachedDefinitions();
    // This should return newly built definitions.
    $this->sut->getDefinitions();
  }

  /**
   * @covers ::useCaches
   */
  public function testUseCachesWithCachedDecoratedDiscovery() {
    $this->decoratedDiscovery = $this->getMockForAbstractClass(PluginDiscoveryDecoratorTestCachedDiscovery::class);

    $this->sut = new PluginDiscoveryDecorator($this->decoratedDiscovery);

    $this->decoratedDiscovery->expects($this->once())
      ->method('clearCachedDefinitions');
    $this->decoratedDiscovery->expects($this->exactly(3))
      ->method('getDefinitions')
      ->willReturn([]);

    // There are no cached definitions yet, so this should call the decorated
    // discovery.
    $this->sut->getDefinitions();
    // This should return the cached definitions.
    $this->sut->getDefinitions();

    $this->sut->useCaches(FALSE);
    // This should return newly built definitions, so this should call the
    // decorated discovery.
    $this->sut->getDefinitions();
    // This should return newly built definitions again, because we disabled
    // caching.
    $this->sut->getDefinitions();
  }

}

/**
 * Provides a dummy discovery that caches definitions.
 */
abstract class PluginDiscoveryDecoratorTestCachedDiscovery implements DiscoveryInterface, CachedDiscoveryInterface {
}
