<?php

/**
 * @file
 * Contains \Drupal\page_manager\Routing\RouteAttributes.
 */

namespace Drupal\page_manager\Routing;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides utilities for interacting with route attributes.
 *
 * @todo Consider moving to CTools.
 */
class RouteAttributes {

  /**
   * Extracts all of the raw attributes from a path for a given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   * @param string $name
   *   The route name.
   * @param string $path
   *   A path.
   *
   * @return array
   *   An array of raw attributes for this path and route.
   */
  public static function extractRawAttributes(Route $route, $name, $path) {
    // See \Symfony\Component\Routing\Matcher\UrlMatcher::matchCollection().
    preg_match($route->compile()->getRegex(), $path, $matches);

    // See \Symfony\Component\Routing\Matcher\UrlMatcher::mergeDefaults().
    $attributes = $route->getDefaults();
    foreach ($matches as $key => $value) {
      if (!is_int($key)) {
        $attributes[$key] = $value;
      }
    }

    // See \Symfony\Cmf\Component\Routing\NestedMatcher\UrlMatcher::getAttributes().
    $attributes[RouteObjectInterface::ROUTE_OBJECT] = $route;
    $attributes[RouteObjectInterface::ROUTE_NAME] = $name;

    return $attributes;
  }

}
