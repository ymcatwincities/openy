<?php

namespace Drupal\ymca_personify;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\serialization\Encoder\XmlEncoder;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;

/**
 * Class ChildcarePaymentHistoryRequest.
 *
 * @package Drupal\ymca_personify
 */
class ChildcarePaymentHistoryRequest {

  /**
   * Test childcare user ID.
   */
  const TEST_USER_ID = '2015228900';

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
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * Is production flag.
   *
   * @var bool
   */
  protected $isProduction;

  /**
   * Creates a new ChildcarePaymentHistoryForm.
   *
   * @param Client $client
   *   Http client.
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel.
   */
  public function __construct(Client $client, ConfigFactory $config, LoggerChannelFactoryInterface $logger_factory) {
    $this->client = $client;
    $this->config = $config;
    $this->logger = $logger_factory->get('ymca_personify');
    $settings = $this->config->get('ymca_personify.settings');
    $this->isEnabledTestUser = (bool) $settings->get('is_enabled_childcare_test_user');
  }

  /**
   * Returns data from Personify.
   *
   * @param array $parameters
   *   Array of input parameters.
   *
   * @return array
   *   An array with response data from Personify.
   */
  public function personifyRequest($parameters) {
    $data = [];
    $settings = $this->config->get('ymca_personify.settings');
    $start_date = !empty($parameters['start_date']) ? DrupalDateTime::createFromTimestamp(strtotime($parameters['start_date']))->format('Y-m-d') . 'T12:00:00' : '';
    $end_date = !empty($parameters['end_date']) ? DrupalDateTime::createFromTimestamp(strtotime($parameters['end_date']))->format('Y-m-d') . 'T12:00:00' : '';
    $client_id = isset($_COOKIE['Drupal_visitor_personify_id']) ? $_COOKIE['Drupal_visitor_personify_id'] : '';
    if ($this->isEnabledTestUser) {
      $client_id = self::TEST_USER_ID;
    }
    $options = [
      'body' => '<CL_ChildcarePaymentInfoInput>
        <BillMasterCustomerId>' . $client_id . '</BillMasterCustomerId>
        <ReceiptStartDate>' . $start_date . '</ReceiptStartDate>
        <ReceiptEndDate>' . $end_date . '</ReceiptEndDate>
        <BillSubCustomerId>0</BillSubCustomerId>
        <ProductClassCodes>CC,LC,PS,RD,SC,DC</ProductClassCodes>
        <DescriptionLike>NOT LIKE</DescriptionLike>
        <Descriptions>%Change%,%late%fee%,%late%pick%,%lunch%</Descriptions>
        <ProductCodeLike>NOT LIKE</ProductCodeLike>
        <ProductCodes>%_DC_9%%%</ProductCodes>
        </CL_ChildcarePaymentInfoInput>',
      'headers' => [
        'Authorization' => $settings->get('childcare_authorization'),
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $settings->get('customer_orders_username'),
        $settings->get('customer_orders_password'),
      ],
    ];

    try {
      $response = $this->client->request('POST', $settings->get('childcare_endpoint'), $options);
      if ($response->getStatusCode() == '200') {
        $body = $response->getBody();
        $xml = $body->getContents();
        $xml = preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $xml);
        $encoder = new XmlEncoder();
        $data = $encoder->decode($xml, 'xml');
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
    }
    return $data;
  }

}
