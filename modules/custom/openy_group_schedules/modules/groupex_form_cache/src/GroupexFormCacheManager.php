<?php

namespace Drupal\groupex_form_cache;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\groupex_form_cache\Entity\GroupexFormCache;
use Drupal\Component\Utility\Timer;
use Drupal\openy_socrates\OpenyCronServiceInterface;

/**
 * Class GroupexFormCacheManager.
 */
class GroupexFormCacheManager implements OpenyCronServiceInterface {

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
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * GroupexFormCacheManager constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   Query factory.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
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
  public function getCache(array $options) {
    array_multisort($options);
    $search = serialize($options);
    $request_time = \Drupal::time()->getRequestTime();

    $local_cache = &drupal_static('groupex_form_cache_static');
    if (!isset($local_cache[$search])) {
      $result = $this->queryFactory->get(self::ENTITY_TYPE)
        ->condition('field_gfc_options', $search)
        ->condition('field_gfc_created', $request_time - $this->config->get('cache_max_age'), '>')
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
  public function setCache(array $options, array $data) {
    $request_time = \Drupal::time()->getRequestTime();

    array_multisort($options);
    $cache = GroupexFormCache::create([
      'field_gfc_created' => $request_time,
      'field_gfc_options' => serialize($options),
      'field_gfc_response' => serialize($data)
    ]);
    $cache->setName('Cache item');
    $cache->save();
  }

  /**
   * Remove all caches.
   *
   * @param int $count
   *   Size of the chunk.
   */
  public function resetCache($count = 100) {
    $timer = 'reset_cache';
    Timer::start($timer);

    $result = $this->queryFactory->get(self::ENTITY_TYPE)->execute();
    if (empty($result)) {
      $this->logger->info('No cache was found. Nothing to clear.');
      Timer::stop($timer);
      return;
    }

    $this->removeByChunk($result, $count);

    $msg = 'All caches were cleared. [items removed: %items, elapsed time: %time sec.]';
    $this->logger->info(
      $msg,
      [
        '%items' => count($result),
        '%time' => round(Timer::read($timer) / 1000, 1)
      ]
    );

    Timer::stop($timer);
  }

  /**
   * Remove stale cache.
   *
   * @param int $count
   *   Size of the chunk.
   * @param int $time
   *   Time frame to calc stale cache.
   */
  public function resetStaleCache($count = 100, $time = 86400) {
    $timer = 'reset_stale_cache';
    $request_time = \Drupal::time()->getRequestTime();

    Timer::start($timer);

    $result = $this->queryFactory->get(self::ENTITY_TYPE)
      ->condition('field_gfc_created', $request_time - $time, '<')
      ->execute();
    if (empty($result)) {
      $this->logger->info('No stale cache was found. Nothing to clear.');
      Timer::stop($timer);
      return;
    }

    $this->removeByChunk($result, $count);

    $msg = 'The stale cache was cleared. [items removed: %items, elapsed time: %time sec.]';
    $this->logger->info(
      $msg,
      [
        '%items' => count($result),
        '%time' => round(Timer::read($timer) / 1000, 1)
      ]
    );

    Timer::stop($timer);
  }

  /**
   * Remove list of entities by chunk.
   *
   * @param array $ids
   *   Entity ids.
   * @param int $count
   *   Size of the chunk.
   */
  private function removeByChunk(array $ids, $count = 10) {
    $storage = $this->entityTypeManager->getStorage(self::ENTITY_TYPE);
    $chunks = array_chunk($ids, $count);
    foreach ($chunks as $chunk) {
      $entities = GroupexFormCache::loadMultiple($chunk);
      $storage->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function runCronServices() {
    $this->resetStaleCache(10, 172800);
  }

  /**
   * Quick reset of GroupEx Pro Form Cache.
   */
  public function quickResetCache() {
    $tables = [
      'groupex_form_cache',
      'groupex_form_cache__field_gfc_created',
      'groupex_form_cache__field_gfc_options',
      'groupex_form_cache__field_gfc_response',
    ];

    foreach ($tables as $table) {
      db_truncate($table)->execute();
    }
    $this->logger->info('GroupEx Pro Form Cache has been quickly erased using "TRUNCATE".');
  }

}
