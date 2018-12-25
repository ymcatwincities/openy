<?php

namespace Drupal\panelizer\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field_ui\Access\ViewModeAccessCheck as FieldUIViewModeAccessCheck;
use Symfony\Component\Routing\Route;

/**
 * An access check as an adapter around field_ui's custom access check.
 */
class ViewModeAccessCheck implements AccessInterface {

  /**
   * The decorated ViewModeAccessCheck from field_ui.
   *
   * @var \Drupal\field_ui\Access\ViewModeAccessCheck
   */
  protected $accessCheck;

  /**
   * ViewModeAccessCheck constructor.
   *
   * @param \Drupal\field_ui\Access\ViewModeAccessCheck $access_check
   */
  public function __construct(FieldUIViewModeAccessCheck $access_check) {
    $this->accessCheck = $access_check;
  }

  /**
   * Adapt the panelizer defaults access check to correspond to field ui.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The original route definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route matched.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user's account.
   * @param string $machine_name
   *   The machine name of the panelizer default.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   * @throws \Exception
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, $machine_name) {
    $parts = explode('__', $machine_name);
    if (count($parts) != 4) {
      throw new \Exception('The provided machine_name is not well formed.');
    }
    list($entity_type_id, $bundle, $view_mode) = $parts;
    $defaults = [
      'entity_type_id' => $entity_type_id,
    ] + $route->getDefaults();
    $route->setDefaults($defaults);
    $route->setRequirement('_field_ui_view_mode_access', 'administer ' . $entity_type_id . ' display');
    return $this->accessCheck->access($route, $route_match, $account, $view_mode, $bundle);
  }

}
