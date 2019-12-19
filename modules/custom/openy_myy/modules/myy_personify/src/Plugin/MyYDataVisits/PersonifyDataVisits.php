<?php

namespace Drupal\myy_personify\Plugin\MyYDataVisits;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
    $this->logger = $loggerChannelFactory->get('personify_authenticator');
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
        $item['USR_BRANCH_ID'] = $item['USR_BRANCH'];
        $item['USR_BRANCH'] = $this->personifyUserHelper->locationMapping(trim($item['USR_BRANCH']));
        $visits[] = $item;
      }
    }

    return $visits;
  }

}
