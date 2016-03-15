<?php

namespace Drupal\ymca_groupex\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Implements AllSearchResultsController.
 */
class AllSearchResultsController extends ControllerBase {

  /**
   * Show the page.
   */
  public function pageView() {
    $query = \Drupal::request()->query->all();

    // Get classes schedules.
    $schedule = \Drupal::service('ymca_groupex.schedule_fetcher')->getSchedule();

    // Are results empty?
    $empty_results = \Drupal::service('ymca_groupex.schedule_fetcher')->isEmpty();

    $formatted_results = ymca_groupex_schedule_layout($schedule);
    $form = $this->formBuilder()->getForm('Drupal\ymca_groupex\Form\GroupexFormFullRefine', $query);

    $module_path = drupal_get_path('module', 'ymca_groupex');
    $image = Url::fromUri('base:' . $module_path . '/assets/endorsed_by_silverfit_sm.png');

    return [
      '#form' => $form,
      '#schedule' => $formatted_results,
      '#empty_results' => $empty_results,
      '#theme' => 'groupex_all_search_results',
      '#image' => $image,
      '#interval' => $query['filter_length'],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
