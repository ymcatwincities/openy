<?php

namespace Drupal\myy_personify\Plugin\MyYDataVisits;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\myy_personify\PersonifyUserData;
use Drupal\myy_personify\PersonifyUserHelper;
use Drupal\openy_myy\PluginManager\MyYDataVisitsInterface;
use Drupal\personify\PersonifyClient;
use Drupal\personify\PersonifySSO;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Personify Visits stat data plugin.
 *
 * @MyYDataVisits(
 *   id = "myy_personify_data_visits",
 *   label = "MyY Data Profile: Visits",
 *   description = "Visits data from Personify",
 * )
 */
class PersonifyDataVisits extends PluginBase implements MyYDataVisitsInterface, ContainerFactoryPluginInterface {

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
    $this->logger = $loggerChannelFactory->get('personify_authenticator');
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
  public function getVisitsCountByDate($personifyID, $start_date, $finish_date) {

    $personifyID = $this->personifyUserHelper->personifyGetId();
    $visits = $this->personifyClient->getVisitCountByDate(
      $personifyID,
      \DateTime::createFromFormat('Y-m-d', $start_date),
      \DateTime::createFromFormat('Y-m-d', $finish_date)
    );

    return [
      'total' => $visits->TotalVisits,
    ];
  }

  /**
   * @param array $personifyIDs
   * @param $start_date
   * @param $finish_date
   *
   * @return mixed|string
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getVisitsDetails($personifyIDs, $start_date, $finish_date) {

    $body = '
    <StoredProcedureRequest>
    <StoredProcedureName>OPENY_GET_VISITS_BY_IDS</StoredProcedureName>
    <IsUserDefinedFunction>false</IsUserDefinedFunction>
    <SPParameterList>
        <StoredProcedureParameter>
            <Name>@ids</Name>
            <Value>' . $personifyIDs . '</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@dateStart</Name>
            <Value>' . $start_date . '</Value>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
            <Name>@dateEnd</Name>
            <Value>'. $finish_date . '</Value>
        </StoredProcedureParameter>
    </SPParameterList>
    </StoredProcedureRequest>
    ';

    $data = $this->personifyClient->doAPIcall('POST', 'GetStoredProcedureDataJSON?$format=json', $body, 'xml');
    $results = json_decode($data['Data'], TRUE);

    $visits = [];
    if (!empty($results['Table'])) {
      foreach ($results['Table'] as $item) {
        $name = explode(' ', $item['USR_LAST_FIRST_NAME']);
        $short_name = $name[1][0] . ' ' . $name[0][0];
        $item['USR_BRANCH_ID'] = $item['USR_BRANCH'];
        $item['USR_BRANCH'] = $this->personifyUserHelper->locationMapping(trim($item['USR_BRANCH']));
        $date = str_replace('  ', ' ', $item['ADDDATE']);
        $date = explode(' ', $date);
        $item['CUSTOM_USR_DATE'] = implode(' ', [$date[0], $date[1], $date[2]]);
        $item['CUSTOM_USR_TIME'] = !empty($date[4]) ? $date[4] : $date[3];
        $item['ADDDATE_TIMESTAMP'] = strtotime($item['ADDDATE']);
        $item['SHORT_NAME'] = $short_name;
        $visits[] = $item;
      }
    }

    // Sort results.
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    $parameters = $request->query->all();
    // Sort by date_ASC even if sort query parameter has not passed.
    if (empty($parameters['sort']) || (!empty($parameters['sort']) && $parameters['sort'] == 'date_ASC')) {
      usort($visits, function ($a, $b) {
        return ($a['ADDDATE_TIMESTAMP'] < $b['ADDDATE_TIMESTAMP']) ? -1 : 1;
      });
    }
    if (!empty($parameters['sort']) && $parameters['sort'] == 'date_DESC') {
      usort($visits, function ($a, $b) {
        return ($a['ADDDATE_TIMESTAMP'] > $b['ADDDATE_TIMESTAMP']) ? -1 : 1;
      });
    }

    return $visits;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisitsOverview() {

    $family = $this->personifyUserData->getFamilyData();
    $pids = [];
    foreach ($family['household'] as $fmember) {
      $id = $this->personifyUserHelper->personifyGetId();
      if (!empty($fmember['RelatedMasterCustomerId'])) {
        $id = $fmember['RelatedMasterCustomerId'];
      }
      $name = explode(', ', $fmember['name']);
      $short_name = $name[1][0] . ' ' . $name[0][0];
      $pids[] = $id;
      // Fill array initially with empty values in order to show all households even with 0 visits.
      $overview[$id] = [
        'total' => 0,
        'name' => $fmember['name'],
        'short_name' => $short_name,
        'unique' => [],
        'unique_total' => 0
      ];
    }

    // Get data from the start of the month.
    $start_date = date('Y-m-01');
    $finish_date = date('Y-m-d');

    $body = '
    <StoredProcedureRequest>
    <StoredProcedureName>OPENY_GET_VISITS_BY_IDS</StoredProcedureName>
    <IsUserDefinedFunction>false</IsUserDefinedFunction>
    <SPParameterList>
        <StoredProcedureParameter>
            <Name>@ids</Name>
            <Value>' . implode(',', $pids) . '</Value>
        </StoredProcedureParameter>
                <StoredProcedureParameter>
            <Name>@dateStart</Name>
            <Value>' . $start_date . '</Value>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
            <Name>@dateEnd</Name>
            <Value>'. $finish_date . '</Value>
        </StoredProcedureParameter>
    </SPParameterList>
    </StoredProcedureRequest>
    ';

    $data = $this->personifyClient->doAPIcall('POST', 'GetStoredProcedureDataJSON?$format=json', $body, 'xml');
    $results = json_decode($data['Data'], TRUE);
    foreach ($results['Table'] as $row) {
      $day = date('d', strtotime($row['ADDDATE']));
      $overview[$row['MASTER_CUSTOMER_ID']]['total']++;
      $overview[$row['MASTER_CUSTOMER_ID']]['name'] = $row['USR_LAST_FIRST_NAME'];
      $overview[$row['MASTER_CUSTOMER_ID']]['unique'][$day]++;
      $overview[$row['MASTER_CUSTOMER_ID']]['unique_total'] = count($overview[$row['MASTER_CUSTOMER_ID']]['unique']);
    }
    return $overview;

  }

}
