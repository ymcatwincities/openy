<?php
/**
 * @file
 * Contains \Drupal\ymca_search_alter\Routing\RouteSubscriber.
 */

namespace Drupal\ymca_search_alter\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    foreach ($collection as &$item) {
      /** @var Route */
      if ($item->getPath() == '/search/results') {
        $item->setPath('/search_results');
      }
    }
//    // Change path '/user/login' to '/login'.
//    if ($route = $collection->get('user.login')) {
//      $route->setPath('/login');
//    }
//    // Always deny access to '/user/logout'.
//    // Note that the second parameter of setRequirement() is a string.
//    if ($route = $collection->get('user.logout')) {
//      $route->setRequirement('_access', 'FALSE');
//    }
  }

}
?>