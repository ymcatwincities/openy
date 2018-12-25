<?php

namespace Drupal\Tests\plugin\Unit\PluginDefinition;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\plugin\PluginDefinition\PluginDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDefinition\PluginDefinition
 *
 * @group Plugin
 */
class PluginDefinitionTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDefinition\PluginDefinition
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->sut = $this->getMockForAbstractClass(PluginDefinition::class);
  }

  /**
   * @covers ::setId
   * @covers ::getId
   */
  public function testGetId() {
    $id = $this->randomMachineName();

    $this->assertSame($this->sut, $this->sut->setId($id));
    $this->assertSame($id, $this->sut->getId());
  }

  /**
   * @covers ::setClass
   * @covers ::getClass
   */
  public function testGetClass() {
    $class = get_class($this->getMock(PluginInspectionInterface::class));

    $this->assertSame($this->sut, $this->sut->setClass($class));
    $this->assertSame($class, $this->sut->getClass());
  }

  /**
   * @covers ::setProvider
   * @covers ::getProvider
   */
  public function testGetProvider() {
    $provider = $this->randomMachineName();

    $this->assertSame($this->sut, $this->sut->setProvider($provider));
    $this->assertSame($provider, $this->sut->getProvider());
  }

}
