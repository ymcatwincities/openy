<?php

namespace Drupal\ymca_personify\PageCache;

use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache policy for Personify pages.
 */
class Personify implements ResponsePolicyInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a deny node preview page cache policy.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    $routes = [
      'ymca_personify.personify_login',
      'ymca_personify.personify_auth',
      'ymca_personify.personify_account',
      'ymca_personify.personify_signout',
    ];

    if (in_array($this->routeMatch->getRouteName(), $routes)) {
      return static::DENY;
    }
  }

}
