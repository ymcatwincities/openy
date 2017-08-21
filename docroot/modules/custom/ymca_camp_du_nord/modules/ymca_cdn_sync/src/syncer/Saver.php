<?php

namespace Drupal\ymca_cdn_sync\syncer;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ymca_cdn_product\CdnPrsProductRepositoryInterface;

/**
 * Class Saver.
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
class Saver implements SaverInterface {

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Wrapper.
   *
   * @var \Drupal\ymca_cdn_sync\syncer\WrapperInterface
   */
  protected $wrapper;

  /**
   * Repository.
   *
   * @var \Drupal\ymca_cdn_product\CdnPrsProductRepositoryInterface
   */
  protected $repository;

  /**
   * Saver constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger.
   * @param \Drupal\ymca_cdn_sync\syncer\WrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\ymca_cdn_product\CdnPrsProductRepositoryInterface $repository
   *   Product repository.
   */
  public function __construct(LoggerChannelInterface $loggerChannel, WrapperInterface $wrapper, CdnPrsProductRepositoryInterface $repository) {
    $this->logger = $loggerChannel;
    $this->wrapper = $wrapper;
    $this->repository = $repository;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    foreach ($this->wrapper->getSourceData() as $product) {

      // Check whether we have the product in DB.
      $personifyProductId = (string) $product->ProductID;
      $existing = $this->repository->getProductByPersonifyProductId($personifyProductId);
      if ($existing) {
        $hash_existing = $existing->field_cdn_prd_hash->value;
        $hash_current = $this->repository->getProductHash($product);

        // Here is no difference. Just skip this item.
        if (strcmp($hash_current, $hash_existing) === 0) {
          continue;
        }

        // Some differences have been found. Let's remove existing entity and create new one.
        $this->repository->removeProduct($existing->id());
      }

      try {
        $this->repository->createEntity($product);
      }
      catch (\Exception $e) {
        $this->logger->error('Failed to create an Entity with the message: %message', ['%message' => $e->getMessage()]);
      }
    }
  }

}
