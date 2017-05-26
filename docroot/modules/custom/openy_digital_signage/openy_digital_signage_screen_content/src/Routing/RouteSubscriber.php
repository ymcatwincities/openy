<?php

namespace Drupal\openy_digital_signage_screen_content\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change controller for the Panel IPE.
    // Layouts.
    if ($route = $collection->get('panels_ipe.layouts')) {
      $route->setDefault('_controller', '\Drupal\openy_digital_signage_screen_content\Controller\OpenYDSPanelsIPEPageController::getLayouts');
    }
    if ($route = $collection->get('panels_ipe.block_plugins')) {
      $route->setDefault('_controller', '\Drupal\openy_digital_signage_screen_content\Controller\OpenYDSPanelsIPEPageController::getBlockPlugins');
    }
    if ($route = $collection->get('panels_ipe.block_content_types')) {
      $route->setDefault('_controller', '\Drupal\openy_digital_signage_screen_content\Controller\OpenYDSPanelsIPEPageController::getBlockContentTypes');
    }
  }

}
