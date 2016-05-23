<?php

namespace Drupal\ymca_google\Controller;

use Drupal\ymca_google\GooglePush;

/**
 * Implements SearchResultsController.
 */
class MvpController {

  /**
   * Show the page.
   */
  public function pageView() {
    //include_once 'quickstart.php';

    /** @var GooglePush $pusher */
    $pusher = $entity_manager = \Drupal::service('ymca_google.pusher');
    $pusher->createTestEvent();
    $element = [
      '#markup' => 'Hello user'
    ];
    return $element;
  }

}
