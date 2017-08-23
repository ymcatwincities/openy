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
      '#default_value' => $default_arrival_date,
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'cdn-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
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
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'cdn-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['range'] = [
      '#type' => 'select',
      '#title' => '',
      '#default_value' => $state['range'],
      '#options' => [
        0 => '+/- 3 Days'
      ],
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'cdn-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
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

    $cdn_product_ids = \Drupal::entityQuery('cdn_prs_product')
      ->execute();
    $cdn_product_ids = array_slice($cdn_product_ids, 0, 3);
    $formatted_results = $this->t('No results. Please try again.');
    if ($cdn_products = \Drupal::entityManager()->getStorage('cdn_prs_product')->loadMultiple($cdn_product_ids)) {
      $formatted_results = ymca_camp_du_nord_results_layout($cdn_products);
    }
    return $formatted_results;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
