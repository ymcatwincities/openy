<?php

namespace Drupal\myy_personify\Plugin\MyYDataChildcare;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
    LoggerChannelFactory $loggerChannelFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->personifySSO = $personifySSO;
    $this->personifyClient = $personifyClient;
    $this->config = $configFactory->get('myy_personify.settings');
    $this->logger = $loggerChannelFactory->get('personify_data_childcare');
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
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getChildcareEvents($start_date, $end_date) {

    $personifyID = $this->personifySSO->getCustomerIdentifier($_COOKIE['Drupal_visitor_personify_authorized']);

    //@TODO Change to real data parameters
    $body = "<StoredProcedureRequest>
    <StoredProcedureName>OPENY_GET_MYY_CHILDCARE_SESSIONS</StoredProcedureName>
    <SPParameterList>
        <StoredProcedureParameter>
            <Name>@id</Name>
            <Value>$personifyID</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@startDate</Name>
            <Value>2019-08-26 00:00:00.000</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@endDate</Name>
            <Value>2019-08-30 00:00:00.000</Value>
        </StoredProcedureParameter>
    </SPParameterList>
    </StoredProcedureRequest>";

    $result = [];

    $data = $this->personifyClient->doAPIcall('POST', 'GetStoredProcedureDataJSON?$format=json', $body, 'xml');
    $results = json_decode($data->Data);
    if (!empty($results->Table)) {
       foreach ($results->Table as $childcare_item) {
         $result[] = [
           'order_number' => $childcare_item->order_number,
           'usr_day' => $childcare_item->usr_day,
           'od_order_date' => $childcare_item->od_order_date,
           'order_date' => $childcare_item->order_date,
           'scheduled' => $childcare_item->sflag,
           'attended' => $childcare_item->aflag,
           'type' => $childcare_item->stype,
           'program_name' => $childcare_item->pname,
           'program_code' => $childcare_item->pcode,
           'product_id' => $childcare_item->pid,
           'branch_id' => $childcare_item->branch_id
         ];
       }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildcareScheduledEvents($start_date, $end_date) {

    $items = $this->getChildcareEvents($start_date, $end_date);

    $result = [];

    foreach ($items as $item) {
      if (($item['attended'] == 'N') && ($item['scheduled'] == 'Y')) {
        $result[] = $item;
      }
    }

    return $result;
  }


}
