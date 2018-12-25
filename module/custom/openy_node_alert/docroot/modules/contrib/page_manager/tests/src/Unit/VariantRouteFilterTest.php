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
use Symfony\Component\HttpFoundation\RequestStack;
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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->pageVariantStorage = $this->prophesize(ConfigEntityStorageInterface::class);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getStorage('page_variant')
      ->willReturn($this->pageVariantStorage);
    $this->currentPath = $this->prophesize(CurrentPathStack::class);
    $this->requestStack = new RequestStack();

    $this->routeFilter = new VariantRouteFilter($this->entityTypeManager->reveal(), $this->currentPath->reveal(), $this->requestStack);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // The request stack begins empty, ensure it is empty after filtering.
    $this->assertNull($this->requestStack->getCurrentRequest());
    parent::tearDown();
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
   * @covers ::getVariantRouteName
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
   * @covers ::getVariantRouteName
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
   * @covers ::getVariantRouteName
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
   * @covers ::getVariantRouteName
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
   * @covers ::getVariantRouteName
   */
  public function testFilterAllowedAccessTwoRoutes() {
    $route_collection = new RouteCollection();
    $request = new Request();

    $route1 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant_1', 'page_manager_page_variant_weight' => 0]);
    $route2 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant_2', 'page_manager_page_variant_weight' => 2]);
    // Add route2 first to ensure that the routes get sorted by weight.
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
      'page_manager_page_variant_weight' => 0,
      '_route_object' => $route1,
      '_route' => 'route_1',
    ];
    $this->assertSame($expected_attributes, $request->attributes->all());
  }

  /**
   * @covers ::filter
   * @covers ::getVariantRouteName
   */
  public function testFilterAllowedAccessSecondRoute() {
    $route_collection = new RouteCollection();
    $request = new Request();

    $route1 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant_1', 'page_manager_page_variant_weight' => 1]);
    $defaults = [
      'page_manager_page_variant' => 'variant_2',
      'page_manager_page_variant_weight' => 2,
      'overridden_route_name' => 'overridden_route_name_for_selected_route',
    ];
    $route2 = new Route('/path/with/{slug}', $defaults);
    // Add route2 first to ensure that the routes get sorted by weight.
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
    $expected = ['overridden_route_name_for_selected_route' => $route2];
    $this->assertSame($expected, $result->all());
    $expected_attributes = $defaults + [
      '_route_object' => $route2,
      '_route' => 'overridden_route_name_for_selected_route',
    ];
    $this->assertSame($expected_attributes, $request->attributes->all());
  }

  /**
   * @covers ::filter
   * @covers ::getVariantRouteName
   * @covers ::sortRoutes
   *
   * Tests when the first page_manager route is allowed, but other
   * non-page_manager routes are also present.
   */
  public function testFilterAllowedAccessFirstRoute() {
    $route_collection = new RouteCollection();
    $request = new Request();

    // The selected route specifies a different base route.
    $defaults = [
      'page_manager_page_variant' => 'variant1',
      'page_manager_page_variant_weight' => -2,
      'overridden_route_name' => 'route_1',
    ];
    $route1 = new Route('/path/with/{slug}');
    $route2 = new Route('/path/with/{slug}', $defaults);
    $route3 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant2', 'page_manager_page_variant_weight' => -1]);
    $route4 = new Route('/path/with/{slug}');
    // Add routes in different order to test sorting.
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
    $expected = ['route_1' => $route2, 'route_4' => $route4];
    $this->assertSame($expected, $result->all());
    $expected_attributes = $defaults + [
      '_route_object' => $route2,
      '_route' => 'route_1',
    ];
    $this->assertSame($expected_attributes, $request->attributes->all());
  }

  /**
   * @covers ::filter
   * @covers ::getVariantRouteName
   * @covers ::getRequestAttributes
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
   * @covers ::filter
   * @covers ::getVariantRouteName
   * @covers ::getRequestAttributes
   */
  public function testFilterRequestAttributesException() {
    $route_collection = new RouteCollection();
    $original_attributes = ['foo' => 'bar', 'slug' => 2];
    $request = new Request([], [], $original_attributes);

    $route = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'a_variant']);
    $route_collection->add('a_route', $route);

    $page_variant = $this->prophesize(PageVariantInterface::class);
    $page_variant->access('view')->willReturn(TRUE);

    $this->currentPath->getPath($request)->willReturn('/path/with/1');
    $this->pageVariantStorage->load('a_variant')->willReturn($page_variant->reveal());

    $route_enhancer = $this->prophesize(RouteEnhancerInterface::class);
    $this->routeFilter->addRouteEnhancer($route_enhancer->reveal());
    $expected_enhance_attributes = [
      'foo' => 'bar',
      'slug' => '1',
      'page_manager_page_variant' => 'a_variant',
      '_route_object' => $route,
      '_route' => 'a_route',
    ];
    $route_enhancer->enhance($expected_enhance_attributes, $request)->willThrow(new \Exception('A route enhancer failed'));

    $result = $this->routeFilter->filter($route_collection, $request);
    $this->assertEmpty($result->all());
    $this->assertSame($original_attributes, $request->attributes->all());
  }

  /**
   * @covers ::filter
   * @covers ::sortRoutes
   */
  public function testFilterPreservingBaseRouteName() {
    $route_collection = new RouteCollection();
    $request = new Request();

    // Add routes in different order to also test order preserving.
    $route1 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant1', 'page_manager_page_variant_weight' => -10, 'overridden_route_name' => 'preserved_route_name']);
    $route2 = new Route('/path/with/{slug}', ['page_manager_page_variant' => 'variant2', 'page_manager_page_variant_weight' => -5]);
    $route3 = new Route('/path/with/{slug}', []);
    $route4 = new Route('/path/with/{slug}', []);
    $route_collection->add('route_4', $route4);
    $route_collection->add('route_3', $route3);
    $route_collection->add('route_1', $route1);
    $route_collection->add('route_2', $route2);

    $page_variant1 = $this->prophesize(PageVariantInterface::class);
    $page_variant1->access('view')->willReturn(TRUE);
    $page_variant2 = $this->prophesize(PageVariantInterface::class);
    $page_variant2->access('view')->willReturn(FALSE);

    $this->currentPath->getPath($request)->willReturn('');
    $this->pageVariantStorage->load('variant1')->willReturn($page_variant1->reveal())->shouldBeCalled();
    $this->pageVariantStorage->load('variant2')->shouldNotBeCalled();

    $result = $this->routeFilter->filter($route_collection, $request);

    $expected = ['preserved_route_name' => $route1, 'route_4' => $route4, 'route_3' => $route3];
    $this->assertSame($expected, $result->all());
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
