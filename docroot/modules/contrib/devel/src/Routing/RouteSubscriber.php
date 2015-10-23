<?php

/**
 * @file
 * Contains \Drupal\devel\Routing\RouteSubscriber.
 */

namespace Drupal\devel\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Devel routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {

      if ($entity_type->hasLinkTemplate('devel-load') || $entity_type->hasLinkTemplate('devel-render')) {

        $options = array(
          '_admin_route' => TRUE,
          '_devel_entity_type_id' => $entity_type_id,
          'parameters' => array(
            $entity_type_id => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
        );

        if ($devel_load = $entity_type->getLinkTemplate('devel-load')) {
          $route = new Route(
            $devel_load,
            array(
              '_controller' => '\Drupal\devel\Controller\DevelController::entityLoad',
              '_title' => 'Devel Load',
            ),
            array('_permission' => 'access devel information'),
            $options
          );

          $collection->add("entity.$entity_type_id.devel_load", $route);
        }

        if ($devel_render = $entity_type->getLinkTemplate('devel-render')) {
          $route = new Route(
            $devel_render,
            array(
              '_controller' => '\Drupal\devel\Controller\DevelController::entityRender',
              '_title' => 'Devel Render',
            ),
            array('_permission' => 'access devel information'),
            $options
          );

          $collection->add("entity.$entity_type_id.devel_render", $route);
        }
      }
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
