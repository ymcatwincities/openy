<?php
/**
 * @file
 * Contains Drupal\globalredirect\RedirectChecker.
 */

namespace Drupal\globalredirect;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Redirect checker class.
 */
class RedirectChecker {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * @param ConfigFactoryInterface $config
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The access manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider service.
   */
  public function __construct(ConfigFactoryInterface $config, AccessManager $access_manager, AccountInterface $account, RouteProviderInterface $route_provider) {
    $this->config = $config->get('globalredirect.settings');
    $this->accessManager = $access_manager;
    $this->account = $account;
    $this->routeProvider = $route_provider;
  }

  /**
   * Checks access to the route.
   *
   * @param string $route_name
   *   The current route name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return bool
   *   TRUE if access is granted.
   */
  public function canRedirect($route_name, Request $request) {
    $do_redirect = TRUE;

    /** @var \Symfony\Component\Routing\Route $route */
    $route = $this->routeProvider->getRouteByName($route_name);

    if ($this->config->get('access_check')) {
      $do_redirect &= $this->accessManager->check($route, $request, $this->account);
    }

    if ($this->config->get('ignore_admin_path')) {
      $do_redirect &= !(bool) $route->getOption('_admin_route');
    }

    return $do_redirect;
  }
}
