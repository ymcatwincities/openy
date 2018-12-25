<?php

/**
 * @file
 * Contains \Drupal\views_advanced_routing\Routing\RouteSubscriber.
 */

namespace Drupal\views_advanced_routing\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\display\DisplayRouterInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\Routing\RouteCollection;

/**
 * Customizes Views routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->viewStorage = $entity_manager->getStorage('view');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $views = [];
    foreach (Views::getApplicableViews('uses_route') as $data) {
      list($view_id, $display_id) = $data;
      $views[$view_id][] = $display_id;
    }

    foreach ($views as $view_id => $displays) {
      /** @var \Drupal\views\Entity\View $view */
      $view = $this->viewStorage->load($view_id);
      foreach ($displays as $display_id) {
        if (($vex = $view->getExecutable()) && $vex instanceof ViewExecutable) {
          if ($vex->setDisplay($display_id) && $display = $vex->displayHandlers->get($display_id)) {
            if ($display instanceof DisplayRouterInterface) {
              $options = $display->getOption('display_extenders');
              if (isset($options['views_advanced_routing_route'])) {
                $route = $collection->get($display->getRouteName());
                $settings = $options['views_advanced_routing_route'];
                if ($route && !empty($settings['route'])) {
                  $route_info = $settings['route'];
                  $route_info += [
                    'defaults' => [],
                    'requirements' => [],
                    'options' => [],
                  ];
                  $route
                    ->addOptions($route_info['options'])
                    ->addRequirements($route_info['requirements'])
                    ->addDefaults($route_info['defaults']);
                  $collection->add($display->getRouteName(), $route);
                }
              }
            }
          }
          $vex->destroy();
        }
      }
    }
  }

}
