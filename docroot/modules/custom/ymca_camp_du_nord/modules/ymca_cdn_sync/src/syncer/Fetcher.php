<?php

namespace Drupal\ymca_cdn_sync\syncer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ymca_cdn_sync\SyncException;
use GuzzleHttp\Client;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class Fetcher.
 *
 * @property string $dataServiceUrl
 * @property string $dataServiceUser
 * @property string $dataServicePassword
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
class Fetcher implements FetcherInterface {

  /**
   * Config name.
   */
  const CONFIG_NAME = 'ymca_cdn_sync.settings';

  /**
   * Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Wrapper.
   *
   * @var \Drupal\ymca_cdn_sync\syncer\WrapperInterface
   */
  protected $wrapper;

  /**
   * Fetcher constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   Client.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger channel.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config factory.
   */
  public function __construct(Client $client, LoggerChannelInterface $logger, ConfigFactoryInterface $config, WrapperInterface $wrapper) {
    $this->client = $client;
    $this->logger = $logger;
    $this->config = $config;
    $this->wrapper = $wrapper;
  }

  /**
   * Magic method to get properties.
   *
   * @todo Move to lower level to reuse.
   *
   * @param mixed $property
   *   Property name.
   *
   * @return array|mixed|null
   *   Property value.
   */
  public function __get($property) {
    $config = $this->config->get(self::CONFIG_NAME);
    $property_underscore = $this->fromCamelCase($property);

    if (property_exists($this, $property_underscore)) {
      return $this->$property_underscore;
    }

    return $config->get($property_underscore);
  }

  /**
   * Convert CamelCase to underscore.
   *
   * @todo Move to lower level to reuse.
   *
   * @see https://stackoverflow.com/a/1993772/1547435
   *
   * @param string $input
   *   Input.
   *
   * @return string
   *   Output.
   */
  private function fromCamelCase($input) {
    preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
    $ret = $matches[0];
    foreach ($ret as &$match) {
      $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
    }
    return implode('_', $ret);
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $this->wrapper->setSourceData([]);

    $options = [
      'timeout' => 90,
      'headers' => [
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $this->dataServiceUser,
        $this->dataServicePassword,
      ],
    ];
    $today = new DrupalDateTime();
    $today = $today->format('Y-m-d');
    // @todo Make this XML from array. Possibly move to config.
    // @todo Make iterator if there is more item per page.
    $max_count = 10000;
    $options['body'] = "<CL_GetProductListingInput>
      <ProductClassCode>RS</ProductClassCode>
      <AvailableToOrdersFlag>true</AvailableToOrdersFlag>
      <EcommerceFlag>true</EcommerceFlag>
      <EcommerceBeginDate>$today</EcommerceBeginDate>
      <EcommerceEndDate>$today</EcommerceEndDate>
    </CL_GetProductListingInput>";

    try {
      $endpoint = $this->dataServiceUrl . '/CL_GetProductListing';

      $response = $this->client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() == '200') {
        $products = [];
        $contents = $response->getBody()->getContents();
        $xml = simplexml_load_string($contents);

        // Check max count.
        // @todo Remove after implementing page iterator.
        if ($xml->TotalCount > $max_count) {
          $this->logger->error('Max count reached. Please implement pagination');
        }

        $listingItems = $xml->ProductListingRecord;
        $children = $listingItems->children();
        $a = $b = $c = 0;
        foreach ($children->CL_ProductListingRecord as $product) {
          $c++;
          if ($product->WebDisplayFlag == 'false') {
            $a++;
            continue;
          }
          $b++;
          $products[] = $product;
        }
        $this->logger->notice($a . ' skipped. ' . $b . ' imported. ' . $c . ' total');
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
        throw new SyncException('Failed to get Personify products. Please, examine the logs.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get Personify data: %msg', ['%msg' => $e->getMessage()]);
      throw new SyncException('Failed to get Personify products. Please, examine the logs.');
    }

    $this->wrapper->setSourceData($products);
  }

}
