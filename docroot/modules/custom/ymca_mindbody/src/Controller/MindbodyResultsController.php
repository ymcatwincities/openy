<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\Core\Ajax;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\mindbody\MindbodyException;
use Drupal\ymca_mindbody\YmcaMindbodyResultsSearcher;
use Drupal\ymca_mindbody\YmcaMindbodyResultsSearcherInterface;
use Drupal\ymca_mindbody\YmcaMindbodyRequestGuard;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for "Mindbody results" page.
 */
class MindbodyResultsController extends ControllerBase {

  /**
   * The results searcher.
   *
   * @var YmcaMindbodyResultsSearcherInterface
   */
  protected $resultsSearcher;

  /**
   * Logger.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The request stack.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * MindbodyResultsController constructor.
   *
   * @param YmcaMindbodyResultsSearcherInterface $results_searcher
   *   Results searcher.
   * @param YmcaMindbodyRequestGuard $request_guard
   *   Request guard.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param RequestStack $request_stack
   *   Request stack.
   */
  public function __construct(
    YmcaMindbodyResultsSearcherInterface $results_searcher,
    YmcaMindbodyRequestGuard $request_guard,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack
  ) {
    $this->requestGuard = $request_guard;
    $this->resultsSearcher = $results_searcher;
    $this->logger = $logger_factory->get('ymca_mindbody');
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ymca_mindbody.results_searcher'),
      $container->get('ymca_mindbody.request_guard'),
      $container->get('logger.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * Set page content.
   */
  public function content() {
    $query = $this->requestStack->getCurrentRequest()->query->all();
    $values = [
      'location' => !empty($query['location']) && is_numeric($query['location']) ? $query['location'] : NULL,
      'program' => !empty($query['program']) && is_numeric($query['program']) ? $query['program'] : NULL,
      'session_type' => !empty($query['session_type']) && is_numeric($query['session_type']) ? $query['session_type'] : NULL,
      'trainer' => !empty($query['trainer']) ? $query['trainer'] : NULL,
      'start_time' => !empty($query['start_time']) ? $query['start_time'] : NULL,
      'end_time' => !empty($query['end_time']) ? $query['end_time'] : NULL,
      'date_range' => !empty($query['date_range']) ? $query['date_range'] : NULL,
      'context' => isset($query['context']) ? $query['context'] : '',
      'bookable_item_id' => isset($query['bookable_item_id']) ? $query['bookable_item_id'] : '',
    ];


    $node = $this->requestStack->getCurrentRequest()->get('node');
    try {
      $search_results = $this->resultsSearcher->getSearchResults($values, $node);
    }
    catch (MindbodyException $e) {
      $this->logger->error('Failed to get the results: %msg', ['%msg' => $e->getMessage()]);

      return [
        '#prefix' => '<div class="row mindbody-search-results-content">
          <div class="container">
            <div class="day col-sm-12">',
        'markup' => $this->resultsSearcher->getDisabledMarkup(),
        '#suffix' => '</div></div></div>',
      ];
    }

    return [
      'search_results' => $search_results,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return $this->t('Personal Training Schedules');
  }

  /**
   * Minbody PT book callback.
   */
  public function book() {
    $response = new AjaxResponse();

    $query = $this->requestStack->getCurrentRequest()->query->all();
    if (!YmcaMindbodyResultsSearcher::validateToken($query)) {
      return $this->invalidTokenResponse();
    }

    $output[] = $this->t('Token is valid.');
    if ($personify_authenticated = $this->requestStack->getCurrentRequest()->cookies->has('Drupal_visitor_personify_authorized')) {
      // Book item if user is authenticated in Personify.
      if ($this->bookItem($query)) {
        // Successfully booked.
        $output[] = $this->t('Successfully booked.');
      }
      else {
        // Booking failed.
        $output[] = $this->t('The booking process failed.');
      }
    }
    else {
      // Redirect to Personify login if user isn't authenticated there.
      return $this->redirectToPersonifyLogin();
    }

    $output[] = print_r($query, TRUE);

    $content = '<div class="popup-content">' . implode('<br>', $output) . '</div>';
    $options = array(
      'dialogClass' => 'popup-dialog-class',
      'width' => '620',
      'height' => '600',
    );
    $title = $this->t('Booking');
    $response->addCommand(new OpenModalDialogCommand($title, $content, $options));

    return $response;
  }

  /**
   * Custom response callback.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response object.
   */
  private function invalidTokenResponse() {
    $query = $this->requestStack->getCurrentRequest()->query->all();

    $output = [];
    $output[] = $this->t('Token is invalid.');
    $output[] = $this->t('Not booked.');
    $output[] = $this->t('Refresh the page.');
    $output[] = print_r($query, TRUE);

    $response = new AjaxResponse();
    $content = '<div class="token-invalid-popup-content">' . implode('<br>', $output) . '</div>';
    $options = array(
      'dialogClass' => 'popup-dialog-class-error',
      'width' => '300',
      'height' => '300',
    );
    $title = $this->t('Error');
    $response->addCommand(new OpenModalDialogCommand($title, $content, $options));

    return $response;
  }

  /**
   * Books Mindbody item.
   *
   * @param array $data
   *   Array of required item parameters.
   *
   * @return bool
   *   The state of booking.
   *
   * @todo
   *   Implement method.
   */
  private function bookItem(array $data) {
    // TODO: implement method.
    return mt_rand(0, 100) > 50;
  }

  /**
   * Return redirect AJAX response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AJAX response object, that redirects to Personify login.
   */
  private function redirectToPersonifyLogin() {
    $query = $this->requestStack->getCurrentRequest()->query->all();
    $args = YmcaMindbodyResultsSearcher::getTokenArgs();
    foreach (array_keys($query) as $key) {
      if (!in_array($key, $args)) {
        unset($query[$key]);
      }
    }
    // Build return url.
    if (isset($query['context']) && in_array($query['context'], ['location', 'trainer'])) {
      $destination = Url::fromRoute('ymca_mindbody.location.pt.results', [
        'node' => $query['location'],
      ], [
        'query' => $query,
      ]);
    }
    else {
      $destination = Url::fromRoute('ymca_mindbody.pt.results', [], [
        'query' => $query,
      ]);
    }
    // Build Personify login url.
    $redirect_url = Url::fromRoute('ymca_personify.personify_login', [], [
      'query' => [
        'dest' => $destination->toString(),
      ],
    ]);

    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($redirect_url->toString()));

    return $response;
  }

}
