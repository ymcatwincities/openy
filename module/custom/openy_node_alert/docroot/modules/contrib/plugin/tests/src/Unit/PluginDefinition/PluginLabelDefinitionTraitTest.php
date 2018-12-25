<?php

namespace Drupal\Tests\plugin\Unit\PluginDefinition;

use Drupal\plugin\PluginDefinition\PluginLabelDefinitionTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDefinition\PluginLabelDefinitionTrait
 *
 * @group Plugin
 */
class PluginLabelDefinitionTraitTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDefinition\PluginLabelDefinitionTrait
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->sut = $this->getMockForTrait(PluginLabelDefinitionTrait::class);
  }

  /**
   * @covers ::setLabel
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $label = $this->randomMachineName();

    $this->assertSame($this->sut, $this->sut->setLabel($label));
    $this->assertSame($label, $this->sut->getLabel());
  }

}
