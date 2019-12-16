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
 * Personify Childcare data plugin.
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
  public function getOrders($date_start, $date_end, $type = 'A') {

    $personifyID = $this->personifyUserHelper->personifyGetId();

    // $type = A - All, $type= P - Pending.

    // Example: /myy-model/data/orders/2019-01-01/2019-09-01/A
    $filters = [
      'ShipMasterCustomerId eq \'' . $personifyID . '\'',
      'DueDate ge datetime\'' . $date_start .'\'',
      'DueDate le datetime\'' . $date_end . '\''
    ];

    $data = $this
      ->personifyClient
      ->doAPIcall('GET', 'WebOrderBalanceViews?$format=json&$filter=' . implode(' and ', $filters));

    $orders = [];

    foreach ($data['d'] as $item) {

      $orderData = $this
        ->personifyClient
        ->doAPIcall(
          'GET',
          'OrderMasterInfos(%27' . $item['OrderNumber'] . '%27)/OrderDetailInfo?$format=json'
        );

      $payed_amount = $orderData['d'][0]['PaidAmount'];

      if (!$payed_amount) {
        $payed_amount = '0.00';
      }

      //@TODO replace it with real one
      $pay_link = 'https://ygtc762tstebiz.personifycloud.com/personifyebusiness/Default.aspx?TabID=134&OrderNumber=' . $item['OrderNumber'] . '&RenewalMode=false';

      $orders[] = [
        'title' => $orderData['d'][0]['ProductDescription'],
        'description' => $orderData['d'][0]['CL_OrderDescription'],
        'total' => $orderData['d'][0]['BaseTotalAmount'],
        'payed' => ($payed_amount != $orderData['d'][0]['BaseTotalAmount']) ? 0 : 1,
        'pay_link' => ($payed_amount != $orderData['d'][0]['BaseTotalAmount']) ? $pay_link : '',
        'payed_amount' => $payed_amount
      ];
    }

    return $orders;
  }




}
