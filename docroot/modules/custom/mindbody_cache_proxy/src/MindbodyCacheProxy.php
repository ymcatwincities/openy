<?php

namespace Drupal\mindbody_cache_proxy;

use Drupal\Core\State\State;
use Drupal\mindbody\MindbodyClientInterface;
use Drupal\mindbody_cache_proxy\Entity\MindbodyCache;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class MindbodyProxy.
 *
 * @package Drupal\ymca_mindbody
 */
class MindbodyCacheProxy implements MindbodyCacheProxyInterface {

  /**
   * Collection name.
   */
  const STORAGE = 'mindbody_cache_proxy';

  /**
   * MindbodyClient.
   *
   * @var MindbodyClientInterface
   */
  protected $mindbodyClient;

  /**
   * Query factory.
   *
   * @var QueryFactory
   */
  protected $queryFactory;

  /**
   * State.
   *
   * @var State
   */
  protected $state;

  /**
   * Manager.
   *
   * @var MindbodyCacheProxyManagerInterface
   */
  protected $manager;

  /**
   * MindbodyProxy constructor.
   *
   * @param MindbodyClientInterface $mindbody_client
   *   MindBody client.
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param State $state
   *   State.
   * @param MindbodyCacheProxyManagerInterface $manager
   *   Manager.
   */
  public function __construct(MindbodyClientInterface $mindbody_client, QueryFactory $query_factory, State $state, MindbodyCacheProxyManagerInterface $manager) {
    $this->mindbodyClient = $mindbody_client;
    $this->queryFactory = $query_factory;
    $this->state = $state;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function call($service, $endpoint, array $params = [], $cache = TRUE) {
    $params_str = '';

    if ($cache) {
      $params_str = serialize($params);

      // Check whether the cache exists. If so, return it.
      $result = $this->checkCache($service, $endpoint, $params_str);
      if ($result) {
        $this->updateStats('hit');
        return $result;
      }
    }

    // There is no cache. Make the call and create cache item.
    $result = $this->mindbodyClient->call($service, $endpoint, $params);

    if ($cache) {
      $cache = MindbodyCache::create([
        'field_mindbody_cache_service' => $service,
        'field_mindbody_cache_endpoint' => $endpoint,
        'field_mindbody_cache_params' => $params_str,
        'field_mindbody_cache_data' => serialize($result),
      ]);

      // If params contain location ID save it.
      $key = 'LocationIDs';
      if (array_key_exists($key, $params)) {
        // Location IDs may be multiple, but we need only single one.
        if (count($params[$key]) == 1) {
          $cache->set('field_mindbody_cache_location', reset($params[$key]));
        }
      }

      $cache->setName(sprintf('Cache item: %s, %s', $service, $endpoint));
      $cache->save();
    }

    $this->updateStats('miss');

    return $result;
  }

  /**
   * Update stats.
   *
   * @param string $type
   *   Either: 'hit' or 'miss' string.
   */
  protected function updateStats($type) {
    $current = $this->state->get(self::STORAGE);
    $date_time = new \DateTime();
    $date_time->setTimezone(new \DateTimeZone('UTC'));

    // Create new stats item.
    if (!$current) {
      $this->manager->flushStats();
      return;
    }

    $current->{$type}++;
    $this->state->set(self::STORAGE, $current);
    \Drupal::moduleHandler()->invokeAll('mindbody_cache_proxy_update_stats', [$current]);
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
