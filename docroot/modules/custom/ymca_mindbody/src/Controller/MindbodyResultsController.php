<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\Core\Ajax;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\mindbody\MindbodyException;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;
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
   * Session type.
   */
  const QUERY_PARAM__SESSION_TYPE = 's';

  /**
   * Location.
   */
  const QUERY_PARAM__LOCATION = 'location';

  /**
   * Staff ID.
   */
  const QUERY_PARAM__STAFF_ID = 'si';

  /**
   * Is Male.
   */
  const QUERY_PARAM__IS_MALE = 'im';

  /**
   * Is Male.
   */
  const QUERY_PARAM__START_TIMESTAMP = 'tm';

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
   * Cache proxy.
   *
   * @var MindbodyCacheProxy
   */
  protected $proxy;

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * Mindbody Credentials.
   *
   * @var array
   */
  protected $credentials;

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
   * @param MindbodyCacheProxy $proxy
   *   Mindbody Cache Proxy.
   * @param ConfigFactory $config_factory
   *   Config Factory.
   */
  public function __construct(
    YmcaMindbodyResultsSearcherInterface $results_searcher,
    YmcaMindbodyRequestGuard $request_guard,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    MindbodyCacheProxy $proxy,
    ConfigFactory $config_factory
  ) {
    $this->requestGuard = $request_guard;
    $this->resultsSearcher = $results_searcher;
    $this->logger = $logger_factory->get('ymca_mindbody');
    $this->requestStack = $request_stack;
    $this->proxy = $proxy;
    $this->configFactory = $config_factory;
    $this->credentials = $this->configFactory->get('mindbody.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ymca_mindbody.results_searcher'),
      $container->get('ymca_mindbody.request_guard'),
      $container->get('logger.factory'),
      $container->get('request_stack'),
      $container->get('mindbody_cache_proxy.client'),
      $container->get('config.factory')
    );
  }

  /**
   * Set page content.
   */
  public function content() {
    $query = $this->requestStack->getCurrentRequest()->query->all();
    $values = [
      'location' => !empty($query['location']) && is_numeric($query['location']) ? $query['location'] : NULL,
      'p' => !empty($query['p']) && is_numeric($query['p']) ? $query['p'] : NULL,
      's' => !empty($query['s']) && is_numeric($query['s']) ? $query['s'] : NULL,
      'trainer' => !empty($query['trainer']) ? $query['trainer'] : NULL,
      'st' => !empty($query['st']) ? $query['st'] : NULL,
      'et' => !empty($query['et']) ? $query['et'] : NULL,
      'dr' => !empty($query['dr']) ? $query['dr'] : NULL,
      'context' => isset($query['context']) ? $query['context'] : '',
      'bid' => isset($query['bid']) ? $query['bid'] : '',
      'si' => isset($query['si']) ? $query['si'] : '',
      'im' => isset($query['im']) ? $query['im'] : '',
      'tm' => isset($query['tm']) ? $query['tm'] : '',
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
   * MindBody PT book callback.
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
      $book = $this->bookItem($query);
    }
    else {
      // Redirect to Personify login if user isn't authenticated there.
      return $this->redirectToPersonifyLogin();
    }

    // Default message.
    $message = $this->t('Unfortunately we can\'t schedule that appointment. Please call branch or try to select another time.');

    // OK.
    if (is_array($book) && isset($book['status']) && $book['status'] === TRUE) {
      $message = $this->t('Your appointment is confirmed.');
    }

    // Not OK, but there is a message.
    if (is_array($book) && isset($book['status'], $book['message']) && $book['status'] === FALSE) {
      $message = $book['message'];
    }

    $output[] = print_r($query, TRUE);

    $content = '<div class="popup-content">' . $message . '</div>';
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
   * @return array
   *   The array should contain 2 keys:
   *     - status: TRUE or FALSE
   *     - message (optional): description.
   */
  private function bookItem(array $data) {
    // Get client ID from cookies.
    if (!$client_id = $this->requestStack->getCurrentRequest()->cookies->get('Drupal_visitor_personify_id')) {
      $this->logger->error('There is no client ID in cookies.');
      return [
        'status' => FALSE
      ];
    }

    // @todo Use real client ID here.
    $client_id = 69696969;

    // Default credentials.
    $user_credentials = [
      'Username' => $this->credentials->get('user_name'),
      'Password' => $this->credentials->get('user_password'),
      'SiteIDs' => [$this->credentials->get('site_id')],
    ];

    // Get client services.
    try {
      $params = [
        'UserCredentials' => $user_credentials,
        'SessionTypeIDs' => [$data['s']],
        'ClientID' => $client_id,
        'ClassID' => FALSE,
      ];

      $result = $this->proxy->call('ClientService', 'GetClientServices', $params, FALSE);

      if (200 != $result->GetClientServicesResult->ErrorCode) {
        $this->logger->error('Got non 200 error code with ClientService (GetClientServices). Result: %s', ['%s' => serialize($result->GetClientServicesResult)]);
        return [
          'status' => FALSE
        ];
      }

      // @todo Need to test with different conditions: no services, single service, expired service, remain 0, etc.
      $service = FALSE;
      foreach ($result->GetClientServicesResult->ClientServices->ClientService as $service) {
        $service = [
          'Current' => $service->Current,
          'Count' => $service->Count,
          'ID' => $service->ID,
          'Remaining' => $service->Remaining
        ];

        // We need just first one.
        break;
      }

      if (FALSE == $service) {
        $this->logger->error('Failed to find available services. Response: %s', ['%s' => serialize($result->GetClientServicesResult)]);
        return [
          'status' => FALSE,
          'message' => $this->t('You have no available trainings, please visit the front desk to purchase personal training.'),
        ];
      }

    }
    catch (\Exception $e) {
      $this->logger->error('Failed to make a request to ClientService (GetClientServices). Message: %s', ['%s' => $e->getMessage()]);
      return [
        'status' => FALSE
      ];
    }

    // Book an appointment.
    try {
      $params = [
        'UserCredentials' => $user_credentials,
        'Test' => TRUE,
        'Appointments' => [
          'Appointment' => [
            'StartDateTime' => date('Y-m-d\TH:i:s', $data[self::QUERY_PARAM__START_TIMESTAMP]),
            'Location' => [
              'ID' => $data[self::QUERY_PARAM__LOCATION],
            ],
            'Staff' => [
              'isMale' => (bool) $data[self::QUERY_PARAM__IS_MALE],
              'ID' => $data[self::QUERY_PARAM__STAFF_ID],
            ],
            'Client' => [
              'ID' => $client_id,
            ],
            'SessionType' => [
              'ID' => $data[self::QUERY_PARAM__SESSION_TYPE],
            ],
            'ClientService' => $service,
          ],
        ],
      ];
      $result = $this->proxy->call('AppointmentService', 'AddOrUpdateAppointments', $params, FALSE);

      if (200 != $result->AddOrUpdateAppointmentsResult->ErrorCode) {
        $this->logger->error('Got non 200 error code with AppointmentService (AddOrUpdateAppointments). Result: %s', ['%s' => serialize($result->AddOrUpdateAppointmentsResult)]);
        return [
          'status' => FALSE
        ];
      }

      // Check status.
      if ('Booked' != $result->AddOrUpdateAppointmentsResult->Appointments->Appointment->Status) {
        $this->logger->error('Failed to book an appointment. Result: %s', ['%s' => serialize($result->AddOrUpdateAppointmentsResult)]);
        return [
          'status' => FALSE
        ];
      }

    }
    catch (\Exception $e) {
      $this->logger->error('Failed to make a request to AppointmentService (AddOrUpdateAppointments). Message: %s', ['%s' => $e->getMessage()]);
      return [
        'status' => FALSE
      ];
    }

    return [
      'status' => TRUE
    ];
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
