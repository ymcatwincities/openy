<?php

namespace Drupal\Tests\lazyloader\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\lazyloader\ThemePreprocess;

/**
 * @coversDefaultClass \Drupal\lazyloader\ThemePreprocess
 * @group lazyloader
 */
class ThemePreprocessTest extends \PHPUnit_Framework_TestCase {

  /**
   * @test
   */
  public function addsCacheTagToRenderArray() {
    $sut = new ThemePreprocess($this->getMock(ConfigFactoryInterface::class));

    $expected = ['#cache' => ['tags' => [0 => 'config:lazyloader.configuration']]];

    $this->assertEquals($expected, $sut->addCacheTags([]));
  }

}
