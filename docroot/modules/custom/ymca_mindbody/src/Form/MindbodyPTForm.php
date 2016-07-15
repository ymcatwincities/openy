<?php

namespace Drupal\ymca_mindbody\Form;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\mindbody\MindbodyException;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\ymca_mindbody\YmcaMindbodyRequestGuard;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\ymca_mindbody\YmcaMindbodyTrainingsMapping;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the Personal Training Form.
 *
 * @ingroup ymca_mindbody
 */
class MindbodyPTForm extends FormBase {

  /**
   * Default value for start time on PT form.
   */
  const DEFAULT_START_TIME = 5;

  /**
   * Default value for end time on PT form.
   */
  const DEFAULT_END_TIME = 22;

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
   * Mindbody Proxy.
   *
   * @var MindbodyCacheProxyInterface
   */
  protected $proxy;

  /**
   * Credentials.
   *
   * @var ImmutableConfig
   */
  protected $credentials;

  /**
   * State storage.
   *
   * @var array
   */
  protected $stateStorage;

  /**
   * Training mapping service.
   *
   * @var YmcaMindbodyTrainingsMapping
   */
  protected $trainingsMapping;

  /**
   * Ymca Mindbody settings.
   *
   * @var ImmutableConfig
   */
  protected $settings;

  /**
   * Mindbody request guard.
   *
   * @var YmcaMindbodyRequestGuard
   */
  protected $requestGuard;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * MindbodyPTForm constructor.
   *
   * @param MindbodyCacheProxyInterface $cache_proxy
   *   The Mindbody cache proxy.
   * @param YmcaMindbodyTrainingsMapping $trainings_mapping
   *   The Mindbody training mapping .
   * @param YmcaMindbodyRequestGuard $request_guard
   *   The Mindbody request guard.
   * @param QueryFactory $entity_query
   *   The entity query factory.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The entity type manager.
   * @param array $state
   *   State.
   */
  public function __construct(
      MindbodyCacheProxyInterface $cache_proxy,
      YmcaMindbodyTrainingsMapping $trainings_mapping,
      YmcaMindbodyRequestGuard $request_guard,
      QueryFactory $entity_query,
      EntityTypeManagerInterface $entity_type_manager,
      LoggerChannelFactoryInterface $logger_factory,
      array $state = []
    ) {
    $this->proxy = $cache_proxy;
    $this->credentials = $this->config('mindbody.settings');
    $this->trainingsMapping = $trainings_mapping;
    $this->settings = $this->config('ymca_mindbody.settings');
    $this->requestGuard = $request_guard;
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('ymca_mindbody');

    try {
      if (!$this->requestGuard->validateSearchCriteria($state)) {
        $state = [];
      }
    }
    catch (MindbodyException $e) {
      $this->logger->error('Failed to validate search criteria: %msg', ['%msg' => $e->getMessage()]);
      $state = [];
    }
    $this->state = $state;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $query = \Drupal::request()->query->all();
    $state = array(
      'step' => isset($query['step']) && is_numeric($query['step']) ? $query['step'] : NULL,
      'mb_location' => isset($query['mb_location']) && is_numeric($query['mb_location']) ? $query['mb_location'] : NULL,
      'mb_program' => isset($query['mb_program']) && is_numeric($query['mb_program']) ? $query['mb_program'] : NULL,
      'mb_session_type' => isset($query['mb_session_type']) && is_numeric($query['mb_session_type']) ? $query['mb_session_type'] : NULL,
      'mb_trainer' => isset($query['mb_trainer']) && is_numeric($query['mb_trainer']) ? $query['mb_trainer'] : NULL,
      'mb_start_date' => isset($query['mb_start_date']) ? $query['mb_start_date'] : NULL,
      'mb_end_date' => isset($query['mb_end_date']) ? $query['mb_end_date'] : NULL,
      'mb_start_time' => isset($query['mb_start_time']) && is_numeric($query['mb_start_time']) ? $query['mb_start_time'] : NULL,
      'mb_end_time' => isset($query['mb_end_time']) && is_numeric($query['mb_end_time']) ? $query['mb_end_time'] : NULL,
      'prepopulated_location' => FALSE,
    );

    if (isset($query['context'], $query['location']) && is_numeric($query['location'])) {
      $state['context'] = $query['context'];
      $state['location'] = $query['location'];
      $state['mb_location'] = $query['location'];
      $state['prepopulated_location'] = TRUE;
    }
    if (isset($query['context'], $query['trainer']) && $query['context'] == 'trainer' && is_numeric($query['trainer'])) {
      $state['trainer'] = $query['trainer'];
      $state['mb_trainer'] = $query['trainer'];
      $state['prepopulated_trainer'] = TRUE;
    }

    // Prevent corrupted remote calls on corrupted page urls.
    if (!isset($state['mb_location'])) {
      $state['step'] = 1;
    }
    elseif (!isset($state['mb_program'])) {
      $state['step'] = 2;
    }
    elseif (!isset($state['mb_session_type'])) {
      $state['step'] = 3;
    }

    return new static(
      $container->get('mindbody_cache_proxy.client'),
      $container->get('ymca_mindbody.trainings_mapping'),
      $container->get('ymca_mindbody.request_guard'),
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $state
    );
  }

  /**
   * Check if the form has to be disabled.
   */
  private function isDisabled() {
    // Check whether the form was disabled by administrator.
    if ($this->settings->get('pt_form_disabled')) {
      return TRUE;
    }

    // Disable form if we exceed max requests to MindBody API.
    if (!$this->requestGuard->status()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Provides markup for disabled form.
   */
  public function getDisabledMarkup() {
    $markup = '';
    $block_id = $this->config('ymca_mindbody.settings')->get('disabled_form_block_id');
    $block = $this->entityTypeManager->getStorage('block_content')->load($block_id);
    if (!is_null($block)) {
      $view_builder = $this->entityTypeManager->getViewBuilder('block_content');
      $markup .= '<div class="container disabled-form">';
      $markup .= render($view_builder->view($block));
      $markup .= '</div>';
    }
    return $markup;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mindbody_pt';
  }

  /**
   * Helper method rendering header markup.
   *
   * @return string
   *   Header HTML-markup.
   */
  protected function getElementHeaderMarkup($type, $text, $disabled = FALSE) {
    $classes = 'header-row';
    $disabled ? $classes .= ' disabled' : '';

    switch ($type) {
      case 'location':
        $icon = 'location2';
        $id = 'location-wrapper';
        break;

      case 'program':
        $icon = 'training';
        $id = 'program-wrapper';
        break;

      case 'type':
        $icon = 'clock';
        $id = 'session-type-wrapper';
        break;

      case 'trainer':
        $icon = 'user';
        $id = 'trainer-wrapper';
        break;
    }
    $markup = '<div class="' . $classes . '"><div class="container">';
    $markup .= '<span class="icon icon-' . $icon . '"></span>';
    $markup .= '<span class="choice">' . $text . '</span>';
    $markup .= '<a href="#' . $id . '" class="change"><span class="icon icon-cog"></span>' . $this->t('Change') . '</a>';
    $markup .= '</div></div>';

    return $markup;
  }

  /**
   * Helper method retrieving time options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  protected function getTimeOptions() {
    $time_options = [
      '12 am', '1 am', '2 am', '3 am', '4 am', '5 am', '6 am', '7 am', '8 am', '9 am', '10 am', '11 am',
      '12 pm', '1 pm', '2 pm', '3 pm', '4 pm', '5 pm', '6 pm', '7 pm', '8 pm', '9 pm', '10 pm', '11 pm', '12 am',
    ];

    foreach ($time_options as $key => $time) {
      if ($key < $this::MIN_TIME_RANGE || $key > $this::MAX_TIME_RANGE) {
        unset($time_options[$key]);
      }
    }

    return $time_options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      // Populate form state with state data.
      if ($this->state) {
        foreach ($this->state as $key => $value) {
          if (!$form_state->hasValue($key)) {
            $form_state->setValue($key, $value);
          }
        }
      }

      $values = $form_state->getValues();
      if ($trigger_element = $form_state->getTriggeringElement()) {
        switch ($trigger_element['#name']) {
          case 'mb_location':
            unset($values['mb_program']);
            unset($values['mb_session_type']);
            unset($values['mb_trainer']);
            $user_input = $form_state->getUserInput();
            unset($user_input['mb_program']);
            unset($user_input['mb_session_type']);
            unset($user_input['mb_trainer']);
            $form_state->setUserInput($user_input);
            $values['step'] = 2;
            break;

          case 'mb_program':
            unset($values['mb_session_type']);
            unset($values['mb_trainer']);
            $user_input = $form_state->getUserInput();
            unset($user_input['mb_session_type']);
            unset($user_input['mb_trainer']);
            $form_state->setUserInput($user_input);
            $values['step'] = 3;
            break;

          case 'mb_session_type':
            unset($values['mb_trainer']);
            $user_input = $form_state->getUserInput();
            unset($user_input['mb_trainer']);
            $form_state->setUserInput($user_input);
            $values['step'] = 4;
            break;

          case 'ok':
            $values['step'] = 5;
            break;
        }
      }

      if (!isset($values['step'])) {
        $values['step'] = 1;
      }

      $form['step'] = [
        '#type' => 'hidden',
        '#value' => $values['step'],
      ];

      // Vary on the listed query args.
      $form['#cache'] = [
        // Remove max-age when mindbody tags invalidation is done.
        'max-age' => 0,
        'contexts' => [
          'mindbody_state',
          'url.query_args:step',
          'url.query_args:mb_location',
          'url.query_args:mb_program',
          'url.query_args:mb_session_type',
          'url.query_args:mb_trainer',
          'url.query_args:mb_start_date',
          'url.query_args:mb_end_date',
          'url.query_args:mb_start_time',
          'url.query_args:mb_end_time',
        ],
      ];

      $form['#prefix'] = '<div id="mindbody-pt-form-wrapper" class="content step-' . $values['step'] . '">';
      $form['#suffix'] = '</div>';

      if ($this->isDisabled()) {
        $form['disable'] = ['#markup' => $this->getDisabledMarkup()];
        return $form;
      }

      $location_options = $this->getLocations();
      $form['mb_location'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Select Location'),
        '#options' => $location_options,
        '#default_value' => isset($values['mb_location']) ? $values['mb_location'] : '',
        '#prefix' => '<div id="location-wrapper" class="row"><div class="container">',
        '#suffix' => '</div></div>',
        '#description' => $this->t('You can only select 1 branch per search'),
        '#weight' => 2,
        '#ajax' => array(
          'callback' => array($this, 'rebuildAjaxCallback'),
          'wrapper' => 'mindbody-pt-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => array(
            'type' => 'throbber',
          ),
        ),
      );

      if ($values['step'] >= 2) {
        $form['mb_location_header'] = array(
          '#markup' => $this->getElementHeaderMarkup('location', $location_options[$values['mb_location']], $this->state['prepopulated_location']),
          '#weight' => 1,
        );
        $program_options = $this->getPrograms();
        $form['mb_program'] = array(
          '#type' => 'radios',
          '#title' => $this->t('Appointment Type'),
          '#options' => $program_options,
          '#default_value' => isset($values['mb_program']) ? $values['mb_program'] : '',
          '#prefix' => '<div id="program-wrapper" class="row"><div class="container">',
          '#suffix' => '</div></div>',
          '#weight' => 4,
          '#ajax' => array(
            'callback' => array($this, 'rebuildAjaxCallback'),
            'wrapper' => 'mindbody-pt-form-wrapper',
            'method' => 'replace',
            'event' => 'change',
            'effect' => 'fade',
            'progress' => array(
              'type' => 'throbber',
            ),
          ),
        );
      }

      if ($values['step'] >= 3) {
        $form['mb_program_header'] = array(
          '#markup' => $this->getElementHeaderMarkup('program', $program_options[$values['mb_program']]),
          '#weight' => 3,
        );
        $session_type_options = $this->getSessionTypes($values['mb_program']);
        $form['mb_session_type'] = array(
          '#type' => 'radios',
          '#title' => $this->t('Training type'),
          '#options' => $session_type_options,
          '#default_value' => isset($values['mb_session_type']) ? $values['mb_session_type'] : '',
          '#prefix' => '<div id="session-type-wrapper" class="row"><div class="container">',
          '#suffix' => '</div></div>',
          '#weight' => 6,
          '#ajax' => array(
            'callback' => array($this, 'rebuildAjaxCallback'),
            'wrapper' => 'mindbody-pt-form-wrapper',
            'event' => 'change',
            'effect' => 'fade',
            'progress' => array(
              'type' => 'throbber',
            ),
          ),
        );
      }

      if ($values['step'] >= 4) {
        $form['mb_session_type_header'] = array(
          '#markup' => $this->getElementHeaderMarkup('type', $session_type_options[$values['mb_session_type']]),
          '#weight' => 5,
        );
        $trainer_options = $this->getTrainers($values['mb_session_type'], $values['mb_location']);

        $form['mb_trainer'] = array(
          '#access' => !empty($this->state['prepopulated_trainer']) ? FALSE : TRUE,
          '#type' => 'select',
          '#title' => $this->t('Trainer'),
          '#options' => $trainer_options,
          '#default_value' => isset($values['mb_trainer']) ? $values['mb_trainer'] : 'all',
          '#prefix' => '<div id="trainer-wrapper" class="row"><div class="container"><div class="col-sm-4">',
          '#suffix' => '</div></div></div>',
          '#weight' => 8,
        );

        $form['actions']['#weight'] = 20;
        $form['actions']['#prefix'] = '<div id="actions-wrapper" class="row"><div class="container"><div class="col-sm-12">';
        $form['actions']['#suffix'] = '</div></div></div>';

        $timezone = drupal_get_user_timezone();
        // Initially start date defined as today.
        $start_date = DrupalDateTime::createFromTimestamp(REQUEST_TIME, $timezone);
        if (!empty($values['mb_start_date'])) {
          $start_date = $values['mb_start_date'];
          if (!$start_date instanceof DrupalDateTime) {
            $start_date = DrupalDateTime::createFromFormat('n/d/y', $values['mb_start_date']['date'], $timezone);
          }
        }
        $start_date->setTime(0, 0, 0);

        // Initially end date defined as +5 days after start date.
        $end_date = DrupalDateTime::createFromTimestamp(REQUEST_TIME + 432000, $timezone);
        if (!empty($values['mb_end_date'])) {
          $end_date = $values['mb_end_date'];
          if (!$values['mb_end_date'] instanceof DrupalDateTime) {
            $end_date = DrupalDateTime::createFromFormat('n/d/y', $values['mb_end_date']['date'], $timezone);
          }
        }
        $end_date->setTime(0, 0, 0);

        $form['mb_date'] = [
          '#type' => 'fieldset',
          '#prefix' => '<div id="when-wrapper" class="row"><div class="container"><div class="col-sm-12">',
          '#suffix' => '</div></div></div>',
          '#weight' => 9,
        ];
        $form['mb_date']['mb_start_time'] = [
          '#type' => 'select',
          '#title' => $this->t('Time range'),
          '#options' => $this->getTimeOptions(),
          '#default_value' => isset($values['mb_start_time']) ? $values['mb_start_time'] : $this::DEFAULT_START_TIME,
          '#suffix' => '<span class="dash">—</span>',
          '#weight' => 9,
        ];
        $form['mb_date']['mb_end_time'] = [
          '#type' => 'select',
          '#title' => '',
          '#options' => $this->getTimeOptions(),
          '#default_value' => isset($values['mb_end_time']) ? $values['mb_end_time'] : $this::DEFAULT_END_TIME,
          '#weight' => 9,
        ];
        $form['mb_date']['mb_start_date'] = [
          '#type' => 'datetime',
          '#date_date_format' => 'n/d/y',
          '#title' => $this->t('Date range'),
          '#suffix' => '<span class="dash">—</span>',
          '#default_value' => $start_date,
          '#date_time_element' => 'none',
          '#date_date_element' => 'text',
          '#weight' => 9,
        ];
        $form['mb_date']['mb_end_date'] = [
          '#type' => 'datetime',
          '#date_date_format' => 'n/d/y',
          '#title' => '',
          '#default_value' => $end_date,
          '#date_time_element' => 'none',
          '#date_date_element' => 'text',
          '#weight' => 9,
        ];

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Search'),
        );
      }
    }
    catch (MindbodyException $e) {
      $form['disabled']['#markup'] = $this->getDisabledMarkup();
      $this->logger->error('Failed to build the form. Message: %msg', ['%msg' => $e->getMessage()]);
    }

    return $form;
  }

  /**
   * Custom ajax callback.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Retrieves search results by given filters.
   *
   * @param array $values
   *   Array of filters.
   *
   * @return mixed
   *   Renderable array of results or NULL.
   */
  public function getSearchResults(array $values) {
    if (!isset($values['location'], $values['program'], $values['session_type'], $values['trainer'], $values['start_date'], $values['end_date'])) {
      $link = Link::createFromRoute($this->t('Start your search again'), 'ymca_mindbody.pt');
      return [
        '#prefix' => '<div class="row mindbody-search-results-content">
          <div class="container">
            <div class="day col-sm-12">',
        '#markup' => t('We couldn\'t complete your search. !search_link.', array('!search_link' => $link->toString())),
        '#suffix' => '</div></div></div>',
      ];
    }

    $booking_params = [
      'UserCredentials' => [
        'Username' => $this->credentials->get('user_name'),
        'Password' => $this->credentials->get('user_password'),
        'SiteIDs' => [$this->credentials->get('site_id')],
      ],
      'SessionTypeIDs' => [$values['session_type']],
      'LocationIDs' => [$values['location']],
    ];

    if (!empty($values['trainer']) && $values['trainer'] != 'all') {
      $booking_params['StaffIDs'] = array($values['trainer']);
    }
    $booking_params['StartDate'] = date('Y-m-d', strtotime($values['start_date']));
    $valid_end_date = $this->getValidEndDate($values['start_date'], $values['end_date']);
    $booking_params['EndDate'] = date('Y-m-d', strtotime($valid_end_date));

    $bookable = $this->proxy->call('AppointmentService', 'GetBookableItems', $booking_params);

    $time_options = $this->getTimeOptions();
    $start_time = $time_options[$values['start_time']];
    $end_time = $time_options[$values['end_time']];

    foreach ($time_options as $key => $option) {
      if ($option == $start_time) {
        $start_index = $key;
      }
      if ($option == $end_time) {
        $end_index = $key;
      }
    }
    $time_range = range($start_index, $end_index);

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

          $group_date = date('F d, Y', strtotime($bookable_item->StartDateTime));
          $days[$group_date]['weekday'] = date('l', strtotime($bookable_item->StartDateTime));
          // Add bookable item id if it isn't provided by Mindbody API.
          if (!$bookable_item->ID) {
            $bookable_item->ID = md5(serialize($bookable_item));
          }
          $options = [
            'attributes' => [
              'class' => [
                'use-ajax',
                $bookable_item->ID == $values['bookable_item_id'] ? 'highlight-item' : '',
              ],
              'data-dialog-type' => 'modal',
              'id' => 'bookable-item-' . $bookable_item->ID,
            ],
            'html' => TRUE,
          ];
          $query = ['bookable_item_id' => $bookable_item->ID] + $values;
          $query['token'] = $this::getToken($query);
          $options['query'] = $query;

          $text = new FormattableMarkup('<span class="icon icon-clock"></span> !from - !to', [
            '!from' => date('h:i a', strtotime($bookable_item->StartDateTime)),
            '!to' => date('h:i a', strtotime($bookable_item->EndDateTime)),
          ]);
          $link = Link::createFromRoute($text, 'ymca_mindbody.pt.book', [], $options);

          $days[$group_date]['trainers'][$bookable_item->Staff->Name][] = $link;
        }
      }
    }

    if ($values['trainer'] == 'all') {
      $trainer_name = $this->t('all trainers');
    }
    else {
      $trainer_name = $this->getTrainerName($values['trainer']);
    }

    $time_options = $this->getTimeOptions();
    $start_time = $time_options[$values['start_time']];
    $end_time = $time_options[$values['end_time']];
    $start_date = date('n/d/Y', strtotime($values['start_date']));
    $end_date = date('n/d/Y', strtotime($values['end_date']));
    $datetime = '<div><span class="icon icon-calendar"></span><span>' . $this->t('Time:') . '</span> ' . $start_time . ' - ' . $end_time . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div><div><span>' . $this->t('Date:') . '</span> ' . $start_date . ' - ' . $end_date . '</div>';

    $locations = $this->getLocations();
    $location_name = isset($locations[$values['location']]) ? $locations[$values['location']] : '';
    $programs = $this->getPrograms();
    $program_name = isset($programs[$values['program']]) ? $programs[$values['program']] : '';
    $session_types = $this->getSessionTypes($values['program']);
    $session_type_name = isset($session_types[$values['session_type']]) ? $session_types[$values['session_type']] : '';

    $telephone = '';
    $mapping_id = $this->entityQuery
      ->get('mapping')
      ->condition('type', 'location')
      ->condition('field_mindbody_id', $values['location'])
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
        'mb_location' => $values['location'],
        'mb_program' => $values['program'],
        'mb_session_type' => $values['session_type'],
        'mb_trainer' => $values['trainer'],
        'mb_start_date' => ['date' => $values['start_date']],
        'mb_end_date' => ['date' => $values['end_date']],
        'mb_start_time' => $values['start_time'],
        'mb_end_time' => $values['end_time'],
      ],
    ];
    if (isset($values['context'])) {
      $options['query']['context'] = $values['context'];
      if (isset($values['location'])) {
        $options['query']['location'] = $values['location'];
      }
      if (isset($values['trainer']) && $values['trainer'] != 'all') {
        $options['query']['trainer'] = $values['trainer'];
      }
    }

    $time_options = $this->getTimeOptions();
    $start_time = $time_options[$values['start_time']];
    $end_time = $time_options[$values['end_time']];
    $start_date = date('n/d/Y', strtotime($values['start_date']));
    $end_date = date('n/d/Y', strtotime($values['end_date']));
    $datetime = '<div><span class="icon icon-calendar"></span><span>' . $this->t('Time:') . '</span> ' . $start_time . ' - ' . $end_time . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div><div><span>' . $this->t('Date:') . '</span> ' . $start_date . ' - ' . $end_date . '</div>';

    $locations = $this->getLocations();
    $location_name = isset($locations[$values['location']]) ? $locations[$values['location']] : '';
    $programs = $this->getPrograms();
    $program_name = isset($programs[$values['program']]) ? $programs[$values['program']] : '';
    $session_types = $this->getSessionTypes($values['program']);
    $session_type_name = isset($session_types[$values['session_type']]) ? $session_types[$values['session_type']] : '';

    $options = [
      'query' => [
        'step' => 4,
        'mb_location' => $values['location'],
        'mb_program' => $values['program'],
        'mb_session_type' => $values['session_type'],
        'mb_trainer' => $values['trainer'],
        'mb_start_date' => ['date' => $values['start_date']],
        'mb_end_date' => ['date' => $values['end_date']],
        'mb_start_time' => $values['start_time'],
        'mb_end_time' => $values['end_time'],
      ],
    ];

    $search_results = [
      '#theme' => 'mindbody_results_content',
      '#location' => $location_name,
      '#program' => $program_name,
      '#session_type' => $session_type_name,
      '#trainer' => $trainer_name,
      '#datetime' => $datetime,
      '#back_link' => Url::fromRoute('ymca_mindbody.pt', [], $options),
      '#start_again_link' => Url::fromRoute('ymca_mindbody.pt'),
      '#base_path' => base_path(),
      '#telephone' => $telephone,
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['mb_start_time']) && isset($values['mb_end_time'])  && $values['mb_start_time'] >= $values['mb_end_time']) {
      $form_state->setErrorByName('mb_start_time', $this->t('Please check time range.'));
    }

    // Validate date range.
    if ($values['step'] == 4) {
      if (!isset($values['mb_start_date'], $values['mb_end_date'])) {
        $form_state->setErrorByName('mb_start_date', $this->t('Please provide valid date range.'));
      }

      if (isset($values['mb_start_date'], $values['mb_end_date'])) {
        $start = $values['mb_start_date']->format('U');
        $end = $values['mb_end_date']->format('U');
        if ($start > $end) {
          $form_state->setErrorByName('mb_start_date', $this->t('Please provide valid date range.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $this->state;
    $values = $form_state->getUserInput();
    if (!isset($values['mb_trainer']) && isset($query['trainer'])) {
      $values['mb_trainer'] = $query['trainer'];
    }
    if (!empty($values['mb_location']) &&
      !empty($values['mb_program']) &&
      !empty($values['mb_session_type']) &&
      !empty($values['mb_trainer']) &&
      !empty($values['mb_start_time']) &&
      !empty($values['mb_end_time']) &&
      !empty($values['mb_start_date']) &&
      !empty($values['mb_end_date'])) {
      $params = [
        'location'     => $values['mb_location'],
        'program'      => $values['mb_program'],
        'session_type' => $values['mb_session_type'],
        'trainer'      => $values['mb_trainer'],
        'start_time'   => $values['mb_start_time'],
        'end_time'     => $values['mb_end_time'],
        'start_date'   => $values['mb_start_date']['date'],
        'end_date'     => $this->getValidEndDate($values['mb_start_date']['date'], $values['mb_end_date']['date']),
      ];
      if (isset($query['context'])) {
        $params['context'] = $query['context'];
      }
      $form_state->setRedirect(
        'ymca_mindbody.pt.results',
        [],
        ['query' => $params]
      );
    }
  }

  /**
   * Helper method returning valid end date.
   *
   * End date can't be farther than 2 weeks from start date.
   * The method returns the end date if it's closer than 2 weeks from the start
   * date, otherwise the start date + 2 weeks.
   *
   * @param string $start_date
   *   Start date in n/j/y format.
   * @param string $end_date
   *   End date in n/j/y format.
   *
   * @return string
   *   Valid date in n/j/y format.
   */
  private function getValidEndDate($start_date, $end_date) {
    $valid_end_date = $end_date;
    if (strtotime($end_date) - strtotime($start_date) > 86400 * 14) {
      $valid_end_date = date('n/j/y', strtotime($start_date . " +2 weeks"));
    }
    return $valid_end_date;
  }

  /**
   * Helper method retrieving location options to be used in form element.
   *
   * @return array
   *   Array of locations usable in #options attribute of form elements.
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
   * Helper method retrieving program options to be used in form element.
   *
   * @return array
   *   Array of programs usable in #options attribute of form elements.
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
   * Helper method retrieving session types options to be used in form element.
   *
   * @param int $program_id
   *   MindBody program id.
   *
   * @return array
   *   Array of session types usable in #options attribute of form elements.
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
   * Helper method retrieving trainer options to be used in form element.
   *
   * @param int $session_type_id
   *   MindBody session type id.
   * @param int $location_id
   *   MindBody location id.
   *
   * @return array
   *   Array of trainers usable in #options attribute of form elements.
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
      'location',
      'program',
      'session_type',
      'trainer',
      'start_time',
      'end_time',
      'start_date',
      'end_date',
      'bookable_item_id',
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
   * Helper method retrieving trainer name form mapping.
   *
   * @param int $trainer
   *   MindBody trainer id.
   *
   * @return string
   *   Trainer's name.
   */
  public function getTrainerName($trainer) {
    $trainer_name = '';
    $mapping_id = $this->entityQuery
      ->get('mapping')
      ->condition('type', 'trainer')
      ->condition('field_mindbody_trainer_id', $trainer)
      ->execute();
    $mapping_id = reset($mapping_id);
    if (is_numeric($mapping_id) && $mapping = $this->entityTypeManager->getStorage('mapping')->load($mapping_id)) {
      $name = explode(', ', $mapping->getName());
      if (isset($name[0]) && isset($name[0])) {
        $trainer_name = $name[1] . ' ' . $name[0];
      }
    }

    return $trainer_name;
  }

  /**
   * Returns timestamp in appropriate timezone. See $this::DEFAULT_TIMEZONE.
   */
  protected function getTimestampInTimezone($data) {
    $date = new DrupalDateTime($data, $this::DEFAULT_TIMEZONE);
    return $date->getTimestamp();
  }

}
