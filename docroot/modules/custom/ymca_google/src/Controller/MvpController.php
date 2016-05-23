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

    /** @var GooglePush $pusher */
    $pusher = $entity_manager = \Drupal::service('ymca_google.pusher');

    $element = [
      '#markup' => $pusher->createTestEvent()
    ];
    return $element;
  }

}
