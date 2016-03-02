<?php

namespace Drupal\ymca_groupex\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Implements SearchResultsController.
 */
class AllSearchController extends ControllerBase {

  /**
   * Show the page.
   */
  public function pageView() {
    $form = \Drupal::formBuilder()->getForm('Drupal\ymca_groupex\Form\GroupexFormFull');
    return [
      'form' => $form,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
