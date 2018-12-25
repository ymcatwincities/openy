<?php

namespace Drupal\entity_clone\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Entity Clone routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

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
      if ($route = $this->getEntityCloneRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.clone_form", $route);
      }
    }
  }

  /**
   * Gets the entity_clone route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityCloneRoute(EntityTypeInterface $entity_type) {
    if ($clone_form = $entity_type->getLinkTemplate('clone-form')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($clone_form);
      $route
        ->addDefaults([
          '_form' => '\Drupal\entity_clone\Form\EntityCloneForm',
          '_title' => 'Clone ' . $entity_type->getLabel(),
        ])
        ->addRequirements([
          '_permission' => 'clone ' . $entity_type->id() . ' entity',
        ])
        ->setOption('_entity_clone_entity_type_id', $entity_type_id)
        ->setOption('_admin_route', TRUE)
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
    $events[RoutingEvents::ALTER] = 'onAlterRoutes';
    return $events;
  }

}
