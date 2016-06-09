<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\RouteAttributesTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\page_manager\Routing\RouteAttributes;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\page_manager\Routing\RouteAttributes
 * @group PageManager
 */
class RouteAttributesTest extends UnitTestCase {

  /**
   * @covers ::extractRawAttributes
   *
   * @dataProvider providerTestExtractRawAttributes
   */
  public function testExtractRawAttributes(Route $route, $name, $path, array $expected) {
    $expected['_route_object'] = $route;
    $expected['_route'] = $name;
    $this->assertEquals($expected, RouteAttributes::extractRawAttributes($route, $name, $path));
  }

  public function providerTestExtractRawAttributes() {
    $data = [];
    $data['no-parameters'] = [new Route('/prefix/a'), 'a_route', '/prefix', []];
    $data['no-matching-parameters'] = [new Route('/prefix/{x}'), 'a_route', '/different-prefix/b', []];
    $data['matching-parameters'] = [new Route('/prefix/{x}'), 'a_route', '/prefix/b', ['x' => 'b']];
    $data['with-defaults'] = [new Route('/prefix/{x}', ['foo' => 'bar']), 'a_route', '/different-prefix/b', ['foo' => 'bar']];
    return $data;
  }

}
