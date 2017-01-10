<?php

namespace Drupal\panels\Storage;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Routing access for routes that depend on panels storage.
 */
class PanelsStorageAccess implements RoutingAccessInterface {

  /**
   * The Panels storage manager.
   *
   * @var \Drupal\panels\Storage\PanelsStorageManagerInterface
   */
  protected $panelsStorage;

  /**
   * Constructs a PanelsStorageAccess.
   *
   * @param \Drupal\panels\Storage\PanelsStorageManagerInterface $panels_storage
   *   The Panels storage manager.
   */
  public function __construct(PanelsStorageManagerInterface $panels_storage) {
    $this->panelsStorage = $panels_storage;
  }

  /**
   * Checks if the user has access to underlying storage for a Panels display.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $panels_storage_type = $route_match->getParameter('panels_storage_type');
    $panels_storage_id = $route_match->getParameter('panels_storage_id');
    $op = $route->getRequirement('_panels_storage_access');
    return $this->panelsStorage->access($panels_storage_type, $panels_storage_id, $op, $account);
  }

}
