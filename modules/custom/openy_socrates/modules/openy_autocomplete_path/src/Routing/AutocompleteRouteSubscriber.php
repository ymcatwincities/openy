<?php

namespace Drupal\openy_autocomplete_path\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class AutocompleteRouteSubscriber.
 *
 * @package Drupal\openy_autocomplete_path\Routing
 */
class AutocompleteRouteSubscriber extends RouteSubscriberBase {
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\openy_autocomplete_path\Controller\EntityAutocompleteController::handleAutocomplete');
    }
  }
}
