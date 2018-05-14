<?php

namespace Drupal\search_api_solr\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Search Api Solr routes.
 */
class DevelRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route = $this->getDevelSolrRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.devel_solr", $route);
      }
    }
  }

  /**
   * Gets the devel Solr route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDevelSolrRoute(EntityTypeInterface $entity_type) {
    if ($devel_solr = $entity_type->getLinkTemplate('devel-solr')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($devel_solr);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\search_api_solr\Controller\DevelController::entitySolr',
          '_title' => 'Devel Solr',
        ])
        ->addRequirements([
          '_permission' => 'access devel information',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_devel_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', 100);
    return $events;
  }

}
