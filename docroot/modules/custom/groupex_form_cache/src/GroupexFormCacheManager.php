<?php

namespace Drupal\groupex_form_cache;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\groupex_form_cache\Entity\GroupexFormCache;

/**
 * Class GroupexFormCacheManager.
 */
class GroupexFormCacheManager {

  /**
   * Entity type Id.
   */
  const ENTITY_TYPE = 'groupex_form_cache';

  /**
   * Channel name.
   */
  const CHANNEL = 'groupex_form_cache';

  /**
   * Query factory.
   *
   * @var QueryFactory
   */
  protected $queryFactory;

  /**
   * Config.
   *
   * @var ImmutableConfig
   */
  protected $config;

  /**
   * Logger.
   *
   * @var LoggerChannel
   */
  protected $logger;

  /**
   * Entity type manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * GroupexFormCacheManager constructor.
   *
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param ConfigFactory $config_factory
   *   Config factory.
   * @param LoggerChannelFactory $logger_factory
   *   Logger factory.
   * @param EntityTypeManager $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(QueryFactory $query_factory, ConfigFactory $config_factory, LoggerChannelFactory $logger_factory, EntityTypeManager $entity_type_manager) {
    $this->queryFactory = $query_factory;
    $this->config = $config_factory->get('groupex_form_cache.settings');
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get cache.
   *
   * @param array $options
   *   Options.
   *
   * @return mixed
   *   Data.
   */
  public function getCache($options) {
    array_multisort($options);
    $search = serialize($options);

    $local_cache = &drupal_static('groupex_form_cache_static');
    if (!isset($local_cache[$search])) {
      $result = $this->queryFactory->get(self::ENTITY_TYPE)
        ->condition('field_gfc_options', $search)
        ->condition('field_gfc_created', REQUEST_TIME - $this->config->get('cache_max_age'), '>')
        ->execute();

      $local_cache[$search] = FALSE;
      if (!empty($result)) {
        $id = reset($result);
        $cache = GroupexFormCache::load($id);
        $local_cache[$search] = unserialize($cache->field_gfc_response->value);
      }
    }

    return $local_cache[$search];
  }

  /**
   * Set cache.
   *
   * @param array $options
   *   Options.
   * @param array $data
   *   Data.
   */
  public function setCache($options, $data) {
    array_multisort($options);
    $cache = GroupexFormCache::create([
      'field_gfc_created' => REQUEST_TIME,
      'field_gfc_options' => serialize($options),
      'field_gfc_response' => serialize($data)
    ]);
    $cache->setName('Cache item');
    $cache->save();
  }

  /**
   * Clear all caches.
   */
  public function resetCache() {
    $storage = $this->entityTypeManager->getStorage(self::ENTITY_TYPE);

    $result = $this->queryFactory->get(self::ENTITY_TYPE)->execute();
    if (empty($result)) {
      return;
    }

    $chunks = array_chunk($result, 10);
    foreach ($chunks as $chunk) {
      $entities = GroupexFormCache::loadMultiple($chunk);
      $storage->delete($entities);
    }

    $this->logger->info('The cache was cleared.');
  }

}
