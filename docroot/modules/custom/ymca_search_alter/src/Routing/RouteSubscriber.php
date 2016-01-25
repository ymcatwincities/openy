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
      /** @var Route $item */
      if ($item->getPath() == '/search/results') {
        $item->setPath('/search_results');
        $item->setDefault('_controller', 'Drupal\ymca_search_alter\Controller\SearchController::view');
      }
    }
  }

}
?>