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
    return [
      'form' => [
        '#lazy_builder' => [
          'form_builder:getForm',
          ['Drupal\ymca_groupex\Form\GroupexFormFull']
        ],
        '#create_placeholder' => TRUE,
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
