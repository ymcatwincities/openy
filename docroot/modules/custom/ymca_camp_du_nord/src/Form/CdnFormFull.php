<?php

namespace Drupal\ymca_camp_du_nord\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Views;

/**
 * Implements Cdn Form Full.
 */
class CdnFormFull extends FormBase {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $ajaxOptions;

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
   * CdnFormFull constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The entity type manager.
   */
  public function __construct(QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('ymca_camp_du_nord');

    $this->villageOptions = $this->getVillageOptions();
    $this->capacityOptions = $this->getCapacityOptions();

    $query = $this->getRequest()->query->all();

    $tz = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default'));
    $default_arrival_date = NULL;
    if (!empty($query['arrival_date'])) {
      $dt = new \DateTime($query['arrival_date'], $tz);
      $default_arrival_date = $dt->format('Y-m-d');
    }
    else {
      $dt = new \DateTime();
      $dt->setTimezone($tz);
      $dt->setTimestamp(REQUEST_TIME+(86400*2));
      $default_arrival_date = $dt->format('Y-m-d');
    }
    $default_departure_date = NULL;
    if (!empty($query['departure_date'])) {
      $dt = new \DateTime($query['departure_date'], $tz);
      $default_departure_date = $dt->format('Y-m-d');
    }
    else {
      $dt = new \DateTime();
      $dt->setTimezone($tz);
      $dt->setTimestamp(REQUEST_TIME);
      $default_departure_date = $dt->format('Y-m-d');
    }
    $state = [
      'village' => isset($query['village']) && is_numeric($query['village']) ? $query['village'] : 'all',
      'arrival_date' => isset($query['arrival_date']) ? $query['arrival_date'] : $default_arrival_date,
      'departure_date' => isset($query['departure_date']) ? $query['departure_date'] : $default_departure_date,
      'range' => isset($query['range']) ? $query['range'] : NULL,
      'capacity' => isset($query['capacity']) ? $query['capacity'] : 'all',
      'partly_available' => isset($query['partly_available']) ? $query['partly_available'] : NULL,
      'booked' => isset($query['booked']) ? $query['booked'] : NULL,
    ];

    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cdn_form_full';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $locations = []) {
    $state = $this->state;
    $formatted_results = NULL;

    $formatted_results = self::buildResults($form, $form_state);

    $form['#prefix'] = '<div id="cdn-full-form-wrapper">';
    $form['#suffix'] = '</div>';


    $form['arrival_date'] = [
      '#type' => 'date',
      '#prefix' => '<div class="top-elements-wrapper"><div class="container"><h2>' . $this->t('Search') . '</h2>',
      '#default_value' => $state['arrival_date'],
    ];

    $form['departure_date'] = [
      '#type' => 'date',
      '#default_value' => $state['departure_date'],
    ];

    $form['range'] = [
      '#type' => 'select',
      '#default_value' => $state['range'],
      '#options' => [
        0 => '+/- 3 Days'
      ],
    ];

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#suffix' => '</div></div>', // closes top-elements-wrapper.
      '#button_type' => 'primary',
    );

    $form['village'] = [
      '#type' => 'select',
      '#prefix' => '<div class="bottom-elements-wrapper"><div class="container">',
      '#title' => t('By village'),
      '#default_value' => $state['village'],
      '#options' => $this->villageOptions,
    ];

    $form['capacity'] = [
      '#type' => 'select',
      '#title' => t('Capacity'),
      '#default_value' => $state['capacity'],
      '#options' => $this->capacityOptions,
    ];

    $form['partly_available'] = [
      '#type' => 'checkbox',
      '#title' => t('Include partly available'),
      '#default_value' => $state['partly_available'],
    ];

    $form['booked'] = [
      '#type' => 'checkbox',
      '#suffix' => '</div></div>', // closes bottom-elements-wrapper.
      '#title' => t('Include booked'),
      '#default_value' => $state['booked'],
    ];

    $form['results'] = [
      '#prefix' => '<div class="cdn-results">',
      '#markup' => render($formatted_results),
      '#suffix' => '</div>',
      '#weight' => 10,
    ];

    $form['#attached']['library'][] = 'ymca_camp_du_nord/cdn';

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

    $formatted_results = self::buildResults($form, $form_state);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#cdn-full-form-wrapper .cdn-results', $formatted_results));

    $form_state->setRebuild();
    return $response;
  }

  /**
   * Build results.
   */
  public function buildResults(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $values = $form_state->getValues();
    $query = $this->state;

    $cdn_product_ids = $this->entityQuery
      ->get('cdn_prs_product')
      ->condition('field_cdn_prd_start_date', '%' . $query['arrival_date'] . '%', 'LIKE')
      ->execute();
    $formatted_results = $this->t('No results. Please try again.');
    if ($cdn_products = $this->entityTypeManager->getStorage('cdn_prs_product')->loadMultiple($cdn_product_ids)) {
      if ($query['village'] !== 'all' || $query['capacity'] !== 'all') {
      foreach ($cdn_products as $key => $product) {
        $capacity = $product->field_cdn_prd_capacity->value;
        $cabin_id = $product->field_cdn_prd_cabin_id->value;
        // Filter by capacity.
        if ($query['capacity'] !== $capacity && $query['capacity'] !== 'all') {
          unset($cdn_products[$key]);
        }
        // Filter by village.
        if (!empty($cabin_id)) {
          $mapping_id = $this->entityQuery
            ->get('mapping')
            ->condition('type', 'cdn_prs_product')
            ->condition('field_cdn_prd_cabin_id', $cabin_id)
            ->execute();
          if ($mapping = $this->entityTypeManager->getStorage('mapping')->loadMultiple($mapping_id)) {
            $ref = $mapping->field_cdn_prd_village_ref->getValue();
            $page_id = isset($ref[0]['target_id']) ? $ref[0]['target_id'] : FALSE;
            // Filter by village.
            if ($page_id !== $query['village'] && $query['village'] !== 'all') {
              unset($cdn_products[$key]);
            }
          }
        }
      }
      }
      if (!empty($cdn_products)) {
        $formatted_results = $this->buildResultsLayout($cdn_products, $query);
      }
    }
    return $formatted_results;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $parameters = [];
    unset($values['submit']);
    unset($values['form_build_id']);
    unset($values['form_token']);
    unset($values['op']);
    unset($values['form_id']);
    $route = \Drupal::routeMatch()->getRouteName();
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($route == 'entity.node.canonical') {
      $parameters = [
        'node' => $node->id(),
      ];
    }
    $form_state->setRedirect(
      $route,
      $parameters,
      ['query' => $values]
    );
  }

  /**
   * Return Village options.
   */
  public function getVillageOptions() {
    $options = ['all' => t('Show All')];
    $mapping_ids = $this->entityQuery
      ->get('mapping')
      ->condition('type', 'cdn_prs_product')
      ->execute();
    if ($mappings = $this->entityTypeManager->getStorage('mapping')->loadMultiple($mapping_ids)) {
      foreach ($mappings as $mapping) {
        $ref = $mapping->field_cdn_prd_village_ref->getValue();
        $page_id = isset($ref[0]['target_id']) ? $ref[0]['target_id'] : FALSE;
        if ($page_node = $this->entityTypeManager->getStorage('node')->load($page_id)) {
          $options[$page_id] = $page_node->getTitle();
        }
      }
    }
    return $options;
  }

  /**
   * Return Capacity options.
   */
  public function getCapacityOptions() {
    $options = ['all' => t('Show All')];
    $cdn_products_ids = $this->entityQuery
      ->get('cdn_prs_product')
      ->execute();
    if ($cdn_products = $this->entityTypeManager->getStorage('cdn_prs_product')->loadMultiple($cdn_products_ids)) {
      foreach ($cdn_products as $cdn_product) {
        $value = $cdn_product->field_cdn_prd_capacity->getValue();
        if (!empty($value[0]['value'])) {
          $options[$value[0]['value']] = $value[0]['value'] . ' ' . t('people');
        }
      }
    }
    return $options;
  }

  /**
   * Helper method to make results layout.
   *
   * @param array $cdn_products
   *   Fetched products.
   *
   * @return array
   *   Results render array.
   */
  function buildResultsLayout(array $cdn_products, $query) {
    $attached = $results = $teasers = [];
    $cache = [
      'max-age' => 3600,
      'contexts' => ['url.query_args'],
    ];
    $default_availability = t('Available');
    if (!empty($cdn_products)) {
      foreach ($cdn_products as $product) {
        $code = $product->field_cdn_prd_code->value;
        $code = substr($code, 0, 14);
        $period = new \DatePeriod(
          new \DateTime($query['arrival_date']),
          new \DateInterval('P1D'),
          new \DateTime($query['departure_date'])
        );
        $codes = [];
        $attached['drupalSettings']['cdn']['selected_dates'] = [];
        foreach ($period as $date) {
          $codes[] = $code . $date->format('mdy') . '_YHL';
          $attached['drupalSettings']['cdn']['selected_dates'][] = $date->format('Y-m-d');
        }
        $codes[] = $code . $date->modify('+ 1 day')->format('mdy') . '_YHL';
        $attached['drupalSettings']['cdn']['selected_dates'][] = $date->format('Y-m-d');
        // Load calendar view with all dates for a product.
        $args = [implode(',', $codes)];
        $view = Views::getView('cdn_calendar');
        if (is_object($view)) {
          $view->setArguments($args);
          $view->setDisplay('embed_1');
          $view->preExecute();
          $view->execute();
        }
        $calendar_list = $this->buildListCalendar($view);
        $calendar = $view->buildRenderable('embed_1', $args);
        $capacity = $product->field_cdn_prd_capacity->getValue();
        $image = $this->getCabinImage($product->getName());
        $teasers[] = [
          'teaser' => [
            '#theme' => 'cdn_village_teaser',
            '#title' => !empty($product->getName()) ? substr($product->getName(), 9) : '',
            '#image' => $image,
            '#availability' => $default_availability,
            '#capacity' => !empty($capacity) ? $capacity[0]['value']: '',
            '#cache' => $cache,
          ],
          'calendar' => [
            'list' => $calendar_list,
            'calendar' => $calendar
          ],
        ];
      }
      $results = [
        '#theme' => 'cdn_results_wrapper',
        '#teasers' => $teasers,
        '#cache' => $cache,
        '#attached' => $attached,
      ];
    }

    return $results;
  }

  /**
   * Helper method to create mobile view calendar.
   *
   * @param array $view
   *   Fetched view with products.
   *
   * @return array
   *   Results render array.
   */
  function buildListCalendar($view) {
    $builds = [];
    foreach ($view->result as $row) {
      $entity = $row->_entity;
      $date = $entity->field_cdn_prd_start_date->value;
      $date = substr($date, 0, 10);
      $date1 = DrupalDateTime::createFromFormat('Y-m-d', $date)->format('F');
      $date2 = DrupalDateTime::createFromFormat('Y-m-d', $date)->format('d');
      $date3 = DrupalDateTime::createFromFormat('Y-m-d', $date)->format('D');
      $builds[] = [
        '#theme' => 'cdn_results_calendar',
        '#data' => [
          'date1' => $date1,
          'date2' => $date2,
          'date3' => $date3,
          'booked' => FALSE, // To Do: add real flag when API is ready.
          'selected' => FALSE,
          'price' => '$380', // To Do: add real price when API is ready.
        ],
      ];
    }
    return $builds;
  }

  /**
   * Helper method to get cabin image.
   *
   * @param string $name
   *   Name of the product.
   *
   * @return string
   *   Path to image.
   */
  function getCabinImage($name) {
    $path = '';
    $name = str_replace( ' cabin', '', strtolower(substr($name, 9)));
    $fids = \Drupal::service('entity.query')
      ->get('file')
      ->condition('filename', '%' . $name . '%', 'LIKE')
      ->execute();
    if ($files = \Drupal::service('entity_type.manager')->getStorage('file')->loadMultiple($fids)) {
      foreach ($files as $file) {
        if (preg_match('/cabin/', $file->getFilename())) {
          $path = file_create_url($file->getFileUri());
          return $path;
        }
      }
    }
    return $path;
  }

}
