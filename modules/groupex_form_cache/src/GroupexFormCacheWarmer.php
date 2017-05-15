<?php

namespace Drupal\groupex_form_cache;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\groupex_form_cache\Entity\GroupexFormCache;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\ymca_groupex\GroupexRequestTrait;

/**
 * Class GroupexFormCacheWarmer.
 */
class GroupexFormCacheWarmer {

  use GroupexRequestTrait;

  /**
   * Query factory.
   *
   * @var QueryFactory
   */
  protected $queryFactory;

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $config;

  /**
   * Logger.
   *
   * @var LoggerChannel
   */
  protected $logger;

  /**
   * GroupexFormCacheManager constructor.
   *
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param ConfigFactory $config_factory
   *   Config factory.
   * @param LoggerChannelFactory $logger_factory
   *   Logger factory.
   */
  public function __construct(QueryFactory $query_factory, ConfigFactory $config_factory, LoggerChannelFactory $logger_factory) {
    $this->queryFactory = $query_factory;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get(GroupexFormCacheManager::CHANNEL);
  }

  /**
   * Warms up the cache entities.
   */
  public function warm() {
    Timer::start('warm');

    $this->simpleWarmUp();
    $this->traverse();

    $this->logger->info('Cache warmer finish it\'s run in %sec sec.', ['%sec' => Timer::read('warm') / 1000]);
    Timer::stop('warm');
  }

  /**
   * Warm up simple elements.
   */
  private function simpleWarmUp() {
    $this->request(['locations' => TRUE]);
    $this->request(['classes' => TRUE]);
  }

  /**
   * Walk thorough the existing cache items and warm them up.
   */
  private function traverse() {
    if (!$result = $this->queryFactory->get(GroupexFormCacheManager::ENTITY_TYPE)->execute()) {
      return;
    }

    // Loop over each cache entity.
    foreach ($result as $item) {
      if (!$entity = GroupexFormCache::load($item)) {
        continue;
      }

      if (!$this->needsWarmUp($entity)) {
        continue;
      }

      $this->warmUp($entity);
    }
  }

  /**
   * Warm up single entity.
   *
   * @param GroupexFormCacheInterface $entity
   *   Entity.
   */
  private function warmUp(GroupexFormCacheInterface $entity) {
    $options = unserialize($entity->field_gfc_options->value);

    // If entity is not valid we should make it valid by providing dates.
    if (!$this->isValid($entity)) {
      // New options have to have the save start end interval.
      $offset = $options['query']['end'] - $options['query']['start'];

      $timezone = new \DateTimeZone($this->configFactory->get('system.date')->get('timezone')['default']);
      $datetime = new \DateTime('today midnight', $timezone);

      $options['query']['start'] = $datetime->getTimestamp();
      $options['query']['end'] = $datetime->getTimestamp() + $offset;
    }

    // Make a new request with appropriate options to create new cache entity.
    if (FALSE !== $this->request($options, FALSE)) {
      try {
        $entity->delete();
      }
      catch (\Exception $e) {
        $this->logger->notice('Possible race condition. Tried to delete an entity which does not exist');
      }
    }
  }

  /**
   * Check if entity is valid.
   *
   * Commonly we assume that cache entity with dates in the past is invalid.
   *
   * @param GroupexFormCacheInterface $entity
   *   Entity.
   *
   * @return bool
   *   TRUE if cache entity is valid.
   */
  private function isValid(GroupexFormCacheInterface $entity) {
    $options = unserialize($entity->field_gfc_options->value);
    if (array_key_exists('start', $options['query']) && array_key_exists('end', $options['query'])) {
      if (REQUEST_TIME >= $options['end']) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Check if entity needs warming up.
   *
   * @param GroupexFormCacheInterface $entity
   *   Entity.
   *
   * @return bool
   *   TRUE if needs warming up.
   */
  private function needsWarmUp(GroupexFormCacheInterface $entity) {
    if (!$created = $entity->field_gfc_created->value) {
      return TRUE;
    }

    $config = $this->configFactory->get('groupex_form_cache.settings');
    if ((REQUEST_TIME - $created) > $config->get('cache_warmup_interval')) {
      return TRUE;
    }

    return FALSE;
  }

}
