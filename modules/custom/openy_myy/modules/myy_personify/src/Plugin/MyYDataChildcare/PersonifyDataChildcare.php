<?php

namespace Drupal\myy_personify\Plugin\MyYDataChildcare;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\myy_personify\PersonifyUserData;
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
   * @var \Drupal\myy_personify\PersonifyUserData
   */
  protected $personifyUserData;

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
    PersonifyUserHelper $personifyUserHelper,
    PersonifyUserData $personifyUserData
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->personifySSO = $personifySSO;
    $this->personifyClient = $personifyClient;
    $this->config = $configFactory->get('myy_personify.settings');
    $this->logger = $loggerChannelFactory->get('personify_data_childcare');
    $this->personifyUserHelper = $personifyUserHelper;
    $this->personifyUserData = $personifyUserData;
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
      $container->get('myy_personify_user_helper'),
      $container->get('myy_personify_user_data')
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
            <Value>$start_date</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@endDate</Name>
            <Value>$end_date</Value>
        </StoredProcedureParameter>
    </SPParameterList>
    </StoredProcedureRequest>";

    $result = [];

    $data = $this->personifyClient->doAPIcall('POST', 'GetStoredProcedureDataJSON?$format=json', $body, 'xml');

    $results = json_decode($data['Data'], TRUE);

    $family = $this->personifyUserData->getFamilyData();
    foreach ($family['household'] as $fmember) {
      if (!empty($fmember['RelatedMasterCustomerId'])) {
        $fullname = explode(', ', $fmember['name']);
        $familyMap[$fmember['RelatedMasterCustomerId']] = [
          'name' => $fullname[0] . ', ' . $fullname[1],
          'short_name' => $fullname[1][0] . ' ' . $fullname[0][0],
        ];
      }
    }

    if (!empty($results['Table'])) {
       foreach ($results['Table'] as $id => $childcare_item) {

         $order_date = new \DateTime($childcare_item['order_date']);
         $week_number = $order_date->format('W');

         $dfrom = new \DateTime();
         $dfrom->setISODate($order_date->format('Y'), $week_number);
         $week_start_date = $dfrom->format('F d');

         $dto = new \DateTime();
         $dto->setISODate($order_date->format('Y'), $week_number)->modify('+6 days');
         $week_end_date = $dto->format('F d');
         $week = $week_start_date . ' - ' . $week_end_date;

         // Adding only not attended items.
         if ($childcare_item['aflag'] == 'N') {
           if (empty($result[$childcare_item['prtcpnt_id']][$childcare_item['pcode']]['data'])) {
             $result[$childcare_item['prtcpnt_id']][$childcare_item['pcode']]['program_data'] = [
               'family_member' => isset($familyMap[$childcare_item['prtcpnt_id']]['short_name']) ? $familyMap[$childcare_item['prtcpnt_id']]['short_name'] : '',
               'family_member_name' => isset($familyMap[$childcare_item['prtcpnt_id']]['name']) ? $familyMap[$childcare_item['prtcpnt_id']]['name'] : '',
               'program_name' => $childcare_item['pname'],
               'program_code' => $childcare_item['pcode'],
               'product_id' => $childcare_item['pid'],
               'branch' => $this->personifyUserHelper->locationMapping($childcare_item['branch_id']),
               'start_date' => date("M d", strtotime($start_date)),
               'end_date' => date("M d", strtotime($end_date)),
             ];
           }

           $result[$childcare_item['prtcpnt_id']][$childcare_item['pcode']]['weeks'][$week][] = [
             'order_number' => $childcare_item['order_number'],
             'usr_day' => $childcare_item['usr_day'],
             'od_order_date' => $childcare_item['od_order_date'],
             'order_date' => $childcare_item['order_date'],
             'scheduled' => $childcare_item['sflag'],
             'attended' => $childcare_item['aflag'],
             'type' => $childcare_item['stype'],
             'date' => $order_date->format('Y-m-d')
           ];
         }

       }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildcareScheduledEvents() {

    //$start_date_string = '2019-12-01';
    $start_date_string = date('Y-m-d');
    $start_date  = new \DateTime($start_date_string);

    $end_date = $start_date->modify('+3 month');

    $items = $this->getChildcareEvents($start_date_string, $end_date->format('Y-m-d'));


    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function cancelChildcareSessions($date, $type) {

    $result = $this->updateChildCareData([
      'date' => $date,
      'type' => $type,
      'value' => 'N'
    ]);

    if ($result['sflag'] == 'N') {
      return [
        'status' => 'ok',
      ];
    } else {
      return [
        'status' => 'fail'
      ];
    }

  }

  /**
   * @param $data
   */
  private function updateChildCareData($data) {
    $personifyID = $this->personifyUserHelper->personifyGetId();

    $body = '
    <StoredProcedureRequest>
    <StoredProcedureName>OPENY_GET_MYY_CHILDCARE_SESSIONS_UPDATE</StoredProcedureName>
    <SPParameterList>
        <StoredProcedureParameter>
            <Name>@id</Name>
            <Value>' . $personifyID . '</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@ordDate</Name>
            <Value>' . $data['date'] . '</Value>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
            <Name>@stype</Name>
            <Value>' . $data['type'] . '</Value>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
            <Name>@state</Name>
            <Value>' . $data['value'] .'</Value>
        </StoredProcedureParameter>
    </SPParameterList>
</StoredProcedureRequest>
    ';

    $result = $this->personifyClient->doAPIcall('POST', 'GetStoredProcedureDataJSON?$format=json', $body, 'xml');
    $results = json_decode($result['Data'], TRUE);

    if (isset($results['Table'][0])) {
      return $results['Table'][0];
    } else {
      return [
        'error' => 1,
        'data' => $data
      ];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function addChildcareSessions($data) {
    $chunks = explode(',', $data);
    $result = [];
    foreach ($chunks as $chunk) {
      $info = explode(' ', $chunk);
      $data = [
        'date' => $info[0],
        'type' => $info[1],
        'value' => $info[2]
      ];

      $result[] = $this->updateChildCareData($data);

    }

    return $result;
  }

}
