<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\mindbody\MindbodyException;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyManager;
use Drupal\ymca_errors\ErrorManager;
use Drupal\ymca_mappings\LocationMappingRepository;
use Drupal\ymca_mindbody\YmcaMindbodyResultsSearcher;
use Drupal\ymca_mindbody\YmcaMindbodyResultsSearcherInterface;
use Drupal\ymca_mindbody\YmcaMindbodyRequestGuard;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ymca_alters\Utility\YmcaTimezone;

/**
 * Controller for "Mindbody results" page.
 */
class MindbodyResultsController extends ControllerBase {

  /**
   * Use YmcaTimezone trait to properly format date/time.
   */
  use YmcaTimezone;

  /**
   * Session type.
   */
  const QUERY_PARAM__SESSION_TYPE = 's';

  /**
   * Location.
   */
  const QUERY_PARAM__LOCATION = 'location';

  /**
   * Program ID.
   */
  const QUERY_PARAM__PROGRAM_ID = 'p';

  /**
   * Test trainer. We may create appointments for him.
   */
  const TEST_API_TRAINER_ID = '100000323';

  /**
   * Test client ID.
   */
  const TEST_API_CLIENT_ID = 69696969;

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
   * Error Manager.
   *
   * @var ErrorManager
   */
  protected $errorManager;

  /**
   * The cache manager.
   *
   * @var MindbodyCacheProxyManager
   */
  protected $cacheManager;

  /**
   * Mail manager.
   *
   * @var MailManagerInterface
   */
  protected $mailManager;

  /**
   * Mindbody Credentials.
   *
   * @var array
   */
  protected $credentials;

  /**
   * Production flag.
   *
   * @var bool
   */
  protected $isProduction;

  /**
   * Request Guard.
   *
   * @var YmcaMindbodyRequestGuard
   */
  protected $requestGuard;

  /**
   * Keyvalue expirable factory.
   *
   * @var KeyValueDatabaseExpirableFactory
   */
  protected $keyValueExpirable;

  /**
   * Location repository.
   *
   * @var LocationMappingRepository
   */
  protected $locationRepository;

  /**
   * Language manager.
   *
   * @var LanguageManagerInterface
   */
  protected $languageManager;

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
   *   The Mindbody Cache Proxy.
   * @param ConfigFactory $config_factory
   *   The Config Factory.
   * @param ErrorManager $error_manager
   *   The Error manager.
   * @param MindbodyCacheProxyManager $cache_manager
   *   The cache manager.
   * @param MailManagerInterface $mail_manager
   *   Mail manager.
   * @param KeyValueDatabaseExpirableFactory $key_value_expirable
   *   The Keyvalue expirable factory.
   * @param LocationMappingRepository $location_repository
   *   The location repository.
   * @param LanguageManagerInterface $language_manager
   *   Language manager.
   */
  public function __construct(
    YmcaMindbodyResultsSearcherInterface $results_searcher,
    YmcaMindbodyRequestGuard $request_guard,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    MindbodyCacheProxy $proxy,
    ConfigFactory $config_factory,
    ErrorManager $error_manager,
    MindbodyCacheProxyManager $cache_manager,
    MailManagerInterface $mail_manager,
    KeyValueDatabaseExpirableFactory $key_value_expirable,
    LocationMappingRepository $location_repository,
    LanguageManagerInterface $language_manager
  ) {
    $this->requestGuard = $request_guard;
    $this->resultsSearcher = $results_searcher;
    $this->logger = $logger_factory->get('ymca_mindbody');
    $this->requestStack = $request_stack;
    $this->proxy = $proxy;
    $this->configFactory = $config_factory;
    $this->errorManager = $error_manager;
    $this->cacheManager = $cache_manager;
    $this->mailManager = $mail_manager;
    $this->keyValueExpirable = $key_value_expirable;
    $this->locationRepository = $location_repository;
    $this->languageManager = $language_manager;

    $this->credentials = $this->configFactory->get('mindbody.settings');
    $this->isProduction = $this->configFactory->get('ymca_mindbody.settings')->get('is_production');
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
      $container->get('config.factory'),
      $container->get('ymca_errors.error_manager'),
      $container->get('mindbody_cache_proxy.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('keyvalue.expirable.database'),
      $container->get('ymca_mappings.location_repository'),
      $container->get('language_manager')
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
    $message = $this->errorManager->getError('err__mindbody__booking_failed');

    // OK.
    if (is_array($book) && isset($book['status']) && $book['status'] === TRUE) {
      $message = $this->t('Your appointment is confirmed.');

      // Send email notification if we have proper booking data.
      $storage = $this->keyValueExpirable->get(YmcaMindbodyResultsSearcher::KEY_VALUE_COLLECTION);
      if ($booking_data = $storage->get($query['token'])) {
        $client_id = self::TEST_API_CLIENT_ID;
        if ($this->isProduction) {
          $client_id = $this->requestStack->getCurrentRequest()->cookies->get('Drupal_visitor_personify_id');
        }

        $location_id = $query[self::QUERY_PARAM__LOCATION];
        $location = $this->locationRepository->findByMindBodyId($location_id);

        // Default token for the notification.
        $tokens = [
          'trainer_name' => $booking_data['trainer_name'],
          'start_date' => $this->formatDateTimeString($booking_data['start_date'], 'D, d M Y H:i', 'F d, Y, g:iA'),
          'location' => $location->label(),
          'trainer_email' => $booking_data['trainer_email'],
          'trainer_phone' => $booking_data['trainer_phone'],
          'client_name' => 'N/A',
          'client_email' => 'N/A',
          'client_phone' => 'N/A',
        ];

        // Get client data.
        if ($client_data = $this->getClientData($client_id)) {
          $tokens['client_name'] = $client_data->FirstName . ' ' . $client_data->LastName;
          $tokens['client_email'] = $client_data->Email;
          $tokens['client_phone'] = $client_data->MobilePhone;
        }

        try {
          // Send notifications.
          $lang = $this->languageManager->getCurrentLanguage()->getId();
          $this->mailManager->mail('ymca_mindbody', 'notify_trainer', $booking_data['trainer_email'], $lang, $tokens);
          $this->mailManager->mail('ymca_mindbody', 'notify_customer', $client_data->Email, $lang, $tokens);
        }
        catch (\Exception $e) {
          $msg = 'Failed to send email notification. Error: %error';
          $this->logger->critical($msg, ['%error' => $e->getMessage()]);
        }
      }

    }

    // Not OK, but there is a message.
    $products_list = '';
    if (is_array($book) && isset($book['status'], $book['message']) && $book['status'] === FALSE) {
      $message = $book['message'];
      // Display list of products for chosen location and session length.
      $mapping_repository_location = \Drupal::service('ymca_mappings.location_repository');
      $mapping = $mapping_repository_location->findByMindBodyId($query['location']);
      if (!empty($mapping->field_location_personify_brcode->getValue())) {
        $br_code = $mapping->field_location_personify_brcode->getValue()[0]['value'];
        $session_types = \Drupal::service('ymca_mindbody.results_searcher')
          ->getSessionTypes($query['p']);
        $session_type_name = isset($session_types[$query['s']]) ? $session_types[$query['s']] : '';
        $session_length = preg_replace("/[^0-9]/", "", $session_type_name);

        $mapping_repository = \Drupal::service('ymca_mappings.personify_product_repository');
        $mappings = $mapping_repository->loadAll();
        $products_to_display_member = $products_to_display_non_member = [];
        $config = \Drupal::config('ymca_mindbody.settings');
        $personify_product_url = $config->get('personify_product_url');
        foreach ($mappings as $mapping) {
          if (empty($mapping->field_product_code->getValue())) {
            continue;
          }
          $product_code_array = explode('_', $mapping->field_product_code->getValue()[0]['value']);
          if (count($product_code_array) == 6) {
            $session_length_product = $mapping->field_session_length->getValue()[0]['value'];
            if ($product_code_array[0] == $br_code && $session_length == $session_length_product) {
              $product_member_price = $mapping->field_member_price->getValue()[0]['value'];
              $product_non_member_price = $mapping->field_nonmember_price->getValue()[0]['value'];
              $products_to_display_member[$product_code_array[4]][] = [
                'price' => $product_member_price,
                'session' => $product_code_array[4],
                'package' => $product_code_array[2],
                'url' => $personify_product_url . $mapping->field_productid->getValue()[0]['value'],
              ];
              $products_to_display_non_member[$product_code_array[4]][] = [
                'price' => $product_non_member_price,
                'session' => $product_code_array[4],
                'package' => $product_code_array[2],
                'url' => $personify_product_url . $mapping->field_productid->getValue()[0]['value'],
              ];
            }
          }
        }
        if (isset($products_to_display_member[30])) {
          asort($products_to_display_member[30]);
        }
        if (isset($products_to_display_member[60])) {
          asort($products_to_display_member[60]);
        }
        if (isset($products_to_display_non_member[30])) {
          asort($products_to_display_non_member[30]);
        }
        if (isset($products_to_display_non_member[60])) {
          asort($products_to_display_non_member[60]);
        }
        $products_list = [
          '#theme' => 'mindbody_products_list_modal',
          '#products_to_display_member' => $products_to_display_member,
          '#products_to_display_non_member' => $products_to_display_non_member,
        ];
        $products_list = render($products_list);
      }
    }

    $output[] = print_r($query, TRUE);

    $content = '<div class="popup-content">' . $message . $products_list . '</div>';
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
   * Get client data.
   *
   * @param int $id
   *   Client ID.
   *
   * @return \stdClass|bool
   *   Client data.
   */
  private function getClientData($id) {
    try {
      $params = [
        'SourceCredentials' => [
          'SourceName' => $this->credentials->get('sourcename'),
          'Password' => $this->credentials->get('password'),
          'SiteIDs' => [$this->credentials->get('site_id')],
        ],
        'ClientIDs' => [$id],
      ];

      $result = $this->proxy->call('ClientService', 'GetClients', $params);

      if (200 != $result->GetClientsResult->ErrorCode) {
        $this->logger->error('Got non 200 error code with ClientService (GetClients). Result: %s', ['%s' => serialize($result->GetClientsResult)]);
        return FALSE;
      }

      if (!count((array) $result->GetClientsResult->Clients)) {
        $this->logger->error('Client with ID: %id not found with ClientService (GetClients)', ['%id' => $id]);
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get client data for a notification. Message: %message', ['%message' => $e->getMessage()]);
      return FALSE;
    }

    return $result->GetClientsResult->Clients->Client;
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
    $storage = $this->keyValueExpirable->get(YmcaMindbodyResultsSearcher::KEY_VALUE_COLLECTION);
    if (!$booking_data = $storage->get($data['token'])) {
      return [
        'status' => FALSE
      ];
    }

    $client_id = self::TEST_API_CLIENT_ID;
    $location_id = $data[self::QUERY_PARAM__LOCATION];

    // Get client ID from cookies.
    if ($this->isProduction) {
      if (!$client_id = $this->requestStack->getCurrentRequest()->cookies->get('Drupal_visitor_personify_id')) {
        $this->logger->error('There is no client ID in cookies.');
        return [
          'status' => FALSE
        ];
      }
    }

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
        'SessionTypeIDs' => [$data[self::QUERY_PARAM__SESSION_TYPE]],
        'ClientID' => $client_id,
        'ClassID' => FALSE,
        // Retrieve only active trainings.
        'ShowActiveOnly' => TRUE,
      ];

      $result = $this->proxy->call('ClientService', 'GetClientServices', $params, FALSE);

      if (200 != $result->GetClientServicesResult->ErrorCode) {
        $this->logger->error('Got non 200 error code with ClientService (GetClientServices). Result: %s', ['%s' => serialize($result->GetClientServicesResult)]);
        return [
          'status' => FALSE
        ];
      }

      // Check if client has no services at all.
      if (empty((array) $result->GetClientServicesResult->ClientServices)) {
        return [
          'status' => FALSE,
          'message' => $this->t($this->errorManager->getError('err__mindbody__booking_no_services')),
        ];
      }

      $service = FALSE;

      $services = $result->GetClientServicesResult->ClientServices;
      if (is_array($services->ClientService)) {
        $list = $services->ClientService;
      }
      else {
        $list = (array) $services;
      }

      foreach ($list as $service_value) {
        // Select only packages that haven't been used.
        // @TODO: check expiration date?
        if ($service_value->Remaining > 0) {
          $service = [
            'Current' => $service_value->Current,
            'Count' => $service_value->Count,
            'ID' => $service_value->ID,
            'Remaining' => $service_value->Remaining
          ];
        }
        // We need just first one.
        break;
      }

      if (FALSE == $service) {
        $this->logger->error('Failed to find available services. Response: %s', ['%s' => serialize($result->GetClientServicesResult)]);
        return [
          'status' => FALSE,
          'message' => $this->t($this->errorManager->getError('err__mindbody__booking_no_services')),
        ];
      }

    }
    catch (\Exception $e) {
      $this->logger->error('Failed to make a request to ClientService (GetClientServices). Message: %s', ['%s' => $e->getMessage()]);
      return [
        'status' => FALSE,
      ];
    }

    /*Book an appointment.

    First of all, we should check is_production flag. If it's FALSE we should
    always crete appointments in 'test' mode (ie without creating a real appointment).

    We have special test API trainer. For this trainer we could create real
    appointments in test and development modules.*/
    try {
      $params = [
        'UserCredentials' => $user_credentials,
        'Test' => TRUE,
        'Appointments' => [
          'Appointment' => [
            'StartDateTime' => date('Y-m-d\TH:i:s', $booking_data['start_time']),
            'Location' => [
              'ID' => $location_id,
            ],
            'Staff' => [
              'isMale' => (bool) $booking_data['is_male'],
              'ID' => $booking_data['stuff_id'],
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

      // If it's production - create real appointment.
      if ($this->isProduction) {
        unset($params['Test']);
      }

      // Allow creation of real appointments for test trainer.
      if ($booking_data['stuff_id'] == self::TEST_API_TRAINER_ID) {
        unset($params['Test']);
        $params['Appointments']['Appointment']['Staff']['isMale'] = $booking_data['is_male'];
      }

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
        'status' => FALSE,
      ];
    }

    // So, we've booked our item. Let's clear the cache.
    $this->cacheManager->resetBookableItemsCacheByLocation($location_id);

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
