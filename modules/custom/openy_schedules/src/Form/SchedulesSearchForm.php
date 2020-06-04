<?php

namespace Drupal\openy_schedules\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\openy_repeat\RepeatManager;
use Drupal\openy_session_instance\Entity\SessionInstanceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the Schedules Sessions search Form.
 *
 * @ingroup openy_branch
 */
class SchedulesSearchForm extends FormBase {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The node object.
   *
   * @var NodeInterface
   */
  protected $node;

  /**
   * The state.
   *
   * @var array
   */
  protected $state;

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The SessionInstanceManager.
   *
   * @var \Drupal\openy_session_instance\SessionInstanceManagerInterface
   */
  protected $sessionInstanceManager;

  /**
   * Creates a new BranchSessionsForm.
   *
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param RequestStack $request_stack
   *   The request stack.
   * @param QueryFactory $entity_query
   *   The entity query factory.
   * @param EntityTypeManager $entity_type_manager
   *   The EntityTypeManager.
   * @param RepeatManager $session_instance_manager
   *   The SessionInstanceManager.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    QueryFactory $entity_query,
    EntityTypeManager $entity_type_manager,
    RepeatManager $session_instance_manager
  ) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->sessionInstanceManager = $session_instance_manager;

    $query = parent::getRequest();
    $parameters = $query->query->all();
    $today = new DrupalDateTime('now');
    $today = $today->format('m/d/Y');
    $state = [
      'location' => isset($parameters['location']) ? $parameters['location'] : 'All',
      'program' => isset($parameters['program']) ? $parameters['program'] : 'all',
      'category' => isset($parameters['category']) ? $parameters['category'] : 'all',
      'class' => isset($parameters['class']) ? $parameters['class'] : 'all',
      'date' => isset($parameters['date']) ? $parameters['date'] : $today,
      'time' => isset($parameters['time']) ? $parameters['time'] : 'all',
      'display' => isset($parameters['display']) ? $parameters['display'] : 0,
    ];
    $this->logger = $logger_factory->get('openy_schedules');
    $this->setRequestStack($request_stack);
    $this->node = $this->getRequest()->get('node');
    // Invoke hook_openy_schedule_search_form_states_pre_build_alter to changing init field value.
    \Drupal::moduleHandler()->alter('openy_schedule_search_form_states_pre_build', $state);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('request_stack'),
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('session_instance.manager')
    );
  }

  /**
   * Helper method retrieving the display theme.
   *
   * @return string
   *   The theme name to use.
   */
  public function getDisplay() {
    $display = $this->state['display'];
    switch ($display) {
      case 1:
        $theme = 'openy_schedules_main_class';
        break;

      default:
        $theme = 'openy_schedules_main';
        break;
    }
    return $theme;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_schedules_search_form';
  }

  /**
   * Helper method retrieving location options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  public function getLocationOptions() {
    static $options = [];

    if (!$options) {
      $options = [
        'All' => $this->t('All'),
        'branches' => [],
        'camps' => [],
      ];
      $map = [
        'branch' => 'branches',
        'camp' => 'camps',
      ];
      $query = $this->entityQuery
        ->get('node')
        ->condition('status', 1)
        ->condition('type', ['branch', 'camp'], 'IN');
      $entity_ids = $query->execute();
      $nodes = $this->entityTypeManager->getStorage('node')
        ->loadMultiple($entity_ids);
      foreach ($nodes as $id => $node) {
        $options[$map[$node->bundle()]][$id] = $node->getTitle();
      }
      // Remove empty option categories.
      foreach ($options as $key => $option) {
        if (empty($option)) {
          unset($options[$key]);
        }
      }
    }

    return $options;
  }

  /**
   * Helper method retrieving program options.
   *
   * @return array
   *   Array of program options to be used in form element.
   */
  public function getProgramOptions() {
    static $options = [];

    if (!$options) {
      $options = ['all' => $this->t('All')];
      $query = $this->entityQuery
        ->get('node')
        ->condition('status', 1)
        ->condition('type', 'program');
      $entity_ids = $query->execute();
      $nodes = $this->entityTypeManager->getStorage('node')
        ->loadMultiple($entity_ids);
      foreach ($nodes as $id => $node) {
        $options[$id] = $node->getTitle();
      }
    }

    return $options;
  }

  /**
   * Helper method retrieving category options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  public function getCategoryOptions() {
    static $options = [];
    $program = $this->state['program'];

    if (!$options) {
      $options = ['all' => $this->t('All')];
      $query = $this->entityQuery
        ->get('node')
        ->condition('status', 1)
        ->condition('type', 'program_subcategory')
        ->sort('title');
      if ($program && $program !== 'all') {
        $query->condition('field_category_program', $program);
      }
      $entity_ids = $query->execute();

      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($entity_ids);
      foreach ($entity_ids as $id) {
        $options[$id] = $nodes[$id]->getTitle();
      }
    }

    return $options;
  }

  /**
   * Helper method retrieving class options.
   *
   * @return array
   *   Array of class to be used in form element.
   */
  public function getClassOptions() {
    static $options = [];

    $category = $this->state['category'];
    if (!$options) {
      $options = ['all' => $this->t('All')];

      // Get activities ids.
      if ($category == 'all') {
        $categories_ids = array_keys($this->getCategoryOptions());
      }
      else {
        $categories_ids = [$category];
      }
      $query = $this->entityQuery
        ->get('node')
        ->condition('status', 1)
        ->condition('type', 'activity')
        ->condition('field_activity_category', $categories_ids, 'IN');
      $activities_ids = $query->execute();

      if ($activities_ids) {
        // Get classes.
        $query = $this->entityQuery
          ->get('node')
          ->condition('status', 1)
          ->condition('type', 'class')
          ->condition('field_class_activity', $activities_ids, 'IN')
          ->sort('title');
        $entity_ids = $query->execute();

        if ($entity_ids) {
          $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($entity_ids);
          foreach ($entity_ids as $id) {
            $options[$id] = $nodes[$id]->getTitle();
          }
        }
      }
    }

    return $options;
  }

  /**
   * Helper method retrieving time options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  public function getTimeOptions() {
    $options = [
      'all' => 'All'
    ];
    // Add options each half hour from 00:00:00 to 23:30:00 with values
    // 12:00:00 AM to 11:30:00 PM.
    $time = 18000;
    for ($i = 0; $i < 48; $i++) {
      $options[date('H:i:s', $time)] = date('g:i:s A', $time);
      $time += 1800;
    }
    return $options;
  }

  /**
   * Add cache Vary cache contexts on the listed query args.
   *
   * @param array $form
   */
  private function addCommonCacheValues(&$form) {
    $form['#cache'] = [
      'max-age' => 0,
      'contexts' => [
        'url.query_args:location',
        'url.query_args:program',
        'url.query_args:category',
        'url.query_args:date',
        'url.query_args:time',
        'url.query_args:display',
      ],
    ];
  }

  /**
   * Add openy_schedules library.
   *
   * @param array $form
   */
  private function addCommonLibraries(&$form) {
    $form['#attached'] = [
      'library' => [
        'openy_schedules/openy_schedules',
      ],
    ];
  }

  /**
   * Add form elements.
   *
   * @param array $form
   * @param array $values
   * @param \stdClass $options
   */
  private function addFormElements(array &$form, array $values) {
    $form['filter_controls'] = [
      '#markup' => '
          <div class="container controls-wrapper hidden-sm hidden-md hidden-lg">
          <a href="#" class="btn btn-link transparent-blue add-filters">' . $this->t('Add filters') . '</a>
          <a href="#" class="btn btn-link transparent-blue close-filters hidden">' . $this->t('Close filters') . '</a>
          </div>',
    ];

    $form['selects'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'container',
          'selects-container',
          'hidden-xs',
        ],
      ],
    ];

    $form['selects']['location'] = [
      '#type' => 'select',
      '#title' => $this->t('Location'),
      '#options' => $this->getLocationOptions(),
      '#prefix' => '<hr/>',
      '#default_value' => isset($values['location']) ? $values['location'] : 'All',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'schedules-search-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['selects']['program'] = [
      '#type' => 'select',
      '#title' => $this->t('Program'),
      '#options' => $this->getProgramOptions(),
      '#default_value' => isset($values['program']) ? $values['program'] : 'all',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'schedules-search-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['selects']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Sub-Program'),
      '#options' => $this->getCategoryOptions(),
      '#default_value' => isset($values['category']) ? $values['category'] : 'all',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'schedules-search-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['selects']['class'] = [
      '#type' => 'select',
      '#title' => $this->t('Class'),
      '#options' => $this->getClassOptions(),
      '#default_value' => isset($values['class']) ? $values['class'] : 'all',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'schedules-search-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['selects']['date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date'),
      '#default_value' => isset($values['date']) ? $values['date'] : '',
      '#attributes' => [
        'class' => ['openy-schedule-datepicker'],
      ],
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'schedules-search-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
        // Do not focus current input element after ajax finish.
        'disable-refocus' => TRUE,
      ],
    ];

    $form['selects']['time'] = [
      '#type' => 'select',
      '#title' => $this->t('Start Time:'),
      '#options' => $this->getTimeOptions(),
      '#default_value' => isset($values['time']) ? $values['time'] : 'all',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'schedules-search-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['selects']['display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Weekly View'),
      '#default_value' => isset($values['display']) ? $values['display'] : 0,
      '#title_display' => 'before',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'schedules-search-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    // If on Weekly View, default and disable time field.
    if (isset($values['display']) && $values['display']) {
      $form['selects']['time']['#disabled'] = TRUE;
      $form['selects']['time']['#default_value'] = 'all';
    }

    $form['selects']['button'] = [
      '#type' => 'button',
      '#prefix' => '<div class="actions-wrapper hidden-xs hidden-sm hidden-md hidden-lg">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => [
          'btn',
          'blue',
        ]
      ],
      '#value' => $this->t('Apply filters'),
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'method' => 'replace',
        'event' => 'click',
      ],
    ];

    $form['filters'] = [
      '#type' => 'container',
      '#prefix' => '<div class="filters-main-wrapper hidden-sm"><div class="container filters-container">',
      '#suffix' => '</div></div>',
      '#attributes' => [
        'class' => [
          'container',
          'filters-container',
        ],
      ],
      '#markup' => '',
      '#weight' => 99,
      'filters' => self::buildFilters($values),
    ];
  }

  /**
   * Update form user input.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \stdClass $options
   */
  protected function updateUserInput(FormStateInterface &$form_state, array $values) {
    $user_input = $form_state->getUserInput();

    if (!empty($user_input['location'])) {
      $user_input['location'] = isset($this->getLocationOptions()['branches'][$values['location']]) || isset($this->getLocationOptions()['camps'][$values['location']]) ? $values['location'] : 'All';
    }
    if (!empty($user_input['program'])) {
      $user_input['program'] = isset($this->getProgramOptions()[$values['program']]) ? $values['program'] : 'all';
    }
    if (!empty($user_input['category'])) {
      $user_input['category'] = isset($this->getCategoryOptions()[$values['category']]) ? $values['category'] : 'all';
    }
    if (!empty($user_input['class'])) {
      $user_input['class'] = isset($this->getClassOptions()[$values['class']]) ? $values['class'] : 'all';
    }
    if (!empty($user_input['time'])) {
      $user_input['time'] = isset($this->getTimeOptions()[$values['time']]) ? $values['time'] : 'all';
    }
    if (!empty($user_input['display'])) {
      $user_input['display'] = !empty($values['display']) ? $values['display'] : 0;
    }

    $form_state->setUserInput($user_input);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      // Populate state data with user input, if exists.
      if ($form_state->getUserInput()) {
        foreach ($form_state->getUserInput() as $key => $value) {
          if (array_key_exists($key, $this->state)) {
            $this->state[$key] = $value;
          }
        }
      }
      // Populate form state with state data.
      if ($this->state) {
        foreach ($this->state as $key => $value) {
          if (!$form_state->hasValue($key)) {
            $form_state->setValue($key, $value);
          }
        }
      }

      $values = $form_state->getValues();

      if (!empty($form_state->getBuildInfo()['args'][1])) {
        $render_flag = $form_state->getBuildInfo()['args'][1];
      }
      else {
        $render_flag = 'full';
      }

      $this->addCommonCacheValues($form);
      $this->addCommonLibraries($form);

      if ($render_flag == 'full' || $render_flag == 'form') {
        $this->addFormElements($form, $values);
        $this->updateUserInput($form_state, $values);
        $form['#prefix'] = '<div id="schedules-search-form-wrapper">';
        $form['#suffix'] = '</div>';
      }

      if (!$form_state->getTriggeringElement() && ($render_flag == 'full' || $render_flag == 'list')) {
        $rendered_results = '';
        $formatted_results = '';
        $branch_hours = '';
        $renderer = \Drupal::service('renderer');
        $branch_hours = $this->buildBranchHours($form, $values);
        $branch_hours = $renderer->renderRoot($branch_hours);
        $formatted_results = $this->buildResults($form, $values);
        $formatted_results = $renderer->renderRoot($formatted_results);
        // TODO: replace with render arrays.
        $rendered_results = '
        <div id="schedules-search-listing-wrapper">
          <div class="branch-hours-wrapper clearfix">' . $branch_hours . '</div>
          <div class="results clearfix">' . $formatted_results . '</div>
        </div>
        ';

        if ($render_flag == 'list') {
          $form['#suffix'] = $rendered_results;
        }
        else {
          $form['#suffix'] = $form['#suffix'] . $rendered_results;
        }
      }
    }
    catch (Exception $e) {
      $this->logger->error('Failed to build the form. Message: %msg', ['%msg' => $e->getMessage()]);
    }

    return $form;
  }

  /**
   * Build filters.
   */
  public function buildFilters($parameters) {
    $filters_markup = '';

    $locationOptions = $this->getLocationOptions();
    $programOptions = $this->getProgramOptions();
    $categoryOptions = $this->getCategoryOptions();
    $classOptions = $this->getClassOptions();
    $timeOptions = $this->getTimeOptions();

    if ($parameters['location'] !== 'All') {
      if (!empty($locationOptions['branches'][$parameters['location']])) {
        $filters[$parameters['location']] = $locationOptions['branches'][$parameters['location']];
      }
      if (!empty($locationOptions['camps'][$parameters['location']])) {
        $filters[$parameters['location']] = $locationOptions['camps'][$parameters['location']];
      }
    }
    if ($parameters['program'] !== 'all' && !empty($programOptions[$parameters['program']])) {
      $filters[$parameters['program']] = $programOptions[$parameters['program']];
    }
    if ($parameters['category'] !== 'all' && !empty($categoryOptions[$parameters['category']])) {
      $filters[$parameters['category']] = $categoryOptions[$parameters['category']];
    }
    if ($parameters['class'] !== 'all' && !empty($classOptions[$parameters['class']])) {
      $filters[$parameters['class']] = $classOptions[$parameters['class']];
    }
    if (!empty($parameters['date'])) {
      $filters[$parameters['date']] = $parameters['date'];
    }
    if ($parameters['time'] !== 'all' && !empty($timeOptions[$parameters['time']])) {
      $filters[$parameters['time']] = $timeOptions[$parameters['time']];
    }
    if (!empty($filters)) {
      $filters_markup = [
        '#theme' => 'openy_schedules_subcategory_filters',
        '#filters' => $filters,
      ];
    }

    return $filters_markup;
  }

  /**
   * Build Branch Hours.
   */
  public function buildBranchHours(&$form, $parameters) {
    $markup = '';
    if (!$parameters['location'] || $parameters['location'] == 'All') {
      return $markup;
    }

    $locationOptions = $this->getLocationOptions();
    if (!empty($locationOptions['branches'][$parameters['location']])) {
      $id = $parameters['location'];
    }
    if (!empty($locationOptions['camps'][$parameters['location']])) {
      $id = $parameters['location'];
    }
    if (isset($id)) {
      $branch_hours = [];
      $timezone = drupal_get_user_timezone();
      $date = DrupalDateTime::createFromFormat('m/d/Y', $parameters['date'], $timezone);
      $date = strtolower($date->format('D'));
      /* @var $location \Drupal\node\Entity\Node */
      if ($location = $this->entityTypeManager->getStorage('node')->load($id)) {
        $form['#cache']['tags'] = !empty($form['#cache']['tags']) ? $form['#cache']['tags'] : [];
        $form['#cache']['tags'] = $form['#cache']['tags'] + $location->getCacheTags();
        if ($location->hasField('field_branch_hours')) {
          $field_branch_hours = $location->field_branch_hours;
          foreach ($field_branch_hours as $multi_hours) {
            $sub_hours = $multi_hours->getValue();
            if (!empty($sub_hours['hours_' . $date])) {
              $branch_hours['main']['hours'][] = $sub_hours['hours_' . $date];
            }
          }
        }
      }
    }
    if (!empty($branch_hours)) {
      $markup = [
        '#theme' => 'openy_branch_hours_block',
        '#branch_hours' => $branch_hours,
      ];
    }

    return $markup;
  }

  /**
   * Build results.
   */
  public function getSessions($parameters) {
    $locationOptions = $this->getLocationOptions();
    $programOptions = $this->getProgramOptions();
    $categoryOptions = $this->getCategoryOptions();
    $classOptions = $this->getClassOptions();

    $conditions = [];
    $location = $parameters['location'];

    if (isset($locationOptions['branches'][$location]) || isset($locationOptions['camps'][$location])) {
      $conditions['location'] = $location;
    }
    if ($parameters['class'] !== 'all' && !empty($classOptions[$parameters['class']])) {
      $conditions['class'] = $parameters['class'];
    }
    if ($parameters['program'] !== 'all' && !empty($programOptions[$parameters['program']])) {
      $conditions['field_si_program'] = $parameters['program'];
    }
    if ($parameters['category'] !== 'all' && !empty($categoryOptions[$parameters['category']])) {
      $conditions['field_si_program_subcategory'] = $parameters['category'];
    }

    // Format for weekly view.
    if (!empty($parameters['display'])) {
      $conditions['from'] = strtotime($parameters['date'] . 'T00:00:00');
      $conditions['to'] = strtotime($parameters['date'] . 'T23:59:59 + 6 days');
    }
    else {
      $date_string = $parameters['date'] . ' 00:00:00';
      if (!empty($parameters['time']) && $parameters['time'] !== 'all') {
        $date_string = $parameters['date'] . ' ' . $parameters['time'];
      }
      $conditions['from'] = strtotime($date_string);
      $conditions['to'] = strtotime($parameters['date'] . ' next day');
    }

    // Fetch session occurrences.
    $session_instances = $this->sessionInstanceManager->getSessionInstancesByParams($conditions);

    return $session_instances;
  }

  /**
   * Build results.
   */
  public function buildResults(&$form, $parameters) {
    $session_instances = $this->getSessions($parameters);
    $content = [];
    $title_date = DrupalDateTime::createFromFormat('m/d/Y', $parameters['date']);
    $title_date_week_to = DrupalDateTime::createFromTimestamp(strtotime($parameters['date'] . 'T23:59:59 + 6 days'));
    $title_date_week_from = $title_date->format('n/j/Y');
    $title_date_week_to = $title_date_week_to->format('n/j/Y');
    $title_date = $title_date->format('F j, Y');
    // Default results title.
    $title_results = $this->t('Classes for %date', ['%date' => $title_date]);
    $title_results_week = $this->t('Classes from %from to %to', [
      '%from' => $title_date_week_from,
      '%to' => $title_date_week_to,
    ]);

    foreach ($session_instances as $session_instance) {
      /* @var $session_instance \Drupal\openy_session_instance\Entity\SessionInstanceInterface */
      $session = $session_instance->session->referencedEntities();
      $session = reset($session);
      // Check for class arg.
      $classOptions = $this->getClassOptions();
      if ($parameters['class'] !== 'all' && !empty($classOptions[$parameters['class']])) {
        $class = $this->entityTypeManager->getStorage('node')
          ->load($parameters['class']);
      }
      else {
        $class = $session_instance->class->referencedEntities();
        $class = reset($class);
      }
      // Included in membership logic.
      $included_in_membership = TRUE;
      if ($member_price_items = $session->field_session_mbr_price->getValue()) {
        $member_price = (float) reset($member_price_items)['value'];
        if ($member_price) {
          $included_in_membership = FALSE;
        }
      }
      // Ticket required logic.
      $ticket_required = FALSE;
      if ($ticket_required_items = $session->field_session_ticket->getValue()) {
        $ticket_required = (int) reset($ticket_required_items)['value'];
      }
      if (isset($parameters['display']) && $parameters['display']) {
        $timestamp = DrupalDateTime::createFromTimestamp($session_instance->getTimestamp());
        $day = $timestamp->format('D n/j/Y');
        $time_from = $timestamp->format('g:i a');
        $timestamp_to = DrupalDateTime::createFromTimestamp($session_instance->getTimestampTo());
        $day_to = $timestamp_to->format('n/j/Y');
        $time_to = $timestamp_to->format('g:i a');
        $time = $time_from;
        if ($time_from !== $time_to) {
          $time .= ' - ' . $time_to;
        }
        // Set day from on first session.
        if (!isset($day_from)) {
          $day_from = $timestamp->format('n/j/Y');
        }
        $title_results = $title_results_week;

        $content[$day][$class->id() . '--' . $time] = [
          'label' => $class->getTitle(),
          'time' => $time,
          'time_from' => $session_instance->getTimestamp(),
          'description' => strip_tags(text_summary($class->field_class_description->value, $class->field_class_description->format, 140)),
          'included_in_membership' => $included_in_membership,
          'ticket_required' => $ticket_required,
          'url' => Url::fromUri('internal:/node/' . $class->id(), [
            'query' => [
              'location' => $session_instance->location->target_id,
              'session' => $session_instance->session->target_id,
              'instance' => $session_instance->id(),
            ],
          ]),
        ];
      }
      else {
        $timestamp = DrupalDateTime::createFromTimestamp($session_instance->getTimestamp());
        $hour = $timestamp->format('g');
        $minutes = $timestamp->format('i');
        $minute = '00';
        if ($minutes >= 15) {
          $minute = '15';
        }
        if ($minutes >= 30) {
          $minute = '30';
        }
        if ($minutes >= 45 && $minutes <= 59) {
          $minute = '45';
        }
        $viewBuilder = $this->entityTypeManager->getViewBuilder('node');
        $rounded_time = $hour . ':' . $minute . ' ' . $timestamp->format('a');
        $content[$rounded_time][$session_instance->session->target_id] = [
          'label' => $class->getTitle(),
          'teaser' => $viewBuilder->view($class, 'teaser'),
          'included_in_membership' => $included_in_membership,
          'ticket_required' => $ticket_required,
          'url' => Url::fromUri('internal:/node/' . $class->id(), [
            'query' => [
              'location' => $session_instance->location->target_id,
              'session' => $session_instance->session->target_id,
              'instance' => $session_instance->id(),
            ],
          ]),
        ];
      }

      $form['#cache']['tags'] = isset($form['#cache']['tags']) && is_array($form['#cache']['tags']) ? $form['#cache']['tags'] : [];
      $form['#cache']['tags'] = $form['#cache']['tags'] + $session_instance->getCacheTags();
    }

    $formatted_results = [
      '#theme' => $this->getDisplay(),
      '#title' => $title_results,
      '#content' => $content,
    ];

    return $formatted_results;
  }

  /**
   * Custom ajax callback.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    $parameters = $form_state->getUserInput();
    // Remove empty/NULL display.
    if (empty($parameters['display'])) {
      unset($parameters['display']);
    }
    else {
      unset($parameters['time']);
    }
    $formatted_results = $this->buildResults($form, $parameters);
    $filters = self::buildFilters($parameters);
    $branch_hours = $this->buildBranchHours($form, $parameters);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#schedules-search-form-wrapper .selects-container', $form['selects']));
    $response->addCommand(new HtmlCommand('#schedules-search-listing-wrapper .results', $formatted_results));
    $response->addCommand(new HtmlCommand('#schedules-search-form-wrapper .filters-container', $filters));
    $response->addCommand(new HtmlCommand('#schedules-search-listing-wrapper .branch-hours-wrapper', $branch_hours));
    $response->addCommand(new InvokeCommand(NULL, 'schedulesAjaxAction', [$parameters]));
    $form_state->setRebuild();
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

}
