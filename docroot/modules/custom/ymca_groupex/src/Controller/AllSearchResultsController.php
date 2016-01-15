<?php

/**
 * @file
 * Contains \Drupal\ymca_groupex\Controller\AllSearchResultsController.
 */

namespace Drupal\ymca_groupex\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Implements AllSearchResultsController.
 */
class AllSearchResultsController extends ControllerBase {

  /**
   * Show the page.
   */
  public function pageView() {
    $query = \Drupal::request()->query->all()['query'];

    $schedule = ymca_groupex_schedule_layout($query);
    $form = $this->formBuilder()->getForm('Drupal\ymca_groupex\Form\GroupexFormFullRefine', $query);

    return [
      'form' => $form,
      'schedule' => $schedule,
    ];
  }

}
