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

    // Get classes schedules.
    $fetcher = new \Drupal\ymca_groupex\GroupexScheduleFetcher($query);
    $schedule = $fetcher->getSchedule();

    // Are results empty?
    $empty_results = empty($schedule['days']) && empty($schedule['locations']) && empty($schedule['classes']);

    $formatted_results = ymca_groupex_schedule_layout($query, $schedule);
    $form = $this->formBuilder()->getForm('Drupal\ymca_groupex\Form\GroupexFormFullRefine', $query);

    return [
      '#form' => $form,
      '#schedule' => $formatted_results,
      '#empty_results' => $empty_results,
      '#theme' => 'groupex_all_search_results',
    ];
  }

}
