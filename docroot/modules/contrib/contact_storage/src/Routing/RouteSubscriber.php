<?php

namespace Drupal\contact_storage\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use \Drupal\contact_storage\Controller\ContactStorageController;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change the contact_form controller.
    if ($route = $collection->get('entity.contact_form.canonical')) {
      $route->setDefault('_controller', ContactStorageController::class . '::contactSitePage');
    }
  }

}
