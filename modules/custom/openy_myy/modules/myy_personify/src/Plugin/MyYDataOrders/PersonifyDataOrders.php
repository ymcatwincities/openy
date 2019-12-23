<?php

namespace Drupal\myy_personify\Plugin\MyYDataOrders;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Driver\Exception\Exception;
use Drupal\myy_personify\PersonifyUserHelper;
use Drupal\openy_myy\PluginManager\MyYDataOrdersInterface;
use Drupal\personify\PersonifyClient;
use Drupal\personify\PersonifySSO;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Personify Orders data data plugin.
 *
 * @MyYDataOrders(
 *   id = "myy_personify_data_orders",
 *   label = "MyY Data Orders",
 *   description = "Get user orders data",
 * )
 */
class PersonifyDataOrders extends PluginBase implements MyYDataOrdersInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\personify\PersonifySSO;
   */
  protected $personifySSO;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\personify\PersonifyClient
   */
  protected $personifyClient;

  /**
   * @var \Drupal\myy_personify\PersonifyUserHelper
   */
  protected $personifyUserHelper;

  /**
   * PersonifyDataProfile constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\personify\PersonifySSO $personifySSO
   * @param \Drupal\personify\PersonifyClient $personifyClient
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PersonifySSO $personifySSO,
    PersonifyClient $personifyClient,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactory $loggerChannelFactory,
    PersonifyUserHelper $personifyUserHelper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->personifySSO = $personifySSO;
    $this->personifyClient = $personifyClient;
    $this->config = $configFactory->get('myy_personify.settings');
    $this->logger = $loggerChannelFactory->get('personify_data_order');
    $this->personifyUserHelper = $personifyUserHelper;
    $personify_config = $configFactory->get('personify.settings')->getRawData();
    $this->personify_domain = $personify_config[$personify_config['environment'] . '_endpoint'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('personify.sso_client'),
      $container->get('personify.client'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('myy_personify_user_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOrders($ids, $date_start, $date_end) {

    $personifyID = $this->personifyUserHelper->personifyGetId();

    $requested_ids = explode(',', $ids);

    $orders = [];

    $body = '
    <StoredProcedureRequest>
    <StoredProcedureName>OPENY_GET_ORDERS_BY_CUSTOMER_IDS</StoredProcedureName>
    <IsUserDefinedFunction>false</IsUserDefinedFunction>
    <SPParameterList>
        <StoredProcedureParameter>
            <Name>@ids</Name>
            <Value>' . $personifyID . '</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@dateStart</Name>
            <Value>' . $date_start . '</Value>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
            <Name>@dateEnd</Name>
            <Value>'. $date_end . '</Value>
        </StoredProcedureParameter>
    </SPParameterList>
    </StoredProcedureRequest>
    ';

    $data = $this->personifyClient->doAPIcall('POST', 'GetStoredProcedureDataJSON?$format=json', $body, 'xml');
    $results = json_decode($data['Data'], TRUE);

    $ddata = parse_url($this->personify_domain);
    $domain = $ddata['scheme'] . '://' . $ddata['host'];

    foreach ($results['Table'] as $item) {

      $pay_link = $domain . '/personifyebusiness/Default.aspx?TabID=134&OrderNumber=' . $item['OrderNumber'] . '&RenewalMode=false';
      $due_amount = $item['DUE_AMOUNT'];
      $orders[] = [
        'title' => 'REPLACE ME',
        'description' => $item['DESCRIPTION'],
        'total' => $item['BASE_TOTAL_AMOUNT'],
        'payed' => empty($due_amount) ? 1 : 0,
        'pay_link' => !empty($due_amount) ? $pay_link : '',
        'due_amount' => $due_amount,
        'due_date' => $item['DUE_DATE'],
      ];

    }

    return $orders;
  }

}
