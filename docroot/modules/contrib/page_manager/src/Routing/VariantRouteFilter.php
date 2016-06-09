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
use Drupal\Core\Routing\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
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
   * Constructs a new VariantRouteFilter.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentPathStack $current_path) {
    $this->pageVariantStorage = $entity_type_manager->getStorage('page_variant');
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    $parameters = $route->getOption('parameters');
    return !empty($parameters['page_manager_page_variant']);
  }

  /**
   * {@inheritdoc}
   *
   * Invalid page manager routes will be removed. Routes not controlled by page
   * manager will be moved to the end of the collection. Once a valid page
   * manager route has been found, all other page manager routes will also be
   * removed.
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Only proceed if the collection is non-empty.
    if (!$collection->count()) {
      return $collection;
    }

    // Store the unaltered request attributes.
    $original_attributes = $request->attributes->all();

    // First get all routes and sort them by variant weight. Note that routes
    // without a weight will have an undefined order, they are ignored here.
    $routes = $collection->all();
    uasort($routes, [$this, 'routeWeightSort']);

    // Find the first route that is accessible.
    $accessible_route_name = NULL;
    foreach ($routes as $name => $route) {
      $attributes = $this->getRequestAttributes($route, $name, $request);
      // Add the enhanced attributes to the request.
      $request->attributes->add($attributes);
      if ($page_variant_id = $route->getDefault('page_manager_page_variant')) {
        if ($this->checkPageVariantAccess($page_variant_id)) {
          // Access granted, use this route. Do not restore request attributes
          // but keep those from this route by breaking out.
          $accessible_route_name = $name;
          break;
        }
      }

      // Restore the original request attributes, this must be done in the loop
      // or the request attributes will not be calculated correctly for the
      // next route.
      $request->attributes->replace($original_attributes);
    }

    // Because the sort order of $routes is unreliable for a route without a
    // variant weight, rely on the original order of $collection here.
    foreach ($collection as $name => $route) {
      if ($route->getDefault('page_manager_page_variant')) {
        if ($accessible_route_name !== $name) {
          // Remove all other page manager routes.
          $collection->remove($name);
        }
      }
      else {
        // This is not page manager route, move it to the end of the collection,
        // those will only be used if there is no accessible variant route.
        $collection->add($name, $route);
      }
    }

    return $collection;
  }

  /**
   * Sort callback for routes based on the variant weight.
   */
  protected function routeWeightSort(Route $a, Route $b) {
    $a_weight = $a->getDefault('page_manager_page_variant_weight');
    $b_weight = $b->getDefault('page_manager_page_variant_weight');
    if ($a_weight === $b_weight) {
      return 0;
    }
    elseif ($a_weight === NULL) {
      return 1;
    }
    elseif ($b_weight === NULL) {
      return -1;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
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
   * @return array
   *   An array of request attributes.
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
      $attributes = $enhancer->enhance($attributes, $request);
    }

    return $attributes;
  }

}
