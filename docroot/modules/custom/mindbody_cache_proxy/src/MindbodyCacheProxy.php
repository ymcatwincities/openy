<?php

namespace Drupal\mindbody_cache_proxy;

use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\mindbody\MindbodyClientInterface;
use Drupal\mindbody_cache_proxy\Entity\MindbodyCache;

/**
 * Class MindbodyProxy.
 *
 * @package Drupal\ymca_mindbody
 */
class MindbodyCacheProxy implements MindbodyCacheProxyInterface {

  /**
   * MindbodyClient.
   *
   * @var MindbodyClientInterface
   */
  protected $mindbodyClient;

  /**
   * Query factory.
   *
   * @var QueryFactoryInterface
   */
  protected $queryFactory;

  /**
   * MindbodyProxy constructor.
   *
   * @param MindbodyClientInterface $mindbody_client
   *   MindBody client.
   * @param QueryFactoryInterface $query_factory
   *   Query factory.
   */
  public function __construct(MindbodyClientInterface $mindbody_client, QueryFactoryInterface $query_factory) {
    $this->mindbodyClient = $mindbody_client;
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function call($service, $endpoint, array $params = []) {
    $params_str = serialize($params);

    // Check whether the cache exists. If so, return it.
    $result = $this->checkCache($service, $endpoint, $params_str);
    if ($result) {
      return $result;
    }

    // There is no cache. Make the call and create cache item
    $result = $this->mindbodyClient->call($service, $endpoint, $params);
    $cache = MindbodyCache::create([
      'field_mindbody_cache_service' => $service,
      'field_mindbody_cache_endpoint' => $endpoint,
      'field_mindbody_cache_params' => $params_str,
      'field_mindbody_cache_data' => serialize($result),
    ]);
    $cache->setName(sprintf('Cache item: %s, %s', $service, $endpoint));
    $cache->save();

    return $result;
  }

  /**
   * Check whether the cache exists.
   *
   * @param string $service
   *   Service name.
   * @param string $endpoint
   *   Endpoint name.
   * @param string $params
   *   Parameters.
   *
   * @return mixed
   *   stdClass object or FALSE if there is no cache.
   */
  protected function checkCache($service, $endpoint, $params) {
    $result = $this->queryFactory->get('mindbody_cache')
      ->condition('field_mindbody_cache_service', $service)
      ->condition('field_mindbody_cache_endpoint', $endpoint)
      ->condition('field_mindbody_cache_params', $params)
      ->execute();

    if (!empty($result)) {
      $id = reset($result);
      $cache = MindbodyCache::load($id);
      return unserialize($cache->field_mindbody_cache_data->value);
    }

    return FALSE;
  }

}
