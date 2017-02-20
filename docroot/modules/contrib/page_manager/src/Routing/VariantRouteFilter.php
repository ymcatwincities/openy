<?php

/**
 * @file
 * Contains \Drupal\page_manager\Routing\VariantRouteFilter.
 */

namespace Drupal\page_manager\Routing;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Filters variant routes.
 *
 * Each variant for a single page has a unique route for the same path, and
 * needs to be filtered. Here is where we run variant selection, which requires
 * gathering contexts.
 */
class VariantRouteFilter implements RouteFilterInterface {

  use RouteEnhancerCollectorTrait;

  /**
   * The page variant storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $pageVariantStorage;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new VariantRouteFilter.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path stack.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentPathStack $current_path, RequestStack $request_stack) {
    $this->pageVariantStorage = $entity_type_manager->getStorage('page_variant');
    $this->currentPath = $current_path;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   *
   * Ensures only one page manager route remains in the collection.
   */
  public function filter(RouteCollection $collection, Request $request) {
    $routes = $collection->all();
    // Only continue if at least one route has a page manager variant.
    if (!array_filter($routes, function (Route $route) {
      return $route->hasDefault('page_manager_page_variant');
    })) {
      return $collection;
    }

    // Sort routes by variant weight.
    $routes = $this->sortRoutes($routes);

    $variant_route_name = $this->getVariantRouteName($routes, $request);
    foreach ($routes as $name => $route) {
      if (!$route->hasDefault('page_manager_page_variant')) {
        continue;
      }

      // If this page manager route isn't the one selected, remove it.
      if ($variant_route_name !== $name) {
        unset($routes[$name]);
      }
      // If the selected route is overriding another route, remove the
      // overridden route.
      elseif ($overridden_route_name = $route->getDefault('overridden_route_name')) {
        unset($routes[$overridden_route_name]);
      }
    }

    // Create a new route collection by iterating over the sorted routes, using
    // the overridden_route_name if available.
    $result_collection = new RouteCollection();
    foreach ($routes as $name => $route) {
      $overridden_route_name = $route->getDefault('overridden_route_name') ?: $name;
      $result_collection->add($overridden_route_name, $route);
    }
    return $result_collection;
  }

  /**
   * Gets the route name of the first valid variant.
   *
   * @param \Symfony\Component\Routing\Route[] $routes
   *   An array of sorted routes.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A current request.
   *
   * @return string|null
   *   A route name, or NULL if none are found.
   */
  protected function getVariantRouteName(array $routes, Request $request) {
    // Store the unaltered request attributes.
    $original_attributes = $request->attributes->all();
    foreach ($routes as $name => $route) {
      if (!$page_variant_id = $route->getDefault('page_manager_page_variant')) {
        continue;
      }

      if ($attributes = $this->getRequestAttributes($route, $name, $request)) {
        // Use the overridden route name if available.
        $attributes[RouteObjectInterface::ROUTE_NAME] = $route->getDefault('overridden_route_name') ?: $name;
        // Add the enhanced attributes to the request.
        $request->attributes->add($attributes);
        $this->requestStack->push($request);

        if ($this->checkPageVariantAccess($page_variant_id)) {
          $this->requestStack->pop();
          return $name;
        }

        // Restore the original request attributes, this must be done in the loop
        // or the request attributes will not be calculated correctly for the
        // next route.
        $request->attributes->replace($original_attributes);
        $this->requestStack->pop();
      }
    }
  }

  /**
   * Sorts routes based on the variant weight.
   *
   * @param \Symfony\Component\Routing\Route[] $unsorted_routes
   *   An array of unsorted routes.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of sorted routes.
   */
  protected function sortRoutes(array $unsorted_routes) {
    // Create a mapping of route names to their weights.
    $weights_by_key = array_map(function (Route $route) {
      return $route->getDefault('page_manager_page_variant_weight') ?: 0;
    }, $unsorted_routes);

    // Create an array holding the route names to be sorted.
    $keys = array_keys($unsorted_routes);

    // Sort $keys first by the weights and then by the original order.
    array_multisort($weights_by_key, array_keys($keys), $keys);

    // Return the routes using the sorted order of $keys.
    return array_replace(array_combine($keys, $keys), $unsorted_routes);
  }

  /**
   * Checks access of a page variant.
   *
   * @param string $page_variant_id
   *   The page variant ID.
   *
   * @return bool
   *   TRUE if the route is valid, FALSE otherwise.
   */
  protected function checkPageVariantAccess($page_variant_id) {
    /** @var \Drupal\page_manager\PageVariantInterface $variant */
    $variant = $this->pageVariantStorage->load($page_variant_id);

    try {
      $access = $variant && $variant->access('view');
    }
    // Since access checks can throw a context exception, consider that as
    // a disallowed variant.
    catch (ContextException $e) {
      $access = FALSE;
    }

    return $access;
  }

  /**
   * Prepares the request attributes for use by the selection process.
   *
   * This is be done because route filters run before request attributes are
   * populated.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param string $name
   *   The route name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array|false
   *   An array of request attributes or FALSE if any route enhancers fail.
   */
  protected function getRequestAttributes(Route $route, $name, Request $request) {
    // Extract the raw attributes from the current path. This performs the same
    // functionality as \Drupal\Core\Routing\UrlMatcher::finalMatch().
    $path = $this->currentPath->getPath($request);
    $raw_attributes = RouteAttributes::extractRawAttributes($route, $name, $path);
    $attributes = $request->attributes->all();
    $attributes = NestedArray::mergeDeep($attributes, $raw_attributes);

    // Run the route enhancers on the raw attributes. This performs the same
    // functionality as \Symfony\Cmf\Component\Routing\DynamicRouter::match().
    foreach ($this->getRouteEnhancers() as $enhancer) {
      try {
        $attributes = $enhancer->enhance($attributes, $request);
      }
      catch (\Exception $e) {
        return FALSE;
      }
    }

    return $attributes;
  }

}
