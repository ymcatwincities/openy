<?php

namespace Drupal\panelizer\Menu;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 *
 */
class AddDefaultLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $this->pluginDefinition['route_parameters']['entity_type_id'] = $route_match->getCurrentRouteMatch()->getParameter('entity_type_id');
    $this->pluginDefinition['route_parameters']['bundle'] = $route_match->getCurrentRouteMatch()->getParameter('bundle');
    $this->pluginDefinition['route_parameters']['view_mode_name'] = $route_match->getCurrentRouteMatch()->getParameter('view_mode_name');
    return parent::getRouteParameters($route_match);
  }

}
