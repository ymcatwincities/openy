<?php

namespace Drupal\ymca_groupex\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

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

    $formatted_results = ymca_groupex_schedule_table_layout($schedule);

    $parameters = $query;

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#groupex-full-form-wrapper .groupex-results', $formatted_results));
    $response->addCommand(new InvokeCommand(NULL, 'groupExLocationAjaxAction', array($parameters)));
    return $response;
  }

}
