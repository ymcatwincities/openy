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
use Drupal\Core\Datetime;

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
    $this->ajaxOptions = $this->getAjaxOptions();

    $query = $this->getRequest()->query->all();
    $request = $this->getRequest()->request->all();

    $state = [
      'village' => isset($query['village']) && is_numeric($query['village']) ? $query['village'] : NULL,
      'arrival_date' => isset($query['arrival_date']) ? $query['arrival_date'] : NULL,
      'departure_date' => isset($query['departure_date']) ? $query['departure_date'] : NULL,
      'range' => isset($query['range']) ? $query['range'] : NULL,
    ];
    // If not empty this means that form creates after ajax callback.
    if (!empty($request)) {
      $state['arrival_date'] = isset($request['arrival_date']) ? $request['arrival_date'] : NULL;
      $state['departure_date'] = isset($request['departure_date']) ? $request['departure_date'] : NULL;
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
    $values = $form_state->getValues();
    $state = $this->state;
    $formatted_results = NULL;

    $formatted_results = self::buildResults($form, $form_state);

    $form['#prefix'] = '<div id="cdn-full-form-wrapper">';
    $form['#suffix'] = '</div>';

    $tz = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default'));

    $default_arrival_date = NULL;
    if (!empty($state['arrival_date'])) {
      $dt = new \DateTime($state['arrival_date'], $tz);
      $default_arrival_date = $dt->format('Y-m-d');
    }
    else {
      $dt = new \DateTime();
      $dt->setTimezone($tz);
      $dt->setTimestamp(REQUEST_TIME);
      $default_arrival_date = $dt->format('Y-m-d');
    }

    $form['village'] = [
      '#type' => 'hidden',
      '#default_value' => $state['village'],
    ];

    $form['arrival_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Arrival'),
      '#prefix' => '<div class="top-elements-wrapper">',
      '#default_value' => $default_arrival_date,
      '#ajax' => $this->ajaxOptions,
    ];

    $default_departure_date = NULL;
    if (!empty($state['departure_date'])) {
      $dt = new \DateTime($state['departure_date'], $tz);
      $default_departure_date = $dt->format('Y-m-d');
    }
    else {
      $dt = new \DateTime();
      $dt->setTimezone($tz);
      $dt->setTimestamp(REQUEST_TIME);
      $default_departure_date = $dt->format('Y-m-d');
    }

    $form['departure_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Departure'),
      '#default_value' => $default_departure_date,
      '#ajax' => $this->ajaxOptions,
    ];

    $form['range'] = [
      '#type' => 'select',
      '#suffix' => '</div>', // closes top-elements-wrapper.
      '#default_value' => $state['range'],
      '#options' => [
        0 => '+/- 3 Days'
      ],
      '#ajax' => $this->ajaxOptions,
    ];

    $form['village_select'] = [
      '#type' => 'select',
      '#title' => t('Village:'),
      '#default_value' => $state['village_select'],
      '#options' => $this->villageOptions,
      '#ajax' => $this->ajaxOptions,
    ];

    $form['capacity'] = [
      '#type' => 'select',
      '#title' => t('Capacity:'),
      '#default_value' => $state['capacity'],
      '#options' => $this->capacityOptions,
      '#ajax' => $this->ajaxOptions,
    ];

    $form['booked'] = [
      '#type' => 'checkbox',
      '#title' => t('Include booked'),
      '#default_value' => $state['booked'],
      '#ajax' => $this->ajaxOptions,
    ];

    $form['partly_available'] = [
      '#type' => 'checkbox',
      '#title' => t('Include partly available'),
      '#default_value' => $state['partly_available'],
      '#ajax' => $this->ajaxOptions,
    ];

    $form['results'] = [
      '#prefix' => '<div class="cdn-results">',
      'results' => $formatted_results,
      '#suffix' => '</div>',
      '#weight' => 10,
    ];

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
   * Custom ajax callback.
   */
  public function buildResults(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $values = $form_state->getValues();
    $query = $this->state;

    $cdn_product_ids = $this->entityQuery
      ->get('cdn_prs_product')
      ->condition('field_cdn_prd_start_date', '%' . $user_input['arrival_date'] . '%', 'LIKE')
      ->execute();
    $formatted_results = $this->t('No results. Please try again.');
    if ($cdn_products = $this->entityTypeManager->getStorage('cdn_prs_product')->loadMultiple($cdn_product_ids)) {
      if ($user_input['village_select'] !== 'all' || $user_input['capacity'] !== 'all') {
      foreach ($cdn_products as $key => $product) {
        $capacity = $product->field_cdn_prd_capacity->value;
        $cabin_id = $product->field_cdn_prd_cabin_id->value;
        // Filter by capacity.
        if ($user_input['capacity'] !== $capacity && $user_input['capacity'] !== 'all') {
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
            if ($page_id !== $user_input['village_select'] && $user_input['village_select'] !== 'all') {
              unset($cdn_products[$key]);
            }
          }
        }
      }
      }
      if (!empty($cdn_products)) {
        $formatted_results = ymca_camp_du_nord_results_layout($cdn_products);
      }
    }
    return $formatted_results;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

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
   * Provides default ajax build options.
   */
  public function getAjaxOptions() {
    return [
      'callback' => [$this, 'rebuildAjaxCallback'],
      'wrapper' => 'cdn-full-form-wrapper',
      'event' => 'change',
      'method' => 'replace',
      'effect' => 'fade',
      'progress' => [
        'type' => 'throbber',
      ],
    ];
  }

}
