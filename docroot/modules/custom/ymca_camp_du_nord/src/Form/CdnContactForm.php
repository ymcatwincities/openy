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
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;

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
      'name' => isset($query['name']) && is_string($query['name']) ? $query['name'] : '',
      'relationship' => isset($query['relationship']) && is_string($query['relationship']) ? $query['relationship'] : '',
      'phone' => isset($query['phone']) && is_string($query['phone']) ? $query['phone'] : 'cell',
      'phone_number' => isset($query['phone_number']) && is_string($query['phone_number']) ? $query['phone_number'] : '',
      'total' => isset($query['total']) ? $query['total'] : '',
      'nights' => isset($query['nights']) ? $query['nights'] : '',
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
  public function buildForm(array $form, FormStateInterface $form_state, $data = []) {
    $state = $this->state;

    $form['#prefix'] = '<div id="cdn-contact-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form += $this->buildAdditionalQuestionsFields($data, $state);
    $contact = $this->getEmergencyData($data, $state);

    $form['emergency_contact_exist'] = [
      '#type' => 'hidden',
      '#default_value' => empty($contact) ? 0 : 1,
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
      '#attributes' => [
        'placeholder' => $this->t('Name'),
      ],
      '#default_value' => $contact['name'],
    ];

    $form['emergency_contact']['relationship'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Relationship'),
      ],
      '#default_value' => $contact['relationship'],
    ];

    $form['emergency_contact']['phone'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#disabled' => TRUE,
      '#options' => ['cell' => $this->t('Cell')],
      '#default_value' => $state['phone'],
    ];

    $form['emergency_contact']['phone_number'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $contact['phone_number'],
      '#suffix' => '</div>',
      '#attributes' => [
        'placeholder' => $this->t('Phone'),
      ],
    ];

    $form['actions']['#prefix'] = '<div class="cdn-village-footer"><div class="container"><div class="cdn-village-footer-bar active"><div class="total">Total for <span class="nights">' . $state['nights'] . '</span> nights: <span class="price">$' . $state['total'] . '</span>';
    $form['actions']['#suffix'] = '</div></div></div></div>';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Next step ->'),
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
    $values = $form_state->getUserInput();
    $parameters = [];
    $values['answers'] = 1;
    unset($values['submit']);
    unset($values['form_build_id']);
    unset($values['form_token']);
    unset($values['op']);
    unset($values['form_id']);
    $route = \Drupal::routeMatch()->getRouteName();
    $form_state->setRedirect(
      $route,
      $parameters,
      ['query' => $values]
    );
    $query = \Drupal::service('request_stack')->getCurrentRequest()->query->all();
    $service = \Drupal::service('ymca_cdn_sync.add_to_cart');
    if (!$values['emergency_contact_exist']) {
      $record['name'] = $values['name'];
      $record['relationship'] = $values['relationship'];
      $record['phone_number'] = $values['phone_number'];
      $service->emergencyContactsAddRecord($_COOKIE['Drupal_visitor_personify_id'], $record);
    }
    $data = $service->askAdditionalQuestions(explode(',', $values['cart_items_ids']));
    $service->updateCartInfo($values, $data, $query['cdn_personify_chosen_ids']);
  }

  /**
   * Helper method to build dynamic fields.
   *
   * @param array $data
   *   Fetched view with products.
   * @param array $state
   *   State of form.
   *
   * @return array
   *   Results render array.
   */
  public function buildAdditionalQuestionsFields($data, $state) {
    $form['additional'] = [
      '#type' => 'container',
      '#prefix' => '<div class="container">',
    ];
    $form['additional']['head'] = [
      '#markup' => '<h2>' . $this->t('Additional Information') . '</h2>',
    ];
    $form['cart_items_ids'] = [
      '#type' => 'hidden',
      '#default_value' => implode(',', $data['cart_items_ids']),
    ];
    $cabin_q = FALSE;
    foreach ($data['data']['additional']['Data']['NewDataSet']['Table2'] as $q) {
      if (!$cabin_q && strpos($q['QUESTION_TEXT'], 'cabin')) {
        $form['additional']['title'] = [
          '#markup' => '<h3>' . $this->t('Do you need any of the following in your cabin?') . '</h3>',
        ];
        $cabin_q = TRUE;
      }
      switch ($q['ANSWER_TYPE_CODE']) {
        case 'NUMBER_TEXT_BOX':
          $form['additional'][$q['CODE']] = [
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => $q['QUESTION_TEXT'],
            '#default_value' => isset($state[$q['CODE']]) ? $state[$q['CODE']] : '',
          ];
          break;

        case 'DROP_DOWN':
          $form['additional'][$q['CODE']] = [
            '#type' => 'radios',
            '#required' => TRUE,
            '#title' => $q['QUESTION_TEXT'],
            '#options' => ['Y' => $this->t('Yes'), 'N' => $this->t('No')],
            '#default_value' => isset($state[$q['CODE']]) ? $state[$q['CODE']] : 'N',
          ];
          break;
      }
    }
    return $form;
  }

  /**
   * Helper method to get emergency field data.
   *
   * @param array $data
   *   Fetched view with products.
   * @param array $state
   *   State of form.
   *
   * @return array
   *   Results render array.
   */
  public function getEmergencyData($data, $state) {
    $contact = [];
    if (!empty($data['data']['emergency'])) {
      // @To do: decide if build all the records.
      $data = reset($data['data']['emergency']);
      $contact['name'] = $data['EmergencyContactName'];
      $contact['relationship'] = $data['EmergencyContactRelationship'];
      $contact['phone_number'] = $data['PhoneNumber'];
    }
    return $contact;
  }

}
