<?php

namespace Drupal\openy_group_schedules\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\openy_group_schedules\GroupexHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openy_group_schedules\GroupexScheduleFetcher;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Implements GroupEx Pro Full Form.
 */
class GroupexFormFull extends GroupexFormBase {

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
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The state of form.
   *
   * @var array
   */
  protected $state;

  /**
   * The GroupEx Pro Helper.
   *
   * @var \Drupal\openy_group_schedules\GroupexHelper
   */
  protected $groupexHelper;

  /**
   * Gropex pro schedule fetcher
   *
   * @var \Drupal\openy_group_schedules\GroupexScheduleFetcher
   */
  protected $scheduleFetcher;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * GroupexFormFull constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The entity type manager.
   * @param \Drupal\openy_group_schedules\GroupexHelper $groupex_helper
   *   The GroupEx Pro helper.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, GroupexHelper $groupex_helper, $scheduleFetcher, ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->groupexHelper = $groupex_helper;
    $this->scheduleFetcher = $scheduleFetcher;
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('ymca_mindbody');

    $query = $this->getRequest()->query->all();
    $request = $this->getRequest()->request->all();

    $state = [
      'location' => isset($query['location']) && is_numeric($query['location']) ? $query['location'] : NULL,
      'class' => isset($query['class']) ? $query['class'] : NULL,
      'category' => isset($query['category']) ? $query['category'] : NULL,
      'filter_length' => isset($query['filter_length']) ? $query['filter_length'] : NULL,
      'filter_date' => isset($query['filter_date']) ? $query['filter_date'] : NULL,
      'groupex_class' => isset($query['groupex_class']) ? $query['groupex_class'] : NULL,
      'filter_timestamp' => isset($query['filter_timestamp']) ? $query['filter_timestamp'] : NULL,
      'instructor' => isset($query['instructor']) ? $query['instructor'] : NULL,
      'view_mode' => isset($query['view_mode']) ? $query['view_mode'] : NULL,
    ];
    // If not empty this means that form creates after ajax callback.
    if (!empty($request)) {
      $state['class'] = isset($request['class_select']) ? $request['class_select'] : NULL;
      $state['location'] = isset($request['location_select']) ? $request['location_select'] : NULL;
      $state['category'] = isset($request['category_select']) ? $request['category_select'] : NULL;
      $state['filter_date'] = isset($request['date_select']) ? $request['date_select'] : NULL;
    }
    // Reset any 'class' and 'category' value if date has been changed.
    if (!empty($request) && $request['_triggering_element_name'] == 'date_select') {
      $state['class'] = NULL;
      $state['view_mode'] = NULL;
      $state['category'] = NULL;
    }
    // Try to fill location from request.
    if (!empty($state['location']) && !empty($request['location']) == 'date_select') {
      $state['location'] = $request['location'];
    }

    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('openy_group_schedules.helper'),
      $container->get('openy_group_schedules.schedule_fetcher'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groupex_form_full';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $locations = []) {
    $values = $form_state->getValues();
    $user_input = $form_state->getUserInput();
    $state = $this->state;
    $formatted_results = NULL;
    $conf = $this->configFactory->get('openy_group_schedules.settings');
    $max_age = is_numeric($conf->get('cache_max_age')) ? $conf->get('cache_max_age') : 3600;
    $instructors_options = [];
    $request_time = \Drupal::time()->getRequestTime();

    // Get location options.
    $this->locationOptions = $this->getOptions($this->request(['query' => ['locations' => TRUE]]), 'id', 'name');

    // Set location if value passed through form builder.
    if (is_numeric($locations)) {
      $state['location'] = $locations;
      $form['#attributes']['class'][] = 'branch-specific-form';
    }

// @todo Uncomment when ymca pagecontext module is added.
//    // Check if form printed on specific Location Schedules page.
//    if ($this->getRouteMatch()->getRouteName() == 'ymca_frontend.location_schedules') {
//      if ($site_section = $this->pageContext->getContext()) {
//        $mapping_id = \Drupal::entityQuery('mapping')
//          ->condition('type', 'location')
//          ->condition('field_location_ref', $site_section->id())
//          ->execute();
//        $mapping_id = reset($mapping_id);
//        $groupex_id = FALSE;
//        if ($mapping = \Drupal::entityManager()->getStorage('mapping')->load($mapping_id)) {
//          $field_groupex_id = $mapping->field_groupex_id->getValue();
//          $groupex_id = isset($field_groupex_id[0]['value']) ? $field_groupex_id[0]['value'] : FALSE;
//        }
//        if ($groupex_id) {
//          $values['location'] = $groupex_id;
//          $form_state->setValue('location', $groupex_id);
//          $form_state->setValue('location_select', $groupex_id);
//          $formatted_results = self::buildResults($form, $form_state);
//        }
//        else {
//          $this->logger->error('Failed to get location id.');
//        }
//      }
//    }

    if (isset($state['location']) && is_numeric($state['location'])) {
      $values['location'] = $state['location'];
      $form_state->setValue('location', $state['location']);
      $formatted_results = self::buildResults($form, $form_state);
    }
    if (isset($state['filter_date'])) {
      $values['date_select'] = $state['filter_date'];
    }
    if (!empty($user_input['class_select'])) {
      $state['class'] = $user_input['class_select'];
      $this->state = $state;
    }

    $form['#prefix'] = '<div id="groupex-full-form-wrapper">';
    $form['#suffix'] = '</div>';

    $class_select_classes = $location_select_classes = $category_select_classes = $classes = 'hidden';
    $location_classes = 'show';
    if (isset($groupex_id) && empty($state['class'])) {
      $classes = $class_select_classes = 'show';
    }
    if (isset($state['location']) && is_numeric($state['location'])) {
      $location_select_classes = $classes = $class_select_classes = $category_select_classes = 'show';
      $location_classes = 'hidden';
    }
    if (isset($site_section)) {
      $location_select_classes = 'hidden';
    }
    if (!empty($state['class']) && is_numeric($state['class'])) {
      $location_select_classes = $class_select_classes = $classes = 'show';
    }
    if (!empty($state['category']) && is_numeric($state['category'])) {
      $location_select_classes = $category_select_classes = 'show';
    }

    $locationValue = !empty($values['location']) ? $values['location'] : '';
    $form['location'] = [
      '#type' => 'radios',
      '#options' => $this->locationOptions,
      '#title' => $this->t('Locations'),
      '#default_value' => $locationValue,
      '#prefix' => '<div id="location-wrapper" class="' . $location_classes . '">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'groupex-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
      '#weight' => -4,
    ];

    $form['location_select'] = [
      '#type' => 'select',
      '#options' => $this->locationOptions,
      '#default_value' => !empty($state['location']) ? $state['location'] : reset($this->locationOptions),
      '#title' => $this->t('Locations:'),
      '#prefix' => '<div class="top-form-wrapper hidden"><div id="location-select-wrapper" class="' . $location_select_classes . '">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'groupex-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
      '#weight' => -3,
    ];

    $tz = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default'));
    $default_date = NULL;

    if (!empty($state['date_select'])) {
      $dt = new \DateTime($state['date_select'], $tz);
      $default_date = $dt->format('Y-m-d');
    }
    elseif (!empty($values['date_select'])) {
      $dt = new \DateTime($values['date_select'], $tz);
      $default_date = $dt->format('Y-m-d');
    }
    else {
      $dt = new \DateTime();
      $dt->setTimezone($tz);
      $dt->setTimestamp($request_time);
      $default_date = $dt->format('Y-m-d');
    }

    $form['date_select'] = [
      '#type' => 'date',
      '#title' => $this->t('Date'),
      '#prefix' => '<div id="date-select-wrapper" class="' . $classes . '">',
      '#suffix' => '</div>',
      '#default_value' => $default_date,
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'groupex-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
      '#weight' => -2,
    ];

    // Get category options
    $raw_category_data = $this->getOptions($this->request(['query' => ['categories' => TRUE]]), 'id', 'name');
    $processed_category_data['any'] = $this->t('-All-');
    foreach ($raw_category_data as $key => $category) {
      // Remove excess key text & cleanup markup being sent back.
      $id = str_replace('DESC--[', '', $key);
      $processed_category_data[$id] = t($category);
    }

    $form['category_select'] = [
      '#type' => 'select',
      '#options' => $processed_category_data,
      '#default_value' => !empty($state['category']) ? $state['category'] : 'any',
      '#title' => $this->t('Category:'),
      '#prefix' => '<div id="category-select-wrapper" class="' . $category_select_classes . '">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'groupex-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
        '#weight' => -1,
      ],
    ];

    // Get classes options.
    $raw_classes_data = $this->getOptions($this->request(['query' => ['classes' => TRUE]]), 'id', 'title');
    $processed_classes_data['any'] = $this->t('-All-');
    foreach ($raw_classes_data as $key => $class) {
      // Remove excess key text & cleanup markup being sent back.
      $id = str_replace('DESC--[', '', $key);
      $processed_classes_data[$id] = t($class);
    }

    $form['class_select'] = [
      '#type' => 'select',
      '#options' => $processed_classes_data,
      '#default_value' => !empty($state['class']) ? $state['class'] : 'any',
      '#title' => $this->t('Class:'),
      '#prefix' => '<div id="class-select-wrapper" class="' . $class_select_classes . '">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'groupex-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
        '#weight' => -1,
      ],
    ];

    // Get instructor options.
    $instructors_location = isset($values['location_select']) ? $values['location_select'] : $locationValue;
    $instructors_query = !empty($instructors_location) && $instructors_location != 'any' ? ['schedule' => TRUE, 'location' => $instructors_location] : ['schedule' => TRUE];
    $this->instructorOptions = ['any' => (string) $this->t('-All-')];
    $raw_schedule_data = $this->request(['query' => $instructors_query]);
    $instructors = $this->getOptions($raw_schedule_data, 'instructor', 'instructor');
    // Sorting array with instructors by name.
    ksort($instructors);
    // Cleanup markup being sent back.
    foreach($instructors as $key => $value) {
      // Here we need to remove redundant HTML if exists.
      $pos = strpos($key, '<span ');
      if (FALSE !== $pos) {
        $key = strip_tags(substr_replace($key, '', $pos));
      }

      $instructors_options[$key] = t(strip_tags($key));
    }
    $this->instructorOptions = $this->instructorOptions + $instructors_options;

    $form['instructor_select'] = [
      '#type' => 'select',
      '#options' => $this->instructorOptions,
      '#default_value' => !empty($state['instructor']) ? $state['instructor'] : 'any',
      '#title' => $this->t('Instructor:'),
      '#prefix' => '<div id="instructor-select-wrapper" class="' . $class_select_classes . '">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'groupex-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
        '#weight' => 0,
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#prefix' => '<div id="submit-wrapper" class="' . $classes . '">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
      ],
      '#attributes' => [
        'class' => ['btn'],
      ],
    ];

    $form['groupex_pdf_link'] = [
      '#prefix' => '<div class="groupex-pdf-link-container clearfix">',
      '#suffix' => '</div>',
    ];

    if (!empty($values['location'])) {
      $form['groupex_pdf_link'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'groupex-pdf-link-container',
            'clearfix',
          ],
        ],
        '#weight' => 1,
      ];

      $url = $this->groupexHelper->getPdfLink($values['location']);
      $form['groupex_pdf_link']['link'] = [
        '#title' => $this->t('Download PDF'),
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => [
          'class' => [
            'btn',
            'btn-default',
            'pdf-link',
          ],
        ],
        '#weight' => 1,
      ];
    }

    $filter_date_default = date('n/d/y', $request_time);
    $form['date'] = [
      '#type' => 'hidden',
      '#default_value' => $filter_date_default,
    ];

    $form['result'] = [
      '#prefix' => '</div><div class="groupex-results">',
      '#results' => $formatted_results,
      '#suffix' => '</div>',
      '#weight' => 10,
    ];

    $form['#attached']['library'][] = 'openy_group_schedules/openy_group_schedules';

    $form['#cache'] = [
      'max-age' => 0,
      'contexts' => [
        'url.query_args:location',
        'url.query_args:filter_date',
        'url.query_args:class',
        'url.query_args:filter_length',
        'url.query_args:groupex_class',
        'url.query_args:category',
      ],
    ];

    return $form;
  }

  /**
   * Custom ajax callback.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $state = $this->state;
    $location = !empty($values['location_select']) ? $values['location_select'] : $values['location'];
    $filter_date = !empty($values['date_select']) ? $values['date_select'] : $values['date'];
    $tz = \Drupal::config('system.date')->get('timezone.default');
    $date = new \DateTime($filter_date, new \DateTimeZone($tz));
    $filter_date = $date->format('n/d/y');

    $parameters = [
      'location' => $location,
      'filter_date' => $filter_date,
    ];
    $triggering_element = $form_state->getTriggeringElement();

    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'location') {
      $parameters['location'] = $triggering_element['#value'];
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'location_select' && $state['class'] != 'any') {
      $parameters['class'] = $state['class'];
      $parameters['filter_length'] = 'week';
      $parameters['category'] = $state['category'];
      $parameters['groupex_class'] = 'groupex_table_class_individual';
      $parameters['view_mode'] = 'class';
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'class_select' && $triggering_element['#value'] != 'any') {
      $parameters['class'] = $triggering_element['#value'];
      $parameters['filter_length'] = 'week';
      $parameters['category'] = $state['category'];
      $parameters['groupex_class'] = 'groupex_table_class_individual';
      $parameters['view_mode'] = 'class';
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'instructor_select') {
      $parameters['class'] = $state['class'];
      $parameters['filter_length'] = 'week';
      $parameters['category'] = $state['category'];
      $parameters['groupex_class'] = 'groupex_table_instructor_individual';
      if ($triggering_element['#value'] != 'any') {
        $parameters['instructor'] = $triggering_element['#value'];
      }
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'category_select' && $triggering_element['#value'] != 'any') {
      $parameters['class'] = $state['class'];
      $parameters['filter_length'] = 'week';
      $parameters['groupex_class'] = 'groupex_table_instructor_individual';
      if ($triggering_element['#value'] != 'any') {
        $parameters['category'] = $state['category'];;
      }
    }

    $formatted_results = self::buildResults($form, $form_state);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#groupex-full-form-wrapper .groupex-results', $formatted_results));
    $response->addCommand(new InvokeCommand(NULL, 'groupExLocationAjaxAction', [$parameters]));

    $link = [
      '#title' => $this->t('Download PDF'),
      '#type' => 'link',
      '#url' => $this->groupexHelper->getPdfLink($parameters['location']),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-default',
          'pdf-link',
        ],
      ],
    ];
    $groupex_pdf_link = $link;

    $response->addCommand(new HtmlCommand('#groupex-full-form-wrapper .groupex-pdf-link-container', $groupex_pdf_link));

    $form_state->setRebuild();
    return $response;
  }

  /**
   * Custom ajax callback.
   */
  public function buildResults(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();

    $values = $form_state->getValues();
    $query = $this->state;
    $filter_date = NULL;
    if (!isset($values['location']) && is_numeric($query['location'])) {
      $values['location_select'] = $values['location'] = $query['location'];
    }
    if (!isset($values['date']) && !empty($query['filter_date'])) {
      $values['date_select'] = $values['date'] = $query['filter_date'];
    }
    $location = !empty($values['location_select']) ? $values['location_select'] : $values['location'];
    if (isset($values['date'])) {
      $filter_date = !empty($values['date_select']) ? $values['date_select'] : $values['date'];
    }
    if (isset($user_input['date_select']) && $user_input['date_select'] != $filter_date) {
      $filter_date = $user_input['date_select'];
    }
    $class = !empty($values['class_select']) ? $values['class_select'] : 'any';
    if ($class == 'any' && empty($user_input['class_select']) && is_numeric($query['class'])) {
      $class = $query['class'];
    }
    $category = !empty($values['category_select']) ? $values['category_select'] : 'any';
    if ($category == 'any' && empty($user_input['category_select']) && is_numeric($query['category'])) {
      $category = $query['category_select'];
    }

    $filter_length = !empty($query['filter_length']) ? $query['filter_length'] : 'day';
    $groupex_class = !empty($query['groupex_class']) ? $query['groupex_class'] : 'groupex_table_class';
    $triggering_element = $form_state->getTriggeringElement();
    // Reset to day length in any case if date select has been changed.
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'date_select') {
      $filter_length = 'day';
    }
    // Reset to day length in any case if date select has been changed.
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'location_select' && is_numeric($class)) {
      $filter_length = 'week';
      $groupex_class = 'groupex_table_class_individual';
      $view_mode = 'class';
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'class_select' && $triggering_element['#value'] != 'any') {
      $class = $triggering_element['#value'];
      $filter_length = 'week';
      $groupex_class = 'groupex_table_class_individual';
      $view_mode = 'class';
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'category_select' && $triggering_element['#value'] != 'any') {
      $category = $triggering_element['#value'];
      $filter_length = 'week';
      $groupex_class = 'groupex_table_class_individual';
      $view_mode = 'class';
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'location_select' && $groupex_class = 'groupex_table_instructor_individual') {
      $location = $triggering_element['#value'];
      $class = 'any';
      $filter_length = 'day';
      $groupex_class = 'groupex_table_class';
    }
    $parameters = [
      'location' => $location,
      'class' => $class,
      'category' => $category,
      'filter_length' => $filter_length,
      'filter_date' => $filter_date,
      'groupex_class' => $groupex_class,
    ];
    // Add optional parameters.
    if (!empty($user_input['instructor_select'])) {
      $parameters['instructor'] = $user_input['instructor_select'];
    }
    elseif (!empty($query['instructor'])) {
      $parameters['instructor'] = $query['instructor'];
    }
    if (isset($view_mode)) {
      $parameters['view_mode'] = $view_mode;
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'location_select' && $groupex_class = 'groupex_table_instructor_individual') {
      unset($parameters['instructor']);
      unset($parameters['view_mode']);
    }

    // Remove instructor parameter in case of no instructor selected.
    if (isset($parameters['instructor']) && $parameters['instructor'] == 'any') {
      unset($parameters['instructor']);
    }

    $this->scheduleFetcher->__construct($this->groupexHelper, $this->configFactory, $parameters);

    // Get classes schedules.
    $schedule = $this->scheduleFetcher->getSchedule();
    // Are results empty?
    $formatted_results = $this->t('No results. Please try again.');
    if (!$empty_results = $this->scheduleFetcher->isEmpty()) {
      // Format results as table view.
      $formatted_results = openy_group_schedules_schedule_table_layout($schedule);
    }
    return $formatted_results;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
