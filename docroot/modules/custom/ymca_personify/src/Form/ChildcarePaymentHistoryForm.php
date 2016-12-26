<?php

namespace Drupal\ymca_personify\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Url;

/**
 * Provides the Childcare Payment History Form.
 *
 * @ingroup ymca_personify
 */
class ChildcarePaymentHistoryForm extends FormBase {

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
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel.
   */
  public function __construct(ConfigFactory $config, LoggerChannelFactoryInterface $logger_factory) {
    $this->config = $config;
    $this->logger = $logger_factory->get('ymca_personify');
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
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Helper method retrieving child options.
   *
   * @return array
   *   Array of children to be used in form element.
   */
  public function getChildOptions() {
    $options = ['all' => $this->t('All')];
    // Set start date as 2014-01-01 to get children options.
    $parameters = [
      'start_date' => '2014-01-01',
      'end_date' => $this->state['end_date'],
    ];
    $data = \Drupal::service('ymca_personify_childcare_request')->personifyRequest($parameters);
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

      $results = $this->buildResults($values);
      $formatted_results = $this->formatResults($results);

      $form['#prefix'] = '<div id="childcare-payment-history-form-wrapper"><div class="container"><div class="row"><div class="col-xs-12">';
      $form['#suffix'] = '</div></div></div><div class="results clearfix">' . $formatted_results . '</div></div>';

      $form['start_date'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Payment Date Range From'),
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
        '#title' => $this->t('Payment Date Range To'),
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
    $data = \Drupal::service('ymca_personify_childcare_request')->personifyRequest($parameters);
    $content = [];
    $content['total'] = 0;
    if (isset($data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts'])) {
      foreach ($data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts'] as $receipt) {
        // Skip receipts with 0.00 Paid Amount.
        if ($receipt['ActualPostedPaidAmount'] == 0.00) {
          continue;
        }
        $name = str_replace(',', '', $receipt['ShipCustomerLastFirstName']);
        $key = $name . ', ' . $receipt['ShipMasterCustomerId'];
        $date = DrupalDateTime::createFromTimestamp(strtotime($receipt['ReceiptStatusDate']))->format('Y-m-d');
        $content['total'] += $receipt['ActualPostedPaidAmount'];
        $content['pdf_link'] = Url::fromRoute('ymca_personify.childcare_payment_history_pdf', [], [
          'query' => [
            'start_date' => $parameters['start_date'],
            'end_date' => $parameters['end_date'],
            'child' => 'all',
          ],
        ])->toString();
        $content['children'][$key]['name'] = $name;
        $content['children'][$key]['id'] = $receipt['ShipMasterCustomerId'];
        $content['children'][$key]['total'] += $receipt['ActualPostedPaidAmount'];
        $content['children'][$key]['total'] = number_format($content['children'][$key]['total'], 2, '.', '');
        $content['children'][$key]['receipts'][] = [
          'order' => $receipt['OrderAndLineNumber'],
          'description' => $receipt['Description'],
          'date' => $date,
          'amount' => number_format($receipt['ActualPostedPaidAmount'], 2, '.', ''),
        ];
      }
      $content['total'] = number_format($content['total'], 2, '.', '');
    }
    // Sort by date.
    if (!empty($content['children'])) {
      foreach ($content['children'] as $key => $child) {
        usort($child['receipts'], function ($a, $b) {
          return strtotime($a["date"]) - strtotime($b["date"]);
        });
        $content['children'][$key]['receipts'] = $child['receipts'];
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
    $results = $this->buildResults($parameters);
    $formatted_results = $this->formatResults($results);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#childcare-payment-history-form-wrapper .results', $formatted_results));
    if ($parameters['child'] !== 'all') {
      $response->addCommand(new CssCommand('#childcare-payment-history-form-wrapper .child', ['display' => 'none']));
      $response->addCommand(new CssCommand('#childcare-payment-history-form-wrapper .child-' . $parameters['child'], ['display' => 'table']));
    }
    $form_state->setRebuild();
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
