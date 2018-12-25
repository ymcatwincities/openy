<?php

namespace Drupal\scheduler\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Checks access for displaying the scheduler list of scheduled nodes.
 */
class ScheduledListAccess implements AccessCheckInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a ScheduledListAccess object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasRequirement('_access_scheduler_content');
  }

  /**
   * Determine if the $account has access to the scheduled content list.
   *
   * The result will vary depending on whether the page being viewed is the user
   * profile page or the scheduled content admin overview.
   */
  public function access(AccountInterface $account) {
    // When viewing a user profile page routeMatch->getRawParameter('user')
    // returns the user's id. If not on a user page it returns NULL silently.
    $viewing_own_tab = $this->routeMatch->getRawParameter('user') == $account->id();

    // Users with 'schedule publishing of nodes' can see their own scheduled
    // content via a tab on their user page. Users with 'view scheduled content'
    // will be able to access the 'scheduled' tab for any user, and also access
    // the scheduled content overview page.
    $allowed = $account->hasPermission('view scheduled content')
      || ($viewing_own_tab && $account->hasPermission('schedule publishing of nodes'));
    return $allowed ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
