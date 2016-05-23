<?php

namespace Drupal\ymca_google\Controller;

/**
 * Implements SearchResultsController.
 */
class MvpController {

  /**
   * Show the page.
   */
  public function pageView() {
    include_once 'quickstart.php';
    $element = [
      '#markup' => $out
    ];
    return $element;
  }

}
