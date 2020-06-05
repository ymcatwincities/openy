<?php

namespace Drupal\groupex_form_cache;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\groupex_form_cache\Entity\GroupexFormCache;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\openy_socrates\OpenyCronServiceInterface;
use Drupal\openy_group_schedules\GroupexRequestTrait;

/**
 * Class GroupexFormCacheWarmer.
 */
class GroupexFormCacheWarmer implements OpenyCronServiceInterface {

  use GroupexRequestTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * GroupexFormCacheManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger factory.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(ConfigFactory $config_factory, LoggerChannelFactory $logger_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get(GroupexFormCacheManager::CHANNEL);
    $this->entityTypeManager = $entity_type_manager;
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
    // Warm up frequent requests.
    $this->request(['query' => ['classes' => TRUE]]);
    $locations = $this->request(['query' => ['locations' => TRUE]]);

    // Warm up cache for current day for all locations.
    $timezone = new \DateTimeZone($this->configFactory->get('system.date')->get('timezone')['default']);
    $datetime = new \DateTime('today midnight', $timezone);
    $start = $datetime->getTimestamp();
    $end = $datetime->add(new \DateInterval('P1D'))->getTimestamp();
    foreach ($locations as $location) {
      $options = [
        'query' => [
          'schedule' => TRUE,
          'desc' => "true",
          'location' => [(string) $location->id],
          'start' => $start,
          'end' => $end,
        ]
      ];
      $this->request($options);
    }
  }

  /**
   * Walk thorough the existing cache items and warm them up.
   */
  private function traverse() {
    if (!$result = $this->entityTypeManager->getStorage(GroupexFormCacheManager::ENTITY_TYPE)->getQuery()->execute()) {
      return;
    }

    // Loop over each cache entity.
    $this->logger->info('Starting warming up %count cache entities.', ['%count' => count($result)]);
    krsort($result);
    foreach ($result as $item) {
      // Let's protect GroupEx servers.
      sleep(1);

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
   * @param \Drupal\groupex_form_cache\GroupexFormCacheInterface $entity
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
    if (!is_null($this->request($options, FALSE))) {
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
   * @param \Drupal\groupex_form_cache\GroupexFormCacheInterface $entity
   *   Entity.
   *
   * @return bool
   *   TRUE if cache entity is valid.
   */
  private function isValid(GroupexFormCacheInterface $entity) {
    $request_time = \Drupal::time()->getRequestTime();
    $options = unserialize($entity->field_gfc_options->value);
    if (array_key_exists('start', $options['query']) && array_key_exists('end', $options['query'])) {
      if ($request_time >= $options['end']) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Check if entity needs warming up.
   *
   * @param \Drupal\groupex_form_cache\GroupexFormCacheInterface $entity
   *   Entity.
   *
   * @return bool
   *   TRUE if needs warming up.
   */
  private function needsWarmUp(GroupexFormCacheInterface $entity) {
    if (!$created = $entity->field_gfc_created->value) {
      return TRUE;
    }
    $request_time = \Drupal::time()->getRequestTime();

    $config = $this->configFactory->get('groupex_form_cache.settings');
    if (($request_time - $created) > $config->get('cache_warmup_interval')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function runCronServices() {
    $this->warm();
  }

}
