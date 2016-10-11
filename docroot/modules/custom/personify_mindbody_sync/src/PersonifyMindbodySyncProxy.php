<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
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
   * The logger channel.
   *
   * @var LoggerChannelInterface
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
   * @param LoggerChannelInterface $logger
   *   The logger channel.
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param EntityTypeManager $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(PersonifyMindbodySyncWrapper $wrapper, LoggerChannelInterface $logger, QueryFactory $query_factory, EntityTypeManager $entity_type_manager) {
    $this->wrapper = $wrapper;
    $this->logger = $logger;
    $this->query = $query_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function saveEntities() {
    $this->logger->info('The Proxy has been started.');

    $proxy_data = [];
    $new = 0;

    foreach ($this->wrapper->getSourceData() as $item) {
      // Check whether the entity exists.
      $existing = $this->wrapper->findOrder($item->OrderNo, $item->OrderLineNo);

      if (!$existing) {
        $id = 'MasterCustomerId';
        $cache_item = PersonifyMindbodyCache::create([
          'field_pmc_prs_data' => serialize($item),
          'field_pmc_order_num' => $item->OrderNo,
          'field_pmc_ord_l_num' => $item->OrderLineNo,
          'field_pmc_user_id' => $item->{$id},
          'field_pmc_ord_date' => $this->wrapper->personifyDateToTimestamp(trim($item->OrderDate)),
          'field_pmc_status' => 'No attempt. Please, look in the system log.',
        ]);

        // Save cancelled status if the order is cancelled.
        if ($item->LineStatusCode == 'C') {
          $cache_item->set('field_pmc_cancelled', TRUE);
        }

        $cache_item->setName($item->OrderNo . ' (' . $item->OrderLineNo . ')');
        $cache_item->save();
        $proxy_data[$cache_item->id()] = $cache_item;
        $new++;
      }
      else {
        $proxy_data[$existing->id()] = $existing;
      }
    }

    $this->wrapper->setProxyData($proxy_data);

    $msg = 'The Proxy saved to database %num_saved cache items.';
    $this->logger->info(
      $msg,
      [
        '%num_saved' => $new,
      ]
    );

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

}
