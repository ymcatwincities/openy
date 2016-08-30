<?php

namespace Drupal\ymca_groupex\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ymca_groupex\GroupexScheduleFetcher;

/**
 * Implements Groupex Full Form.
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
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The state of form.
   *
   * @var array
   */
  protected $state;

  /**
   * Groupex Schedule Fetcher.
   *
   * @var GroupexScheduleFetcher
   */
  protected $scheduleFetcher;

  /**
   * GroupexFormFull constructor.
   *
   * @param QueryFactory $entity_query
   *   The entity query factory.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The entity type manager.
   */
  public function __construct(QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, GroupexScheduleFetcher $schedule_fetcher) {
    $this->scheduleFetcher = $schedule_fetcher;

    $this->locationOptions = $this->getOptions($this->request(['query' => ['locations' => TRUE]]), 'id', 'name');
    $raw_classes_data = $this->getOptions($this->request(['query' => ['classes' => TRUE]]), 'id', 'title');
    $processed_classes_data['any'] = $this->t('-All-');
    foreach ($raw_classes_data as $key => $class) {
      $id = str_replace('DESC--[', '', $key);
      $processed_classes_data[$id] = $class;
    }
    $this->classesOptions = $processed_classes_data;
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('ymca_mindbody');

    $query = $this->getRequest()->query->all();
    $request = $this->getRequest()->request->all();
    $state = array(
      'location' => isset($query['location']) && is_numeric($query['location']) ? $query['location'] : NULL,
      'class' => isset($query['class']) ? $query['class'] : NULL,
      'category' => isset($query['category']) ? $query['category'] : NULL,
      'filter_length' => isset($query['filter_length']) ? $query['filter_length'] : NULL,
      'filter_date' => isset($query['filter_date']) ? $query['filter_date'] : NULL,
      'groupex_class' => isset($query['groupex_class']) ? $query['groupex_class'] : NULL,
      'filter_timestamp' => isset($query['filter_timestamp']) ? $query['filter_timestamp'] : NULL,
      'instructor' => isset($query['instructor']) ? $query['instructor'] : NULL,
      'view_mode' => isset($query['view_mode']) ? $query['view_mode'] : NULL,
    );
    // If not empty this means that form creates after ajax callback.
    if (!empty($request)) {
      $state['class'] = isset($request['class_select']) ? $request['class_select'] : NULL;
      $state['location'] = isset($request['location_select']) ? $request['location_select'] : NULL;
      $state['filter_date'] = isset($request['date_select']) ? $request['date_select'] : NULL;
    }
    // Reset any 'class' value if date has been changed.
    if (!empty($request) && $request['_triggering_element_name'] == 'date_select') {
      $state['class'] = NULL;
      $state['view_mode'] = NULL;
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
      $container->get('ymca_groupex.schedule_fetcher')
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $state = $this->state;
    $formatted_results = '';

    // Check if form printed on specific Location Schedules page.
    if ($this->getRouteMatch()->getRouteName() == 'ymca_frontend.location_schedules') {
      if ($site_section = \Drupal::service('pagecontext.service')->getContext()) {
        $mapping_id = \Drupal::entityQuery('mapping')
          ->condition('type', 'location')
          ->condition('field_location_ref', $site_section->id())
          ->execute();
        $mapping_id = reset($mapping_id);
        $groupex_id = FALSE;
        if ($mapping = \Drupal::entityManager()->getStorage('mapping')->load($mapping_id)) {
          $field_groupex_id = $mapping->field_groupex_id->getValue();
          $groupex_id = isset($field_groupex_id[0]['value']) ? $field_groupex_id[0]['value'] : FALSE;
        }
        if ($groupex_id) {
          $values['location'] = $groupex_id;
          $form_state->setValue('location', $groupex_id);
          $form_state->setValue('location_select', $groupex_id);
          $formatted_results = self::buildResults($form, $form_state);
        }
        else {
          $this->logger->error('Failed to get location id.');
        }
      }
    }

    if (isset($state['location']) && is_numeric($state['location'])) {
      $values['location'] = $state['location'];
      $formatted_results = self::buildResults($form, $form_state);
    }
    if (isset($state['filter_date'])) {
      $values['date_select'] = $state['filter_date'];
    }

    $form['#prefix'] = '<div id="groupex-full-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['groupex_pdf_link'] = [
      '#prefix' => '<div class="groupex-pdf-link-container clearfix">',
      '#suffix' => '</div>',
    ];
    if (!empty($values['location'])) {
      $url = $this->scheduleFetcher->getPdfLink($values['location']);
      $form['groupex_pdf_link']['link'] = [
        '#title' => $this->t('View This Week\'s PDF'),
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => [
          'class' => [
            'btn',
            'btn-default',
            'btn-xs',
            'pdf-link',
          ],
        ],
      ];
    }

    $class_select_classes = $location_select_classes = $classes = 'hidden';
    $location_classes = 'show';
    if (isset($groupex_id) && empty($state['class'])) {
      $classes = 'show';
    }
    if (isset($state['location']) && is_numeric($state['location'])) {
      $location_select_classes = $classes = 'show';
      $location_classes = 'hidden';
    }
    if (isset($site_section)) {
      $location_select_classes = 'hidden';
    }
    if (!empty($state['class']) && is_numeric($state['class'])) {
      $classes = 'hidden';
      $location_select_classes = $class_select_classes = 'show';
    }
    if (isset($state['instructor'])) {
      $classes = $class_select_classes = 'hidden';
      $location_select_classes = 'show';
    }

    $form['location_select'] = [
      '#type' => 'select',
      '#options' => $this->locationOptions,
      '#default_value' => !empty($state['location']) ? $state['location'] : reset($this->locationOptions),
      '#title' => $this->t('Locations'),
      '#prefix' => '<div id="location-select-wrapper" class="' . $location_select_classes . '">',
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
    ];

    $date_options = [];
    for ($i = 0; $i < 14; $i++) {
      $time = REQUEST_TIME + $i * 86400;
      $dateKey = date('n/d/y', $time);
      $dateTitle = date('D, m/d', $time);
      $date_options[$dateKey] = $dateTitle;
    }
    $form['date_select'] = [
      '#type' => 'select',
      '#options' => $date_options,
      '#title' => $this->t('Date'),
      '#prefix' => '<div id="date-select-wrapper" class="' . $classes . '">',
      '#suffix' => '</div>',
      '#default_value' => !empty($values['date_select']) ? $values['date_select'] : reset($date_options),
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
    ];

    $form['location'] = [
      '#type' => 'radios',
      '#options' => $this->locationOptions,
      '#title' => $this->t('Locations'),
      '#default_value' => !empty($values['location']) ? $values['location'] : '',
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
    ];

    $form['class_select'] = [
      '#type' => 'select',
      '#options' => $this->classesOptions,
      '#default_value' => !empty($state['class']) ? $state['class'] : 'all',
      '#title' => $this->t('Class'),
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
      ],
    ];

    $filter_date_default = date('n/d/y', REQUEST_TIME);
    $form['date'] = [
      '#type' => 'hidden',
      '#default_value' => $filter_date_default,
    ];

    $form['results'] = [
      '#prefix' => '<div class="groupex-results">',
      'results' => $formatted_results,
      '#suffix' => '</div>',
    ];

    $form['#attached']['library'][] = 'ymca_groupex/ymca_groupex';

    $form['#cache'] = [
      'max-age' => 0,
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
      $parameters['category'] = 'any';
      $parameters['groupex_class'] = 'groupex_table_class_individual';
      $parameters['view_mode'] = 'class';
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'class_select' && $triggering_element['#value'] != 'any') {
      $parameters['class'] = $triggering_element['#value'];
      $parameters['filter_length'] = 'week';
      $parameters['category'] = 'any';
      $parameters['groupex_class'] = 'groupex_table_class_individual';
      $parameters['view_mode'] = 'class';
    }
    $formatted_results = self::buildResults($form, $form_state);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#groupex-full-form-wrapper .groupex-results', $formatted_results));
    $response->addCommand(new InvokeCommand(NULL, 'groupExLocationAjaxAction', array($parameters)));

    $link = [
      '#title' => $this->t('View This Week\'s PDF'),
      '#type' => 'link',
      '#url' => $this->scheduleFetcher->getPdfLink($parameters['location']),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-default',
          'btn-xs',
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
    if (!isset($values['location']) && is_numeric($query['location'])) {
      $values['location_select'] = $values['location'] = $query['location'];
    }
    if (!isset($values['date']) && !empty($query['filter_date'])) {
      $values['date_select'] = $values['date'] = $query['filter_date'];
    }
    $location = !empty($values['location_select']) ? $values['location_select'] : $values['location'];
    $filter_date = !empty($values['date_select']) ? $values['date_select'] : $values['date'];
    if (isset($user_input['date_select']) && $user_input['date_select'] != $filter_date) {
      $filter_date == $user_input;
    }
    $class = !empty($values['class_select']) ? $values['class_select'] : 'any';
    if ($class == 'any' && is_numeric($query['class'])) {
      $class = $query['class'];
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
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'location_select' && $groupex_class = 'groupex_table_instructor_individual') {
      $location = $triggering_element['#value'];
      $class = 'any';
      $filter_length = 'day';
      $groupex_class = 'groupex_table_class';
    }
    $parameters = [
      'location' => $location,
      'class' => $class,
      'category' => 'any',
      'filter_length' => $filter_length,
      'filter_date' => $filter_date,
      'groupex_class' => $groupex_class,
    ];
    // Add optional parameters.
    if (!empty($query['instructor'])) {
      $parameters['instructor'] = $query['instructor'];
    }
    if (isset($view_mode)) {
      $parameters['view_mode'] = $view_mode;
    }
    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'location_select' && $groupex_class = 'groupex_table_instructor_individual') {
      unset($parameters['instructor']);
      unset($parameters['view_mode']);
    }
    \Drupal::service('ymca_groupex.schedule_fetcher')->__construct($parameters);
    // Get classes schedules.
    $schedule = \Drupal::service('ymca_groupex.schedule_fetcher')->getSchedule();
    // Are results empty?
    $formatted_results = $this->t('No results. Please try again.');
    if (!$empty_results = \Drupal::service('ymca_groupex.schedule_fetcher')->isEmpty()) {
      // Format results as table view.
      $formatted_results = ymca_groupex_schedule_table_layout($schedule);
    }
    return $formatted_results;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
