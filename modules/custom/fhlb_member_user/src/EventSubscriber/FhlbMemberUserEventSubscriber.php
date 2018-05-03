<?php

namespace Drupal\fhlb_member_user\EventSubscriber;

use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\fhlb_user_roles\FhlbUserRoles;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides fhlb_member_user events.
 */
class FhlbMemberUserEventSubscriber implements EventSubscriberInterface {

  /**
   * The Route Match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The Current Drupal User.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Service path.matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * FhlbMemberUserEventSubscriber constructor.
   */
  public function __construct(RouteMatchInterface $routeMatch, AccountProxyInterface $currentUser, PathMatcherInterface $pathMatcher) {
    $this->routeMatch = $routeMatch;
    $this->currentUser = $currentUser;
    $this->pathMatcher = $pathMatcher;
  }

  /**
   * Define redirect for the current user.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    if (in_array(FhlbUserRoles::USER_ROLE_MEMBER_ADMIN, $this->currentUser->getRoles())) {
      $currentRoute = $this->routeMatch->getRouteName();
      $redirectRoute = NULL;

      $userRoutes = [
        'user.logout',
        'user.login',
        'entity.member_user.add_form',
        'entity.member_user.edit_form',
        'entity.member_user.delete_form',
      ];

      $params = [
        'absolute' => TRUE,
      ];

      $is_frontpage = $this->pathMatcher->isFrontPage();
      if (!in_array($currentRoute, $userRoutes) && !$is_frontpage) {
        $redirectRoute = '<front>';
      }

      if ($redirectRoute) {
        $redirectUrl = Url::fromRoute($redirectRoute, [], $params)->toString();
        $response = new RedirectResponse($redirectUrl, 301);
        $response->send();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['checkForRedirection', 30];
    return $events;
  }

}
