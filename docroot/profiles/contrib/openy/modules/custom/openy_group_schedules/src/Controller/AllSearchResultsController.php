<?php

namespace Drupal\openy_group_schedules\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Implements AllSearchResultsController.
 */
class AllSearchResultsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Gropex pro schedule fetcher
   *
   * @var \Drupal\openy_group_schedules\GroupexScheduleFetcher
   */
  protected $scheduleFetcher;

  /**
   * Constructs All Search Results.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   */
  public function __construct(Request $request, $scheduleFetcher) {
    $this->request = $request;
    $this->scheduleFetcher = $scheduleFetcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('openy_group_schedules.schedule_fetcher')
    );
  }

  /**
   * Show the page.
   */
  public function pageView() {
    // It catches cases with old arguments and redirect to this page without arguments.
    $query = $this->request->query->all();
    if ($this->request->getMethod() == 'GET') {
      return $this->redirect('openy_group_schedules.all_schedules_search');
    }

    // Get classes schedules.
    $schedule = $this->scheduleFetcher->getSchedule();

    $formatted_results = $this->t('No results. Please try again.');
    if (!$empty_results = $this->scheduleFetcher->isEmpty()) {
      $formatted_results = openy_group_schedules_schedule_table_layout($schedule);
    }

    $parameters = $query;

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#groupex-full-form-wrapper .groupex-results', $formatted_results));
    $response->addCommand(new InvokeCommand(NULL, 'groupExLocationAjaxAction', [$parameters]));
    return $response;
  }

}
