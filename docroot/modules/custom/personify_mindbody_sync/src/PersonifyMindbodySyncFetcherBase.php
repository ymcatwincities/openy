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

}
