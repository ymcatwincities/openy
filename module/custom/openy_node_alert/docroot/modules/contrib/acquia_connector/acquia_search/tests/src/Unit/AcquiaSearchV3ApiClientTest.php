<?php

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\acquia_search\AcquiaSearchV3ApiClient;
use Prophecy\Argument;
use Drupal\Component\Serialization\Json;

/**
 * @coversDefaultClass \Drupal\acquia_search\AcquiaSearchV3ApiClient
 *
 * @group Acquia search
 */
class AcquiaSearchV3ApiClientTest extends UnitTestCase {

  protected $search_v3_host;
  protected $search_v3_api_key;
  protected $guzzleClient;
  protected $cacheBackend;

  public function setUp() {
    parent::setUp();
    $this->search_v3_host = 'https://api.sr-dev.acquia.com';
    $this->search_v3_api_key = 'XXXXXXXXXXyyyyyyyyyyXXXXXXXXXXyyyyyyyyyy';

    $path = '/index/network_id/get_all?network_id=WXYZ-12345';
    $data = array(
      'host' => $this->search_v3_host,
      'headers' => array(
        'x-api-key' => $this->search_v3_api_key,
      )
    );
    $uri = $data['host'] . $path;
    $options = array(
      'headers' => $data['headers'],
      'body' => Json::encode($data),
    );

    $json = '[{"name":"WXYZ-12345.dev.drupal8","host":"test.sr-dev.acquia.com"}]';
    $stream = $this->prophesize('Psr\Http\Message\StreamInterface');
    $stream->getSize()->willReturn(1000);
    $stream->read(1000)->willReturn($json);

    $response = $this->prophesize('Psr\Http\Message\ResponseInterface');
    $response->getStatusCode()->willReturn(200);
    $response->getBody()->willReturn($stream);

    $this->guzzleClient = $this->prophesize('\GuzzleHttp\Client');
    $this->guzzleClient->get($uri, $options)->willReturn($response);

    $this->cacheBackend = $this->prophesize('\Drupal\Core\Cache\CacheBackendInterface');
  }

  /**
   * Tests call to search v3 api.
   */
  public function testSearchV3ApiCall() {
    $expected = [
      [
        'balancer' => 'test.sr-dev.acquia.com',
        'core_id' => 'WXYZ-12345.dev.drupal8',
        'version' => 'v3'
      ]
    ];

    $client = new AcquiaSearchV3ApiClient($this->search_v3_host, $this->search_v3_api_key, $this->guzzleClient->reveal(), $this->cacheBackend->reveal());
    $this->assertEquals($expected, $client->getSearchV3Indexes('WXYZ-12345'));
    $this->cacheBackend->set('acquia_search.v3indexes', $expected, time() + (24 * 60 * 60))->shouldHaveBeenCalledTimes(1);
  }

  /**
   * Test to validate cache.
   */
  public function testSearchV3ApiCache() {
    $expected = [
      [
        'balancer' => 'test.sr-dev.acquia.com',
        'core_id' => 'WXYZ-12345.dev.drupal8',
        'version' => 'v3'
      ]
    ];
    $client = new AcquiaSearchV3ApiClient($this->search_v3_host, $this->search_v3_api_key, $this->guzzleClient->reveal(), $this->cacheBackend->reveal());

    $fresh_cache = (object) [
      'data' => $expected,
      'expire' => time() + (24 * 60 * 60)
    ];
    $this->cacheBackend->get('acquia_search.v3indexes')->willReturn($fresh_cache);
    $client->getSearchV3Indexes('WXYZ-12345');

    // New cache should not have been set when there is already a valid cache.
    $this->cacheBackend->set('acquia_search.v3indexes', $expected, time() + (24 * 60 * 60))->shouldHaveBeenCalledTimes(0);

    $expired_cache = (object) [
      'data' => $expected,
      'expire' => 0
    ];
    $this->cacheBackend->get('acquia_search.v3indexes')->willReturn($expired_cache);
    $client->getSearchV3Indexes('WXYZ-12345');

    // When the current cache value is expired, it should have set a new one.
    $this->cacheBackend->set('acquia_search.v3indexes', $expected, time() + (24 * 60 * 60))->shouldHaveBeenCalledTimes(1);
  }

}
