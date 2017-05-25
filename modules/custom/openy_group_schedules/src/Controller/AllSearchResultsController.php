<?php

namespace Drupal\openy_group_schedules\Controller;

use Drupal\Core\Controller\ControllerBase;
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
    // It catches cases with old arguments and redirect to this page without arguments.
    // @var  \Symfony\Component\HttpFoundation\Request $request
    $request = \Drupal::request();
    $query = $request->query->all();
    if ($request->getMethod() == 'GET') {
      return $this->redirect('openy_group_schedules.all_schedules_search');
    }

    // Get classes schedules.
    $schedule = \Drupal::service('openy_group_schedules.schedule_fetcher')->getSchedule();

    $formatted_results = $this->t('No results. Please try again.');
    if (!$empty_results = \Drupal::service('openy_group_schedules.schedule_fetcher')->isEmpty()) {
      $formatted_results = openy_group_schedules_schedule_table_layout($schedule);
    }

    $parameters = $query;

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#groupex-full-form-wrapper .groupex-results', $formatted_results));
    $response->addCommand(new InvokeCommand(NULL, 'groupExLocationAjaxAction', [$parameters]));
    return $response;
  }

}
