<?php

namespace Drupal\ymca_mindbody\Form;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\ymca_mindbody\YmcaMindbodyRequestGuard;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ymca_mindbody\YmcaMindbodyTrainingsMapping;

/**
 * Provides the Personal Training Form.
 *
 * @ingroup ymca_mindbody
 */
class MindbodyPTForm extends FormBase {

  /**
   * Default value for start time on PT form.
   */
  const DEFAULT_START_TIME = 6;

  /**
   * Default value for end time on PT form.
   */
  const DEFAULT_END_TIME = 23;

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
   * MindbodyPTForm constructor.
   *
   * @param MindbodyCacheProxyInterface $cache_proxy
   *   Mindbody cache proxy.
   * @param YmcaMindbodyTrainingsMapping $trainings_mapping
   *   Mindbody cache proxy.
   */
  public function __construct(MindbodyCacheProxyInterface $cache_proxy, YmcaMindbodyTrainingsMapping $trainings_mapping, YmcaMindbodyRequestGuard $request_guard, array $state = []) {
    $this->proxy = $cache_proxy;
    $this->credentials = $this->config('mindbody.settings');
    $this->state = $state;
    $this->trainingsMapping = $trainings_mapping;
    $this->settings = $this->config('ymca_mindbody.settings');
    $this->requestGuard = $request_guard;
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
    );
    return new static(
      $container->get('mindbody_cache_proxy.client'),
      $container->get('ymca_mindbody.trainings_mapping'),
      $container->get('ymca_mindbody.request_guard'),
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
  protected function getDisabledMarkup() {
    $markup = '';
    $block_id = $this->config('ymca_mindbody.settings')->get('disabled_form_block_id');
    $block = \Drupal::entityTypeManager()->getStorage('block_content')->load($block_id);
    if (!is_null($block)) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('block_content');
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

    return $time_options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
          $values['step'] = 2;
          break;

        case 'mb_program':
          unset($values['mb_session_type']);
          unset($values['mb_trainer']);
          $values['step'] = 3;
          break;

        case 'mb_session_type':
          unset($values['mb_trainer']);
          $values['step'] = 4;
          break;

        case 'ok':
          $values['step'] = 5;
          break;
      }
    }

    // Pre-populate values if so.
    $pre_populated_location = FALSE;
    $query = \Drupal::request()->query->all();
    if (isset($query['location']) && is_numeric($query['location'])) {
      // For security reasons check if provided value exists in the mapping.
      $mapping_id = \Drupal::entityQuery('mapping')
        ->condition('field_mindbody_id', $query['location'])
        ->execute();
      if (!empty($mapping_id)) {
        $values['mb_location'] = $query['location'];
        $pre_populated_location = TRUE;
        !isset($values['step']) ? $values['step'] = 2 : '';
      }
    }
    if (isset($query['trainer'])) {
      // For security reasons check if provided value exists in the mapping.
      $mapping_id = \Drupal::entityQuery('mapping')
        ->condition('field_mindbody_trainer_id', $query['trainer'])
        ->execute();
      if (!empty($mapping_id)) {
        $values['mb_trainer'] = $query['trainer'];
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
        '#markup' => $this->getElementHeaderMarkup('location', $location_options[$values['mb_location']], $pre_populated_location),
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
    if (isset($values['location']) && isset($values['program']) && isset($values['session_type']) && isset($values['trainer']) && isset($values['start_date']) && isset($values['end_date'])) {
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
      $booking_params['EndDate'] = date('Y-m-d', strtotime($values['end_date']));

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
      foreach ($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem as $bookable_item) {
        // Additionally filter results by time.
        $start_time = date('G', strtotime($bookable_item->StartDateTime));
        $end_time = date('G', strtotime($bookable_item->EndDateTime));
        if (in_array($start_time, $time_range) && in_array($end_time, $time_range)) {
          $group_date = date('F d, Y', strtotime($bookable_item->StartDateTime));
          $days[$group_date]['weekday'] = date('l', strtotime($bookable_item->StartDateTime));
          $days[$group_date]['trainers'][$bookable_item->Staff->Name][] = [
            'is_available' => TRUE,
            'slot' => date('h:i a', strtotime($bookable_item->StartDateTime)) . ' - ' . date('h:i a', strtotime($bookable_item->EndDateTime)),
            // To Do: Add bookable link.
            'href' => '#',
          ];
        }
      }

      if ($values['trainer'] == 'all') {
        $trainer_name = $this->t('all trainers');
      }
      else {
        $trainers = $this->getTrainers($values['session_type'], $values['location']);
        $trainer_name = isset($trainers[$values['trainer']]) ? $trainers[$values['trainer']] : '';
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
        '#base_path' => base_path(),
        '#days' => $days,
      ];

      return $search_results;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['mb_start_time']) && isset($values['mb_end_time'])  && $values['mb_start_time'] >= $values['mb_end_time']) {
      $form_state->setErrorByName('mb_start_time', $this->t('Please check time range.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
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
        'end_date'     => $values['mb_end_date']['date'],
      ];
      $form_state->setRedirect(
        'ymca_mindbody.pt.results',
        [],
        ['query' => $params]
      );
    }
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
      if ($location->HasClasses != TRUE) {
        continue;
      }
      $location_options[$location->ID] = $location->Name;
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
    foreach ($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem as $bookable_item) {
      $trainer_options[$bookable_item->Staff->ID] = $bookable_item->Staff->Name;
    }

    return $trainer_options;
  }

}
