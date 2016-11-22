<?php

namespace Drupal\ymca_personify\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\serialization\Encoder\XmlEncoder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;
use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\Serializer\Encoder;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Provides the Childcare Payment History Form.
 *
 * @ingroup ymca_personify
 */
class ChildcarePaymentHistoryForm extends FormBase {

  /**
   * Test childcare user ID.
   */
  const TEST_USER_ID = '1023827800';

  /**
   * Http client.
   *
   * @var Client
   */
  protected $client;

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $config;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * Is production flag.
   *
   * @var bool
   */
  protected $isProduction;

  /**
   * The state.
   *
   * @var array
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'childcare_payment_history_form';
  }

  /**
   * Creates a new ChildcarePaymentHistoryForm.
   *
   * @param Client $client
   *   Http client.
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel.
   */
  public function __construct(Client $client, ConfigFactory $config, LoggerChannelFactoryInterface $logger_factory) {
    $this->client = $client;
    $this->config = $config;
    $this->logger = $logger_factory->get('ymca_personify');
    $settings = $this->config->get('personify_mindbody_sync.settings');
    $this->isProduction = (bool) $settings->get('is_production');
    $query = parent::getRequest();
    $parameters = $query->query->all();
    // Set default start date as today -30 days.
    $start_date = date('m/d/Y', strtotime('-30 days'));
    // Set default end date as today.
    $end_date = new DrupalDateTime('now');
    $end_date = $end_date->format('m/d/Y');
    $state = [
      'start_date' => isset($parameters['start_date']) ? $parameters['start_date'] : $start_date,
      'end_date' => isset($parameters['end_date']) ? $parameters['end_date'] : $end_date,
    ];
    $this->logger = $logger_factory->get('ygs_schedules');
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function personifyRequest($parameters) {
    $data = [];
    $settings = $this->config->get('ymca_personify.settings');
    $start_date = !empty($parameters['start_date']) ? DrupalDateTime::createFromTimestamp(strtotime($parameters['start_date']))->format('Y-m-d') . 'T12:00:00' : '';
    $end_date = !empty($parameters['end_date']) ? DrupalDateTime::createFromTimestamp(strtotime($parameters['end_date']))->format('Y-m-d') . 'T12:00:00' : '';
    $client_id = isset($_COOKIE['Drupal_visitor_personify_id']) ? $_COOKIE['Drupal_visitor_personify_id'] : '';
    if (!$this->isProduction) {
      $client_id = self::TEST_USER_ID;
    }
    $options = [
      'body' => '<CL_ChildcarePaymentInfoInput>
        <BillMasterCustomerId>' . $client_id . '</BillMasterCustomerId>
        <ReceiptStartDate>' . $start_date . '</ReceiptStartDate>
        <ReceiptEndDate>' . $end_date . '</ReceiptEndDate>
        <BillSubCustomerId>0</BillSubCustomerId>
        <ProductClassCodes>CC,LC,PS,RD,SC,DC</ProductClassCodes>
        <DescriptionLike>NOT LIKE</DescriptionLike>
        <Descriptions>%Change%,%late%fee%,%late%pick%,%lunch%</Descriptions>
        <ProductCodeLike>NOT LIKE</ProductCodeLike>
        <ProductCodes>%_DC_9%%%</ProductCodes>
        </CL_ChildcarePaymentInfoInput>',
      'headers' => [
        'Authorization' => $settings->get('childcare_authorization'),
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $settings->get('customer_orders_username'),
        $settings->get('customer_orders_password'),
      ],
    ];

    try {
      $response = $this->client->request('POST', $settings->get('childcare_endpoint'), $options);
      if ($response->getStatusCode() == '200') {
        $body = $response->getBody();
        $xml = $body->getContents();
        $xml = preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $xml);
        $encoder = new XmlEncoder();
        $data = $encoder->decode($xml, 'xml');
      }
      else {
        $msg = 'Got %code response from Personify: %msg';
        $this->logger->error(
          $msg,
          [
            '%code' => $response->getStatusCode(),
            '%msg' => $response->getReasonPhrase(),
          ]
        );
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get Personify data: %msg', ['%msg' => $e->getMessage()]);
    }
    return $data;
  }

  /**
   * Helper method retrieving child options.
   *
   * @return array
   *   Array of children to be used in form element.
   */
  public function getChildOptions() {
    $options = ['all' => $this->t('All')];
    // Temporary set start date as 2014-01-01 to get children options.
    $parameters = [
      'start_date' => '2006-01-01',
      'end_date' => $this->state['end_date'],
    ];
    $data = self::personifyRequest($parameters);
    // Collect all children from available receipts.
    if (isset($data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts'])) {
      foreach ($data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts'] as $receipt) {
        $name = str_replace(',', '', $receipt['ShipCustomerLastFirstName']);
        $options[$receipt['ShipMasterCustomerId']] = $name;
      }
    }
    return $options;
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
      // Vary on the listed query args.
      $form['#cache'] = [
        'max-age' => 0,
        'contexts' => [
          'url.query_args:start_date',
          'url.query_args:end_date',
        ],
      ];

      $form['#attached']['library'] = [
        'ymca_personify/personify.childcare',
      ];

      $results = self::buildResults($values);
      $formatted_results = self::formatResults($results);

      $form['#prefix'] = '<div id="childcare-payment-history-form-wrapper"><div class="container"><div class="row"><div class="col-xs-12">';
      $form['#suffix'] = '</div></div></div><div class="results clearfix">' . $formatted_results . '</div></div>';

      $form['start_date'] = [
        '#type' => 'textfield',
        '#title' => $this->t('From'),
        '#default_value' => isset($values['start_date']) ? $values['start_date'] : '',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'childcare-payment-history-form-wrapper',
          'event' => 'keyup',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $form['end_date'] = [
        '#type' => 'textfield',
        '#title' => $this->t('To'),
        '#default_value' => isset($values['end_date']) ? $values['end_date'] : '',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'childcare-payment-history-form-wrapper',
          'event' => 'keyup',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $childOptions = $this->getChildOptions();
      $form['child'] = [
        '#type' => 'select',
        '#title' => $this->t('Child'),
        '#options' => $childOptions,
        '#default_value' => isset($values['child']) ? $values['child'] : 'all',
      ];

      $form['button'] = [
        '#type' => 'button',
        '#prefix' => '<div class="hidden">',
        '#suffix' => '</div>',
        '#value' => $this->t('Submit'),
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'childcare-payment-history-form-wrapper',
          'method' => 'replace',
          'event' => 'click',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];
    }
    catch (Exception $e) {
      $this->logger->error('Failed to build the form. Message: %msg', ['%msg' => $e->getMessage()]);
    }

    return $form;
  }

  /**
   * Build results.
   */
  public function buildResults($parameters) {
    $data = self::personifyRequest($parameters);
    $content = [];
    $content['total'] = 0;
    if (isset($data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts'])) {
      foreach ($data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts'] as $receipt) {
        $name = str_replace(',', '', $receipt['ShipCustomerLastFirstName']);
        $key = $name . ', ' . $receipt['ShipMasterCustomerId'];
        $date = DrupalDateTime::createFromTimestamp(strtotime($receipt['OrderDate']))->format('Y-m-d');
        $content['total'] += $receipt['ActualPostedPaidAmount'];
        $content['children'][$key]['name'] = $name;
        $content['children'][$key]['id'] = $receipt['ShipMasterCustomerId'];
        $content['children'][$key]['total'] += $receipt['ActualPostedPaidAmount'];
        $content['children'][$key]['receipts'][] = [
          'order' => $receipt['OrderAndLineNumber'],
          'description' => $receipt['Description'],
          'date' => $date,
          'amount' => $receipt['ActualPostedPaidAmount'],
        ];
      }
    }
    return $content;
  }

  /**
   * Format results.
   */
  public function formatResults($results) {
    $formatted_results = [
      '#theme' => 'ymca_childcare_payment_history',
      '#content' => $results,
    ];
    $formatted_results = render($formatted_results);
    return $formatted_results;
  }

  /**
   * Custom ajax callback.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    $parameters = $form_state->getUserInput();
    $results = self::buildResults($parameters);
    $formatted_results = self::formatResults($results);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#childcare-payment-history-form-wrapper .results', $formatted_results));
    $form_state->setRebuild();
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
