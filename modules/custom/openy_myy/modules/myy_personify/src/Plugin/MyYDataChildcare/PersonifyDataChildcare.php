<?php

namespace Drupal\myy_personify\Plugin\MyYDataChildcare;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\myy_personify\PersonifyUserHelper;
use Drupal\openy_myy\PluginManager\MyYDataChildcareInterface;
use Drupal\personify\PersonifyClient;
use Drupal\personify\PersonifySSO;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Personify Childcare data plugin.
 *
 * @MyYDataChildcare(
 *   id = "myy_personify_data_childcare",
 *   label = "MyY Data Profile: Visits",
 *   description = "Visits data from Personify",
 * )
 */
class PersonifyDataChildcare extends PluginBase implements MyYDataChildcareInterface, ContainerFactoryPluginInterface {

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
    $this->logger = $loggerChannelFactory->get('personify_data_childcare');
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
  public function getChildcareEvents($start_date, $end_date) {

    $personifyID = $this->personifyUserHelper->personifyGetId();

    $body = "<StoredProcedureRequest>
    <StoredProcedureName>OPENY_GET_MYY_CHILDCARE_SESSIONS</StoredProcedureName>
    <SPParameterList>
        <StoredProcedureParameter>
            <Name>@id</Name>
            <Value>$personifyID</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@startDate</Name>
            <Value>$start_date 00:00:00.000</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@endDate</Name>
            <Value>$end_date 00:00:00.000</Value>
        </StoredProcedureParameter>
    </SPParameterList>
    </StoredProcedureRequest>";

    $result = [];

    $data = $this->personifyClient->doAPIcall('POST', 'GetStoredProcedureDataJSON?$format=json', $body, 'xml');

    $results = json_decode($data['Data'], TRUE);
    if (!empty($results['Table'])) {
       foreach ($results['Table'] as $childcare_item) {
         $order_date = new \DateTime($childcare_item['order_date']);
         $result[] = [
           'order_number' => $childcare_item['order_number'],
           'usr_day' => $childcare_item['usr_day'],
           'od_order_date' => $childcare_item['od_order_date'],
           'order_date' => $childcare_item['order_date'],
           'scheduled' => $childcare_item['sflag'],
           'attended' => $childcare_item['aflag'],
           'type' => $childcare_item['stype'],
           'program_name' => $childcare_item['pname'],
           'program_code' => $childcare_item['pcode'],
           'product_id' => $childcare_item['pid'],
           'branch_id' => $childcare_item['branch_id'],
           'child_id' => $childcare_item['prtcpnt_id'],
           'date' => $order_date->format('Y-m-d')
         ];
       }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildcareScheduledEvents() {

    $start_date_string = '2019-08-01';
    //$start_date_string = date('Y-m-d');
    $start_date  = new \DateTime($start_date_string);

    $end_date = $start_date->modify('+6 months');

    $items = $this->getChildcareEvents($start_date_string, $end_date->format('Y-m-d'));

    $result = [];

    foreach ($items as $item) {
      if (($item['attended'] == 'N')) {
        $result[] = $item;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function cancelChildcareSessions($order_id, $date, $type) {

    $personifyID = $this->personifyUserHelper->personifyGetId();

    $body = '
    <StoredProcedureRequest>
    <StoredProcedureName>OPENY_GET_MYY_CHILDCARE_SESSIONS_UPDATE</StoredProcedureName>
    <SPParameterList>
        <StoredProcedureParameter>
            <Name>@ids</Name>
            <Value>' . $personifyID . '</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@ordDate</Name>
            <Value>' . $date . '</Value>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
            <Name>@stype</Name>
            <Value>' . $type . '</Value>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
            <Name>@state</Name>
            <Value>N</Value>
        </StoredProcedureParameter>
    </SPParameterList>
</StoredProcedureRequest>
    ';


    $data = $this->personifyClient->doAPIcall('POST', 'GetStoredProcedureDataJSON?$format=json', $body, 'xml');
    $results = json_decode($data['Data'], TRUE);
    if ($results['Table']['sflag'] == 'N') {
      return 'OK';
    } else {
      return 'FAIL';
    }

  }

  /**
   * {@inheritdoc}
   */
  public function addChildcareSessions($order_id, $data) {
    return [];
  }

}
