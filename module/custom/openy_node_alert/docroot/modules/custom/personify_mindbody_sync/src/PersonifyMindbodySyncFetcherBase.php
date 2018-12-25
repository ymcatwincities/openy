<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\Client;

/**
 * Class PersonifyMindbodySyncFetcher.
 *
 * @package Drupal\personify_mindbody_sync
 */
abstract class PersonifyMindbodySyncFetcherBase implements PersonifyMindbodySyncFetcherInterface {

  /**
   * Test client ID.
   */
  const TEST_CLIENT_ID = '2052596923';

  /**
   * PersonifyMindbodySyncWrapper definition.
   *
   * @var PersonifyMindbodySyncWrapper
   */
  protected $wrapper;

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
   * @var LoggerChannel
   */
  protected $logger;

  /**
   * Is production flag.
   *
   * @var bool
   */
  protected $isProduction;

  /**
   * PersonifyMindbodySyncFetcher constructor.
   *
   * @param PersonifyMindbodySyncWrapper $wrapper
   *   Wrapper.
   * @param Client $client
   *   Http client.
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(PersonifyMindbodySyncWrapper $wrapper, Client $client, ConfigFactory $config, LoggerChannelInterface $logger) {
    $this->wrapper = $wrapper;
    $this->client = $client;
    $this->config = $config;
    $this->logger = $logger;

    $settings = $this->config->get('personify_mindbody_sync.settings');
    $this->isProduction = (bool) $settings->get('is_production');
  }

  /**
   * Get Personify orders.
   *
   * @param string $lastDataAccessDate
   *   Example: 2000-01-01T11:20:00.
   *
   * @return array
   *   An array of Personify orders.
   */
  protected function getData($lastDataAccessDate) {
    $orders = [];
    $settings = $this->config->get('ymca_personify.settings');

    $options = [
      'json' => [
        'CL_MindBodyCustomerOrderInput' => [
          'LastDataAccessDate' => $lastDataAccessDate,
        ],
      ],
      'headers' => [
        'Content-Type' => 'application/json;charset=utf-8',
      ],
      'auth' => [
        $settings->get('customer_orders_username'),
        $settings->get('customer_orders_password'),
      ],
    ];

    try {
      $response = $this->client->request('POST', $settings->get('customer_orders_endpoint'), $options);
      if ($response->getStatusCode() == '200') {
        $body = $response->getBody();
        $data = json_decode($body->getContents());

        // @todo Implement mocks.
        if (FALSE) {
          // @todo Load this data form the latest cache entity
          $mockObject = unserialize('O:8:"stdClass":33:{s:3:"$id";s:1:"2";s:11:"InternalKey";N;s:13:"NavigationKey";N;s:7:"OrderNo";s:10:"2012809899";s:11:"OrderLineNo";i:1;s:9:"OrderDate";s:19:"2018-05-02T00:00:00";s:9:"ProductId";i:142706076;s:13:"ParentProduct";s:20:"34_PT_20_SESS_60_MIN";s:11:"ProductCode";s:20:"34_PT_20_SESS_60_MIN";s:9:"Subsystem";s:4:"MISC";s:13:"RateStructure";s:6:"Member";s:8:"RateCode";s:3:"STD";s:14:"LineStatusCode";s:1:"A";s:14:"LineStatusDate";s:23:"2018-05-02T20:03:42.140";s:13:"OrderQuantity";i:1;s:9:"UnitPrice";d:1099;s:11:"TotalAmount";d:1099;s:16:"MasterCustomerId";s:10:"2052596923";s:13:"SubCustomerId";i:0;s:9:"FirstName";s:6:"Thomas";s:8:"LastName";s:7:"Christy";s:10:"GenderCode";s:4:"MALE";s:9:"BirthDate";s:19:"1971-10-15T00:00:00";s:12:"PrimaryPhone";s:12:"763-441-6392";s:24:"PrimaryPhoneLocationCode";s:4:"HOME";s:25:"PrimaryPhoneDoNotCallFlag";b:0;s:13:"PrimaryMobile";s:12:"763-859-5022";s:25:"PrimaryMobileLocationCode";s:4:"HOME";s:26:"PrimaryMobileDoNotCallFlag";b:0;s:12:"PrimaryEmail";s:28:"tom@frerichsconstruction.com";s:24:"PrimaryEmailLocationCode";s:4:"HOME";s:28:"PrimaryEmailDoNotContactFlag";b:0;s:27:"MindBodyCustomerOrderDetail";O:8:"stdClass":1:{s:4:"$ref";s:1:"1";}}');
          $data->MindBodyCustomerOrderDetail = [$mockObject];
        }

        foreach ($data->MindBodyCustomerOrderDetail as $order) {
          // In test mode proceed orders only for test user.
          if (!$this->isProduction && $order->MasterCustomerId != self::TEST_CLIENT_ID) {
            continue;
          }
          $orders[] = $order;
        }
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
      throw new \Exception('Personify is down.');
    }

    return $orders;
  }

  /**
   * Check whether order is "personal training".
   *
   * @param \stdClass $order
   *   Order.
   *
   * @return bool
   *   TRUE if "personal training".
   */
  protected function isPersonalTrainingProductCode(\stdClass $order) {
    $code = $order->ProductCode;
    if (strpos($code, 'BY') !== FALSE) {
      return FALSE;
    }

    return TRUE;
  }

}
