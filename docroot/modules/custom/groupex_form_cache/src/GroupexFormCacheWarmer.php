<?php

namespace Drupal\groupex_form_cache;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class GroupexFormCacheWarmer.
 */
class GroupexFormCacheWarmer {

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
   * GroupexFormCacheManager constructor.
   *
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param ConfigFactory $config_factory
   *   Config factory.
   */
  public function __construct(QueryFactory $query_factory, ConfigFactory $config_factory) {
    $this->queryFactory = $query_factory;
    $this->config = $config_factory->get('groupex_form_cache.settings');
  }

  /**
   * Warms up the cache entities.
   */
  public function warm() {
  }

}
