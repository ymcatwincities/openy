<?php

namespace Drupal\Tests\plugin\Unit\PluginDefinition;

use Drupal\plugin\PluginDefinition\PluginCategoryDefinitionTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDefinition\PluginCategoryDefinitionTrait
 *
 * @group Plugin
 */
class PluginCategoryDefinitionTraitTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDefinition\PluginCategoryDefinitionTrait
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->sut = $this->getMockForTrait(PluginCategoryDefinitionTrait::class);
  }

  /**
   * @covers ::setCategory
   * @covers ::getCategory
   */
  public function testGetCategory() {
    $category = $this->randomMachineName();

    $this->assertSame($this->sut, $this->sut->setCategory($category));
    $this->assertSame($category, $this->sut->getCategory());
  }

}
