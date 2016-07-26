<?php

namespace Drupal\groupex_form_cache;

use Drupal\Core\Entity\Query\QueryFactory;
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
   * Query factory.
   *
   * @var QueryFactory
   */
  protected $queryFactory;

  /**
   * GroupexFormCacheManager constructor.
   *
   * @param QueryFactory $query_factory
   *   Query factory.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
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

}
