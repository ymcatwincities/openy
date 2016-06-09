<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\VariantRouteFilterTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\page_manager\PageVariantInterface;
use Drupal\page_manager\Routing\VariantRouteFilter;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\page_manager\Routing\VariantRouteFilter
 * @group PageManager
 */
class VariantRouteFilterTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The mocked page storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $pageVariantStorage;

  /**
   * The mocked current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $currentPath;

  /**
   * The route filter under test.
   *
   * @var \Drupal\page_manager\Routing\VariantRouteFilter
   */
  protected $routeFilter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->pageVariantStorage = $this->prophesize(ConfigEntityStorageInterface::class);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getStorage('page_variant')
      ->willReturn($this->pageVariantStorage);
    $this->currentPath = $this->prophesize(CurrentPathStack::class);

    $this->routeFilter = new VariantRouteFilter($this->entityTypeManager->reveal(), $this->currentPath->reveal());
  }

  /**
   * @covers ::applies
   *
   * @dataProvider providerTestApplies
   */
  public function testApplies($options, $expected) {
    $route = new Route('/path/with/{slug}', [], [], $options);
    $result = $this->routeFilter->applies($route);
    $this->assertSame($expected, $result);
  }

  public function providerTestApplies() {
    $data = [];
    $data['no_options'] = [[], FALSE];
    $data['with_options'] = [['parameters' => ['page_manager_page_variant' => TRUE]], TRUE];
    return $data;
  }

  /**
   * @covers ::filter
   */
  public function testFilterEmptyCollection() {
    $route_collection = new RouteCollection();
    $request = new Request();

    $this->currentPath->getPath($request)->shouldNotBeCalled();

    $result = $this->routeFilter->filter($route_collection, $request);
    $expected = [];
    $this->assertSame($expected, $result->all());
    $this->assertSame([], $request->attributes->all());
  }

  /**
   * @covers ::filter
   * @covers ::checkPageVariantAccess
   */
  public function testFilterContextException() {
    $route_collection = new RouteCollection();
    $request = new Request();

    $route = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'a_variant']);
    $route_collection->add('a_route', $route);

    $page_variant = $this->prophesize(PageVariantInterface::class);
    $page_variant->access('view')->willThrow(new ContextException());

    $this->currentPath->getPath($request)->willReturn('');
    $this->pageVariantStorage->load('a_variant')->willReturn($page_variant->reveal());

    $result = $this->routeFilter->filter($route_collection, $request);
    $expected = [];
    $this->assertSame($expected, $result->all());
    $this->assertSame([], $request->attributes->all());
  }

  /**
   * @covers ::filter
   */
  public function testFilterNonMatchingRoute() {
    $route_collection = new RouteCollection();
    $request = new Request();

    $route = new Route('/path/with/{slug}');
    $route_collection->add('a_route', $route);

    $this->currentPath->getPath($request)->willReturn('');

    $result = $this->routeFilter->filter($route_collection, $request);
    $expected = ['a_route' => $route];
    $this->assertSame($expected, $result->all());
    $this->assertSame([], $request->attributes->all());
  }

  /**
   * @covers ::filter
   * @covers ::checkPageVariantAccess
   */
  public function testFilterDeniedAccess() {
    $route_collection = new RouteCollection();
    $request = new Request();

    $route = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'a_variant']);
    $route_collection->add('a_route', $route);

    $page_variant = $this->prophesize(PageVariantInterface::class);
    $page_variant->access('view')->willReturn(FALSE);

    $this->currentPath->getPath($request)->willReturn('');
    $this->pageVariantStorage->load('a_variant')->willReturn($page_variant->reveal());

    $result = $this->routeFilter->filter($route_collection, $request);
    $expected = [];
    $this->assertSame($expected, $result->all());
    $this->assertSame([], $request->attributes->all());
  }

  /**
   * @covers ::filter
   * @covers ::checkPageVariantAccess
   */
  public function testFilterAllowedAccess() {
    $route_collection = new RouteCollection();
    $request = new Request();

    $route = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'a_variant']);
    $route_collection->add('a_route', $route);

    $page_variant = $this->prophesize(PageVariantInterface::class);
    $page_variant->access('view')->willReturn(TRUE);

    $this->currentPath->getPath($request)->willReturn('');
    $this->pageVariantStorage->load('a_variant')->willReturn($page_variant->reveal());

    $result = $this->routeFilter->filter($route_collection, $request);
    $expected = ['a_route' => $route];
    $this->assertSame($expected, $result->all());
    $expected_attributes = [
      'page_manager_page_variant' => 'a_variant',
      '_route_object' => $route,
      '_route' => 'a_route',
    ];
    $this->assertSame($expected_attributes, $request->attributes->all());
  }

  /**
   * @covers ::filter
   */
  public function testFilterAllowedAccessTwoRoutes() {
    $route_collection = new RouteCollection();
    $request = new Request();

    // Add route2 first to ensure that the routes are sorted by weight.
    $route1 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant_1', 'page_manager_page_variant_weight' => 1]);
    $route2 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant_2', 'page_manager_page_variant_weight' => 2]);
    $route_collection->add('route_2', $route2);
    $route_collection->add('route_1', $route1);

    $page_variant = $this->prophesize(PageVariantInterface::class);
    $page_variant->access('view')->willReturn(TRUE);

    $this->currentPath->getPath($request)->willReturn('');
    $this->pageVariantStorage->load('variant_1')->willReturn($page_variant->reveal());
    $this->pageVariantStorage->load('variant_2')->shouldNotBeCalled();

    $result = $this->routeFilter->filter($route_collection, $request);
    $expected = ['route_1' => $route1];
    $this->assertSame($expected, $result->all());
    $expected_attributes = [
      'page_manager_page_variant' => 'variant_1',
      'page_manager_page_variant_weight' => 1,
      '_route_object' => $route1,
      '_route' => 'route_1',
    ];
    $this->assertSame($expected_attributes, $request->attributes->all());
  }

  /**
   * @covers ::filter
   */
  public function testFilterAllowedAccessSecondRoute() {
    $route_collection = new RouteCollection();
    $request = new Request();

    // Add route2 first to ensure that the routes are sorted by weight.
    $route1 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant_1', 'page_manager_page_variant_weight' => 1]);
    $route2 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant_2', 'page_manager_page_variant_weight' => 2]);
    $route_collection->add('route_2', $route2);
    $route_collection->add('route_1', $route1);

    $page_variant1 = $this->prophesize(PageVariantInterface::class);
    $page_variant1->access('view')->willReturn(FALSE);
    $page_variant2 = $this->prophesize(PageVariantInterface::class);
    $page_variant2->access('view')->willReturn(TRUE);

    $this->currentPath->getPath($request)->willReturn('');
    $this->pageVariantStorage->load('variant_1')->willReturn($page_variant1->reveal())->shouldBeCalled();
    $this->pageVariantStorage->load('variant_2')->willReturn($page_variant2->reveal())->shouldBeCalled();

    $result = $this->routeFilter->filter($route_collection, $request);
    $expected = ['route_2' => $route2];
    $this->assertSame($expected, $result->all());
    $expected_attributes = [
      'page_manager_page_variant' => 'variant_2',
      'page_manager_page_variant_weight' => 2,
      '_route_object' => $route2,
      '_route' => 'route_2',
    ];
    $this->assertSame($expected_attributes, $request->attributes->all());
  }

  /**
   * @covers ::filter
   * @covers ::routeWeightSort
   *
   * Tests when the first page_manager route is allowed, but other
   * non-page_manager routes are also present.
   */
  public function testFilterAllowedAccessFirstRoute() {
    $route_collection = new RouteCollection();
    $request = new Request();

    // Add routes in different order to test sorting.
    $route1 = new Route('/path/with/{slug}');
    $route2 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant1', 'page_manager_page_variant_weight' => 1]);
    $route3 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant2', 'page_manager_page_variant_weight' => 2]);
    $route4 = new Route('/path/with/{slug}');
    $route_collection->add('route_3', $route3);
    $route_collection->add('route_2', $route2);
    $route_collection->add('route_1', $route1);
    $route_collection->add('route_4', $route4);

    $page_variant1 = $this->prophesize(PageVariantInterface::class);
    $page_variant1->access('view')->willReturn(TRUE);
    $page_variant2 = $this->prophesize(PageVariantInterface::class);
    $page_variant2->access('view')->willReturn(FALSE);

    $this->currentPath->getPath($request)->willReturn('');
    $this->pageVariantStorage->load('variant1')->willReturn($page_variant1->reveal())->shouldBeCalled();

    $result = $this->routeFilter->filter($route_collection, $request);
    $expected = ['route_2' => $route2, 'route_1' => $route1, 'route_4' => $route4];
    $this->assertSame($expected, $result->all());
    $expected_attributes = [
      'page_manager_page_variant' => 'variant1',
      'page_manager_page_variant_weight' => 1,
      '_route_object' => $route2,
      '_route' => 'route_2',
    ];
    $this->assertSame($expected_attributes, $request->attributes->all());
  }

  /**
   * @covers ::filter
   */
  public function testFilterRequestAttributes() {
    $route_collection = new RouteCollection();
    $request = new Request([], [], ['foo' => 'bar', 'slug' => 2]);

    $route = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'a_variant']);
    $route_collection->add('a_route', $route);

    $page_variant = $this->prophesize(PageVariantInterface::class);
    $page_variant->access('view')->willReturn(TRUE);

    $this->currentPath->getPath($request)->willReturn('/path/with/1');
    $this->pageVariantStorage->load('a_variant')->willReturn($page_variant->reveal());

    $route_enhancer = $this->prophesize(RouteEnhancerInterface::class);
    $this->routeFilter->addRouteEnhancer($route_enhancer->reveal());
    $result_enhance_attributes = $expected_enhance_attributes = [
      'foo' => 'bar',
      'slug' => '1',
      'page_manager_page_variant' => 'a_variant',
      '_route_object' => $route,
      '_route' => 'a_route',
    ];
    $result_enhance_attributes['slug'] = 'slug 1';
    $route_enhancer->enhance($expected_enhance_attributes, $request)->willReturn($result_enhance_attributes);

    $result = $this->routeFilter->filter($route_collection, $request);
    $expected = ['a_route' => $route];
    $this->assertSame($expected, $result->all());
    $expected_attributes = [
      'foo' => 'bar',
      'slug' => 'slug 1',
      'page_manager_page_variant' => 'a_variant',
      '_route_object' => $route,
      '_route' => 'a_route',
    ];
    $this->assertSame($expected_attributes, $request->attributes->all());
  }

  /**
   * @covers ::getRequestAttributes
   */
  public function testGetRequestAttributes() {
    $request = new Request();

    $route = new Route('/path/with/{slug}');
    $route_name = 'a_route';

    $this->currentPath->getPath($request)->willReturn('/path/with/1');

    $expected_attributes = ['slug' => 1, '_route_object' => $route, '_route' => $route_name];
    $route_enhancer = $this->prophesize(RouteEnhancerInterface::class);
    $route_enhancer->enhance($expected_attributes, $request)->willReturn(['slug' => 'slug 1']);
    $this->routeFilter->addRouteEnhancer($route_enhancer->reveal());

    $this->assertSame([], $request->attributes->all());

    $method = new \ReflectionMethod($this->routeFilter, 'getRequestAttributes');
    $method->setAccessible(TRUE);
    $attributes = $method->invoke($this->routeFilter, $route, $route_name, $request);

    $this->assertSame(['slug' => 'slug 1'], $attributes);
  }

}
