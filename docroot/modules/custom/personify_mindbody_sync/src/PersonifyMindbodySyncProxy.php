<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\personify_mindbody_sync\Entity\PersonifyMindbodyCache;

/**
 * Class PersonifyMindbodySyncProxy.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncProxy implements PersonifyMindbodySyncProxyInterface {

  /**
   * PersonifyMindbodySyncWrapper definition.
   *
   * @var PersonifyMindbodySyncWrapper
   */
  protected $wrapper;

  /**
   * Logger channel.
   *
   * @var LoggerChannel
   */
  protected $logger;

  /**
   * Query factory.
   *
   * @var QueryFactory
   */
  protected $query;

  /**
   * Entity type manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * PersonifyMindbodySyncProxy constructor.
   *
   * @param PersonifyMindbodySyncWrapper $wrapper
   *   Wrapper.
   * @param LoggerChannelFactory $logger_factory
   *   Logger factory.
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param EntityTypeManager $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(PersonifyMindbodySyncWrapper $wrapper, LoggerChannelFactory $logger_factory, QueryFactory $query_factory, EntityTypeManager $entity_type_manager) {
    $this->wrapper = $wrapper;
    $this->logger = $logger_factory->get(PersonifyMindbodySyncWrapper::CHANNEL);
    $this->query = $query_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function saveEntities() {
    foreach ($this->wrapper->getSourceData() as $item) {
      // Check whether the entity exists.
      $existing = $this->findOrder($item->OrderNo, $item->OrderLineNo);

      if (!$existing) {
        $id = '$id';
        $cache_item = PersonifyMindbodyCache::create([
          'field_pmc_data' => serialize($item),
          'field_pmc_order_num' => $item->OrderNo,
          'field_pmc_order_line_num' => $item->OrderLineNo,
          'field_user_id' => $item->{$id},
        ]);
        $cache_item->setName($item->OrderNo . ' (' . $item->OrderLineNo . ')');
        $cache_item->save();
      }
    }
  }

  /**
   * Clear cached entities.
   */
  public function clearCache() {
    $ids = $this->query->get(PersonifyMindbodySyncWrapper::CACHE_ENTITY)->execute();
    $chunks = array_chunk($ids, 10);
    $storage = $this->entityTypeManager->getStorage(PersonifyMindbodySyncWrapper::CACHE_ENTITY);
    foreach ($chunks as $chunk) {
      $cache = PersonifyMindbodyCache::loadMultiple($chunk);
      $storage->delete($cache);
    }
  }

  /**
   * Find Order by order number.
   *
   * The unique id of an order in Personify is the order number + the line number.
   *
   * @param string $order_num
   *   Order number.
   * @param string $order_line_num
   *   Order line number.
   *
   * @return bool|EntityInterface
   *   FALSE or order entity.
   */
  protected function findOrder($order_num, $order_line_num) {
    $result = $this->query->get(PersonifyMindbodySyncWrapper::CACHE_ENTITY)
      ->condition('field_pmc_order_num', $order_num)
      ->condition('field_pmc_order_line_num', $order_line_num)
      ->execute();

    if (!empty($result)) {
      $id = reset($result);
      return PersonifyMindbodyCache::load($id);
    }

    return FALSE;
  }

}
