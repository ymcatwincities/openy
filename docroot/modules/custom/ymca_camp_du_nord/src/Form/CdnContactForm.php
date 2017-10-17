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
 * Implements Cdn Contact Form.
 */
class CdnContactForm extends FormBase {

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

    $query = $this->getRequest()->query->all();

    $state = [
      'first_time' => isset($query['first_time']) && is_bool($query['first_time']) ? $query['first_time'] : 0,
      'extra_mattress' => isset($query['extra_mattress']) && is_bool($query['extra_mattress']) ? $query['extra_mattress'] : 0,
      'pack_n_pay' => isset($query['pack_n_pay']) && is_bool($query['pack_n_pay']) ? $query['pack_n_pay'] : 0,
      'high_chair' => isset($query['high_chair']) && is_bool($query['high_chair']) ? $query['high_chair'] : 0,
      'booster_seat' => isset($query['booster_seat']) && is_bool($query['booster_seat']) ? $query['booster_seat'] : 0,
      'name' => isset($query['name']) && is_string($query['name']) ? $query['name'] : '',
      'relationship' => isset($query['relationship']) && is_string($query['relationship']) ? $query['relationship'] : '',
      'phone' => isset($query['phone']) && is_string($query['phone']) ? $query['phone'] : 'cell',
      'phone_number' => isset($query['phone_number']) && is_string($query['phone_number']) ? $query['phone_number'] : '',
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
    return 'cdn_contact_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $locations = []) {
    $state = $this->state;

    $form['#prefix'] = '<div id="cdn-contact-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['additional'] = [
      '#type' => 'container',
      '#prefix' => '<div class="container">',
    ];

    $form['additional']['first_time'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#prefix' => '<h2>' . $this->t('Additional information') . '</h2>',
      '#title' => $this->t('Is it your first time at a camp?'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#default_value' => $state['first_time'],
    ];

    $form['additional']['title'] = [
      '#markup' => '<h3>' . $this->t('Do you need any of the following in your cabin?') . '</h3>',
    ];

    $form['additional']['extra_mattress'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Extra Matress'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#default_value' => $state['extra_mattress'],
    ];

    $form['additional']['pack_n_pay'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Pack-n-pay'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#default_value' => $state['pack_n_pay'],
    ];

    $form['additional']['high_chair'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('High Chair'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#default_value' => $state['high_chair'],
    ];

    $form['additional']['booster_seat'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Booster Seat'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#default_value' => $state['booster_seat'],
    ];

    $form['emergency_contact'] = [
      '#type' => 'container',
    ];

    $form['emergency_contact']['help'] = [
      '#markup' => '<h2>' . $this->t('Emergency contact') . '</h2><h4>' . $this->t('Enter at least one emergency contact who will not be attending camp during this time') . '</h4>',
    ];

    $form['emergency_contact']['name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Name'),
      '#default_value' => $state['name'],
    ];

    $form['emergency_contact']['relationship'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Relationship'),
      '#default_value' => $state['relationship'],
    ];

    $form['emergency_contact']['phone'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#disabled' => TRUE,
      '#title' => $this->t('Phone'),
      '#options' => ['cell' => $this->t('Cell')],
      '#default_value' => $state['phone'],
    ];

    $form['emergency_contact']['phone_number'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $state['phone_number'],
    ];

    $form['actions']['#prefix'] = '</div><div class="actions"><div class="container">';
    $form['actions']['#suffix'] = '</div></div>';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Confirm reservation ->'),
      '#button_type' => 'primary',
    );

    $form['#cache'] = [
      'max-age' => 0,
    ];

    return $form;
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

}
