<?php

namespace Drupal\ymca_alters\Routing;

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
      switch ($item->getPath()) {
        case '/node/{node}':
          $item->setDefault('_controller', 'Drupal\ymca_alters\Controller\NodeViewController::view');
          break;

        case '/node/preview/{node_preview}/{view_mode_id}':
          $item->setDefault('_controller', '\Drupal\ymca_alters\Controller\YmcaNodePreviewController::view');
          break;

        case '/sitemap':
          $item->setPath('/sitemap_navigation');
          break;
      }
    }

    // Set Location views page to use admin theme.
    // view.[VIEW NAME].[DISPLAY NAME].
    $views = [
      'view.location_schedules.location_schedules_page',
      'view.location_wh.location_wh'
    ];
    foreach ($views as $view) {
      if ($route = $collection->get($view)) {
        $route->setOption('_admin_route', TRUE);
      }
    }

    // Replace page manager block edit form with our customization.
    if ($route = $collection->get('page_manager.variant_edit_block')) {
      $route->setDefault('_form', '\Drupal\ymca_alters\Form\YmcaVariantPluginEditBlockForm');
    }
  }

}
