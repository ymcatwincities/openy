<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\RouteNameResponseSubscriberTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\page_manager\EventSubscriber\RouteNameResponseSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\page_manager\EventSubscriber\RouteNameResponseSubscriber
 * @group PageManager
 */
class RouteNameResponseSubscriberTest extends UnitTestCase {

  /**
   * @covers ::onResponse
   */
  public function testOnResponseCacheable() {
    $response = new CacheableResponse('');
    $event = $this->buildEvent($response);

    $route_name = 'the_route_name';
    $master_route_match = $this->prophesize(RouteMatchInterface::class);
    $master_route_match->getParameter('base_route_name')->willReturn(NULL);
    $master_route_match->getRouteName()->willReturn($route_name);
    $current_route_match = $this->prophesize(StackedRouteMatchInterface::class);
    $current_route_match->getMasterRouteMatch()->willReturn($master_route_match->reveal());

    $subscriber = new RouteNameResponseSubscriber($current_route_match->reveal());
    $subscriber->onResponse($event);

    $expected = ["page_manager_route_name:$route_name"];
    $this->assertSame($expected, $response->getCacheableMetadata()->getCacheTags());
  }

  /**
   * @covers ::onResponse
   */
  public function testOnResponseUncacheable() {
    $response = new Response('');
    $event = $this->buildEvent($response);

    $master_route_match = $this->prophesize(RouteMatchInterface::class);
    $master_route_match->getParameter()->shouldNotBeCalled();
    $master_route_match->getRouteName()->shouldNotBeCalled();
    $current_route_match = $this->prophesize(StackedRouteMatchInterface::class);
    $current_route_match->getMasterRouteMatch()->willReturn($master_route_match->reveal());

    $subscriber = new RouteNameResponseSubscriber($current_route_match->reveal());
    $subscriber->onResponse($event);
  }

  /**
   * @covers ::onResponse
   */
  public function testOnResponseCacheableWithBaseRouteName() {
    $response = new CacheableResponse('');
    $event = $this->buildEvent($response);

    $route_name = 'the_route_name';
    $master_route_match = $this->prophesize(RouteMatchInterface::class);
    $master_route_match->getParameter('base_route_name')->willReturn($route_name);
    $master_route_match->getRouteName()->shouldNotBeCalled();
    $current_route_match = $this->prophesize(StackedRouteMatchInterface::class);
    $current_route_match->getMasterRouteMatch()->willReturn($master_route_match->reveal());

    $subscriber = new RouteNameResponseSubscriber($current_route_match->reveal());
    $subscriber->onResponse($event);

    $expected = ["page_manager_route_name:$route_name"];
    $this->assertSame($expected, $response->getCacheableMetadata()->getCacheTags());
  }

  /**
   * Builds an event to wrap a response.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to be sent as the event payload.
   *
   * @return \Symfony\Component\HttpKernel\Event\FilterResponseEvent
   *   An event suitable for a KernelEvents::RESPONSE subscriber to process.
   */
  protected function buildEvent(Response $response) {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('');
    return new FilterResponseEvent($kernel->reveal(), $request, HttpKernelInterface::SUB_REQUEST, $response);
  }

}
