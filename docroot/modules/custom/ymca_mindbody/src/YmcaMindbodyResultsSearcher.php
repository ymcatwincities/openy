<?php

namespace Drupal\ymca_mindbody;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class YmcaMindbodyResultsSearcher.
 *
 * @package Drupal\ymca_mindbody
 */
class YmcaMindbodyResultsSearcher implements YmcaMindbodyResultsSearcherInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Min time value that should be available on form.
   */
  const MIN_TIME_RANGE = 5;

  /**
   * Max time value that should be available on form.
   */
  const MAX_TIME_RANGE = 22;

  /**
   * Default timezone of incoming results.
   */
  const DEFAULT_TIMEZONE = 'America/Chicago';

  /**
   * The Config Factory definition.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * The Mindbody Proxy.
   *
   * @var MindbodyCacheProxyInterface
   */
  protected $proxy;

  /**
   * The Training mapping service.
   *
   * @var YmcaMindbodyTrainingsMapping
   */
  protected $trainingsMapping;

  /**
   * The credentials.
   *
   * @var ImmutableConfig
   */
  protected $credentials;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The YMCA Mindbody settings.
   *
   * @var ImmutableConfig
   */
  protected $settings;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructor.
   *
   * @param ConfigFactory $config_factory
   *   The Config Factory.
   * @param QueryFactory $entity_query
   *   The entity query factory.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The entity type manager.
   * @param MindbodyCacheProxyInterface $proxy
   *   The Mindbody Cache Proxy.
   * @param YmcaMindbodyTrainingsMapping $trainings_mapping
   *   The Mindbody Training Mapping.
   */
  public function __construct(
    ConfigFactory $config_factory,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    MindbodyCacheProxyInterface $proxy,
    YmcaMindbodyTrainingsMapping $trainings_mapping
  ) {
    $this->configFactory = $config_factory;
    $this->proxy = $proxy;
    $this->trainingsMapping = $trainings_mapping;
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('ymca_mindbody');
    $this->credentials = $this->configFactory->get('mindbody.settings');
    $this->settings = $this->configFactory->get('ymca_mindbody.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('mindbody_cache_proxy.client'),
      $container->get('ymca_mindbody.trainings_mapping')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Split on several methods: fetch data, represent data.
   */
  public function getSearchResults(array $criteria, $node = NULL) {
    if (!isset(
      $criteria['location'],
      $criteria['p'],
      $criteria['s'],
      $criteria['trainer'],
      $criteria['dr'],
      $criteria['st'],
      $criteria['et']
    )) {
      $link = Link::createFromRoute($this->t('Start your search again'), 'ymca_mindbody.pt');
      if (isset($this->node)) {
        $link = Link::createFromRoute($this->t('Start your search again'), 'ymca_mindbody.location.pt', [
          'node' => $this->node->id(),
        ]);
      }
      return [
        '#prefix' => '<div class="row mindbody-search-results-content">
          <div class="container">
            <div class="day col-sm-12">',
        '#markup' => t('We couldn\'t complete your search. !search_link.', [
          '!search_link' => $link->toString(),
        ]),
        '#suffix' => '</div></div></div>',
      ];
    }

    // Get default timezone (in order to play good with strtotime()).
    $defaultTimeZone = new \DateTimeZone(date_default_timezone_get());

    $booking_params = [
      'UserCredentials' => [
        'Username' => $this->credentials->get('user_name'),
        'Password' => $this->credentials->get('user_password'),
        'SiteIDs' => [$this->credentials->get('site_id')],
      ],
      'SessionTypeIDs' => [$criteria['s']],
      'LocationIDs' => [$criteria['location']],
    ];

    if (!empty($criteria['trainer']) && $criteria['trainer'] != 'all') {
      $booking_params['StaffIDs'] = array($criteria['trainer']);
    }

    $period = $this->getRangeStrtotime($criteria['dr']);
    $booking_params['StartDate'] = date('Y-m-d', strtotime('today'));
    $booking_params['EndDate'] = date('Y-m-d', strtotime("today $period"));

    $bookable = $this->proxy->call('AppointmentService', 'GetBookableItems', $booking_params);

    $time_range = range($criteria['st'], $criteria['et']);

    $days = [];
    // Group results by date and trainer.
    if (!empty($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem)) {
      $schedule_item = $bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem;
      if (count($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem) == 1) {
        $schedule_item = $bookable->GetBookableItemsResult->ScheduleItems;
      }
      foreach ($schedule_item as $bookable_item) {
        // Additionally filter results by time.
        $start_time = date('G', strtotime($bookable_item->StartDateTime));
        $end_time = date('G', strtotime($bookable_item->EndDateTime));

        if (in_array($start_time, $time_range) && in_array($end_time, $time_range)) {
          // Do not process the items which are in the past.
          // Temporary solution, should be removed once Drupal default timezone is changed.
          if ($this->getTimestampInTimezone('now') >= $this->getTimestampInTimezone($bookable_item->StartDateTime)) {
            continue;
          }

          // Here we create date range to iterate.
          $dateTime = new \DateTime();
          $dateTime->setTimezone($defaultTimeZone);

          $begin = clone $dateTime;
          $begin->setTimestamp(strtotime($bookable_item->StartDateTime));

          $end = clone $dateTime;
          $end->setTimestamp(strtotime($bookable_item->EndDateTime));

          $interval = new \DateInterval(sprintf('PT%dM', $bookable_item->SessionType->DefaultTimeLength));
          $range = new \DatePeriod($begin, $interval, $end);

          foreach ($range as $i => $item) {
            // Skip if time between $item start and time slot length less than training length.
            $remain = ($end->getTimestamp() - $item->getTimestamp()) / 60;
            if ($remain < $bookable_item->SessionType->DefaultTimeLength) {
              continue;
            }

            $group_date = date('F d, Y', strtotime($bookable_item->StartDateTime));
            $days[$group_date]['weekday'] = date('l', strtotime($bookable_item->StartDateTime));

            // Add bookable item id if it isn't provided by Mindbody API.
            if (!$bookable_item->ID) {
              $bookable_item->ID = md5(serialize($bookable_item));
            }

            // Unique ID for each time slice.
            $id = $bookable_item->ID . '-' . $i;

            $options = [
              'attributes' => [
                'class' => [
                  'use-ajax',
                  $id == $criteria['bid'] ? 'highlight-item' : '',
                ],
                'data-dialog-type' => 'modal',
                'id' => 'bookable-item-' . $id,
              ],
              'html' => TRUE,
            ];

            $query = ['bid' => $id] + $criteria;
            $query['token'] = $this::getToken($query);
            $options['query'] = $query;

            $text = new FormattableMarkup('<span class="icon icon-clock"></span> @from - @to', [
              '@from' => date('h:i a', $item->getTimestamp()),
              '@to' => date('h:i a', $item->add($interval)->getTimestamp()),
            ]);
            $link = Link::createFromRoute($text, 'ymca_mindbody.pt.book', [], $options);

            $days[$group_date]['trainers'][$bookable_item->Staff->Name][] = $link;
          }
        }
      }
    }

    if ($criteria['trainer'] == 'all') {
      $trainer_name = $this->t('all trainers');
    }
    else {
      $trainer_name = $this->getTrainerName($criteria['trainer']);
    }

    $time_options = $this->getTimeOptions();
    $start_time = $time_options[$criteria['st']];
    $end_time = $time_options[$criteria['et']];

    $period = $this->getRangeStrtotime($criteria['dr']);
    $start_date = date('n/d/Y', strtotime("today"));
    $end_date = date('n/d/Y', strtotime("today $period"));

    $datetime = '<div><span class="icon icon-calendar"></span><span>' . $this->t('Time:') . '</span> ' . $start_time . ' - ' . $end_time . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div><div><span>' . $this->t('Date:') . '</span> ' . $start_date . ' - ' . $end_date . '</div>';

    $locations = $this->getLocations();
    $location_name = isset($locations[$criteria['location']]) ? $locations[$criteria['location']] : '';
    $programs = $this->getPrograms();
    $program_name = isset($programs[$criteria['p']]) ? $programs[$criteria['p']] : '';
    $session_types = $this->getSessionTypes($criteria['p']);
    $session_type_name = isset($session_types[$criteria['s']]) ? $session_types[$criteria['s']] : '';

    $telephone = '';
    /* @todo Use a service instead of direct queries. */
    $mapping_id = $this->entityQuery
      ->get('mapping')
      ->condition('type', 'location')
      ->condition('field_mindbody_id', $criteria['location'])
      ->execute();
    $mapping_id = reset($mapping_id);
    if ($mapping = $this->entityTypeManager->getStorage('mapping')->load($mapping_id)) {
      $field_location_ref = $mapping->field_location_ref->getValue();
      $location_id = isset($field_location_ref[0]['target_id']) ? $field_location_ref[0]['target_id'] : FALSE;
      if ($location_node = $this->entityTypeManager->getStorage('node')->load($location_id)) {
        $field_fitness_phone = $location_node->field_fitness_phone->getValue();
        $telephone = isset($field_fitness_phone[0]['value']) ? $field_fitness_phone[0]['value'] : FALSE;
      }
    }
    $options = [
      'query' => [
        'step' => 4,
        'mb_location' => $criteria['location'],
        'mb_program' => $criteria['p'],
        'mb_session_type' => $criteria['s'],
        'mb_trainer' => $criteria['trainer'],
        'mb_date_range' => $criteria['dr'],
        'mb_start_time' => $criteria['st'],
        'mb_end_time' => $criteria['et'],
      ],
    ];
    if (isset($criteria['context'])) {
      $options['query']['context'] = $criteria['context'];
      if (isset($criteria['location'])) {
        $options['query']['location'] = $criteria['location'];
      }
      if (isset($criteria['trainer']) && $criteria['trainer'] != 'all') {
        $options['query']['trainer'] = $criteria['trainer'];
      }
    }

    $search_results = [
      '#theme' => 'mindbody_results_content',
      '#location' => $location_name,
      '#program' => $program_name,
      '#session_type' => $session_type_name,
      '#trainer' => $trainer_name,
      '#datetime' => $datetime,
      '#back_link' => $this->getSearchLink($options, $node),
      '#start_again_link' => $this->getSearchLink([], $node),
      '#telephone' => $telephone,
      '#base_path' => base_path(),
      '#days' => $days,
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];

    return $search_results;
  }

  /**
   * {@inheritdoc}
   */
  public static function getRangeStrtotime($value) {
    $options = [
      '3days' => '+ 3 days',
      'week' => '+ 1 week',
      '3weeks' => '+ 3 weeks',
    ];
    $default = $options['3days'];

    if (!isset($options[$value])) {
      return $default;
    }

    return $options[$value];
  }

  /**
   * {@inheritdoc}
   */
  public static function getTimestampInTimezone($data) {
    $date = new DrupalDateTime($data, static::DEFAULT_TIMEZONE);
    return $date->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSearchLink($options, $node = NULL) {
    if (!isset($node)) {
      return Url::fromRoute('ymca_mindbody.pt', [], $options);
    }
    return Url::fromRoute('ymca_mindbody.location.pt', ['node' => $node->id()], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations() {
    $locations = $this->proxy->call('SiteService', 'GetLocations');

    $location_options = [];
    foreach ($locations->GetLocationsResult->Locations->Location as $location) {
      if ($location->HasClasses != TRUE || !$this->trainingsMapping->locationIsActive($location->ID)) {
        continue;
      }
      $location_options[$location->ID] = $this->trainingsMapping->getLocationLabel($location->ID, $location->Name);
    }

    return $location_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrograms() {
    $programs = $this->proxy->call('SiteService', 'GetPrograms', [
      'OnlineOnly' => FALSE,
      'ScheduleType' => 'Appointment',
    ]);

    $program_options = [];
    foreach ($programs->GetProgramsResult->Programs->Program as $program) {
      if (!$this->trainingsMapping->programIsActive($program->ID)) {
        continue;
      }
      $program_options[$program->ID] = $this->trainingsMapping->getProgramLabel($program->ID, $program->Name);
    }

    return $program_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionTypes($program_id) {
    $session_types = $this->proxy->call('SiteService', 'GetSessionTypes', [
      'OnlineOnly' => FALSE,
      'ProgramIDs' => [$program_id],
    ]);

    $session_type_options = [];
    foreach ($session_types->GetSessionTypesResult->SessionTypes->SessionType as $type) {
      if (!$this->trainingsMapping->sessionTypeIsActive($type->ID)) {
        continue;
      }
      $session_type_options[$type->ID] = $this->trainingsMapping->getSessionTypeLabel($type->ID, $type->Name);
    }

    return $session_type_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainers($session_type_id, $location_id) {
    /*
     * NOTE: MINDBODY API doesn't support filtering staff by location without specific date and time.
     * That's why we see all trainers, even courts.
     * see screenshot https://goo.gl/I9uNY2
     * see API Docs https://developers.mindbodyonline.com/Develop/StaffService
     */
    $booking_params = [
      'UserCredentials' => [
        'Username' => $this->credentials->get('user_name'),
        'Password' => $this->credentials->get('user_password'),
        'SiteIDs' => [$this->credentials->get('site_id')],
      ],
      'SessionTypeIDs' => [$session_type_id],
      'LocationIDs' => [$location_id],
      'StartDate' => date('Y-m-d', strtotime('today')),
      'EndDate' => date('Y-m-d', strtotime("today +3 weeks")),
    ];

    $bookable = $this->proxy->call('AppointmentService', 'GetBookableItems', $booking_params);

    $trainer_options = ['all' => $this->t('All')];
    if (!empty($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem)) {
      foreach ($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem as $bookable_item) {
        $trainer_options[$bookable_item->Staff->ID] = $bookable_item->Staff->Name;
      }
    }

    return $trainer_options;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Use a service instead of direct queries.
   */
  public function getTrainerName($trainer) {
    $trainer_name = '';
    $mapping_id = $this->entityQuery
      ->get('mapping')
      ->condition('type', 'trainer')
      ->condition('field_mindbody_trainer_id', $trainer)
      ->execute();
    $mapping_id = reset($mapping_id);
    /* @var \Drupal\ymca_mappings\MappingInterface $mapping */
    if (is_numeric($mapping_id) && $mapping = $this->entityTypeManager->getStorage('mapping')->load($mapping_id)) {
      $name = explode(', ', $mapping->getName());
      if (isset($name[0]) && isset($name[0])) {
        $trainer_name = $name[1] . ' ' . $name[0];
      }
    }

    return $trainer_name;
  }

  /**
   * {@inheritdoc}
   */
  public static function getTimeOptions() {
    $time_options = [
      '12 am', '1 am', '2 am', '3 am', '4 am', '5 am', '6 am', '7 am', '8 am', '9 am', '10 am', '11 am',
      '12 pm', '1 pm', '2 pm', '3 pm', '4 pm', '5 pm', '6 pm', '7 pm', '8 pm', '9 pm', '10 pm', '11 pm', '12 am',
    ];

    $possible_hours = range(static::MIN_TIME_RANGE, static::MAX_TIME_RANGE);
    $possible_hours_keyed = array_combine($possible_hours, $possible_hours);

    $time_options = array_intersect_key($time_options, $possible_hours_keyed);

    return $time_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisabledMarkup() {
    $markup = [];
    $block_id = $this->settings->get('disabled_form_block_id');
    $block = $this->entityTypeManager->getStorage('block_content')->load($block_id);
    if (!is_null($block)) {
      $view_builder = $this->entityTypeManager->getViewBuilder('block_content');
      $markup = [
        '#prefix' => '',
        'block' => $view_builder->view($block),
        '#suffix' => '',
      ];
    }

    return $markup;
  }

  /**
   * Custom hash salt getter.
   *
   * @return string
   *   String to be used as a hash salt.
   */
  public static function getHashSalt() {
    return \Drupal::config('system.site')->get('uuid');
  }

  /**
   * Custom token generator.
   *
   * @param array $query
   *   Array of data usually taken form request object.
   *
   * @return string
   *   Generated token.
   */
  public static function getToken(array $query) {
    $data = [];
    foreach (static::getTokenArgs() as $key) {
      $data[$key] = (string) $query[$key];
    }

    return md5(serialize($data) . static::getHashSalt());
  }

  /**
   * Returns token args.
   *
   * @return array
   *   Array of strings.
   */
  public static function getTokenArgs() {
    return [
      'context',
      'location',
      'p',
      's',
      'trainer',
      'st',
      'et',
      'dr',
      'bid',
    ];
  }

  /**
   * Custom token validator.
   *
   * @param array $query
   *   Query array usually taken from a request object.
   *
   * @return bool
   *   Returns token validity.
   */
  public static function validateToken(array $query) {
    if (!isset($query['token'])) {
      return FALSE;
    }

    return $query['token'] == static::getToken($query);
  }

  /**
   * {@inheritdoc}
   */
  public function getDuration($session_type) {
    $all = $this->proxy->call('SiteService', 'GetSessionTypes', ['OnlineOnly' => FALSE]);
    foreach ($all->GetSessionTypesResult->SessionTypes->SessionType as $type) {
      if ($type->ID == $session_type) {
        return $type->DefaultTimeLength;
      }
    }
    return FALSE;
  }

}
