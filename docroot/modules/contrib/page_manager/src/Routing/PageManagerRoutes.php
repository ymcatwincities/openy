<?php

/**
 * @file
 * Contains \Drupal\page_manager\Routing\PageManagerRoutes.
 */

namespace Drupal\page_manager\Routing;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\page_manager\PageInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for page entities.
 */
class PageManagerRoutes extends RouteSubscriberBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a new PageManagerRoutes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->entityStorage = $entity_type_manager->getStorage('page');
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityStorage->loadMultiple() as $entity_id => $entity) {
      /** @var \Drupal\page_manager\PageInterface $entity */

      // If the page is disabled skip making a route for it.
      if (!$entity->status() || !$entity->getVariants()) {
        continue;
      }

      $parameters = [];
      $requirements = [];
      if ($route_name = $this->findPageRouteName($entity, $collection)) {
        $this->cacheTagsInvalidator->invalidateTags(["page_manager_route_name:$route_name"]);

        $collection_route = $collection->get($route_name);
        $path = $collection_route->getPath();
        $parameters = $collection_route->getOption('parameters') ?: [];
        $requirements = $collection_route->getRequirements();

        $collection->remove($route_name);
      }
      else {
        $route_name = "page_manager.page_view_$entity_id";
        $path = $entity->getPath();
      }

      // Add in configured parameters.
      foreach ($entity->getParameters() as $parameter_name => $parameter) {
        if (!empty($parameter['type'])) {
          $parameters[$parameter_name]['type'] = $parameter['type'];
        }
      }

      $parameters['page_manager_page_variant']['type'] = 'entity:page_variant';
      $parameters['page_manager_page']['type'] = 'entity:page';
      $requirements['_page_access'] = 'page_manager_page.view';

      $page_id = $entity->id();
      $first = TRUE;
      foreach ($entity->getVariants() as $variant_id => $variant) {
        // Construct and add a new route.
        $route = new Route(
          $path,
          [
            '_entity_view' => 'page_manager_page_variant',
            '_title' => $entity->label(),
            'page_manager_page_variant' => $variant_id,
            'page_manager_page' => $page_id,
            'page_manager_page_variant_weight' => $variant->getWeight(),
            // When adding multiple variants, the variant ID is added to the
            // route name. In order to convey the base route name for this set
            // of variants, add it as a parameter.
            'base_route_name' => $route_name,
          ],
          $requirements,
          [
            'parameters' => $parameters,
            '_admin_route' => $entity->usesAdminTheme(),
          ]
        );
        $collection->add($first ? $route_name : $route_name . '_' . $variant_id, $route);
        $first = FALSE;
      }
    }
  }

  /**
   * Finds the overridden route name.
   *
   * @param \Drupal\page_manager\PageInterface $entity
   *   The page entity.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   *
   * @return string|null
   *   Either the route name if this is overriding an existing path, or NULL.
   */
  protected function findPageRouteName(PageInterface $entity, RouteCollection $collection) {
    // Get the stored page path.
    $path = $entity->getPath();

    // Loop through all existing routes to see if this is overriding a route.
    foreach ($collection->all() as $name => $collection_route) {
      // Find all paths which match the path of the current display.
      $route_path = $collection_route->getPath();
      $route_path_outline = RouteCompiler::getPatternOutline($route_path);

      // Match either the path or the outline, e.g., '/foo/{foo}' or '/foo/%'.
      if ($path === $route_path || $path === $route_path_outline) {
        // Return the overridden route name.
        return $name;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after EntityRouteAlterSubscriber.
    $events[RoutingEvents::ALTER][] = ['onAlterRoutes', -160];
    return $events;
  }

}
