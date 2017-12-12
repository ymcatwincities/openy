<?php

namespace Drupal\ymca_cdn_product;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\ymca_cdn_sync\SyncException;

/**
 * Class CdnPrsProductRepository.
 *
 * @package Drupal\ymca_cdn_product
 */
class CdnPrsProductRepository implements CdnPrsProductRepositoryInterface {

  use CdnPrsRepositoryTrait;

  /**
   * Query Factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * CdnPrsProductRepository constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   Query Factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(QueryFactory $queryFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->queryFactory = $queryFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->storage = $this->entityTypeManager->getStorage('cdn_prs_product');
  }

  /**
   * {@inheritdoc}
   */
  public function removeAll() {
    $ids = $this->queryFactory
      ->get('cdn_prs_product')
      ->execute();

    if (!$ids) {
      return;
    }

    $this->removeAllByChunks($this->storage, $ids, 25);
  }

  /**
   * {@inheritdoc}
   */
  public function getProductByPersonifyProductId($id) {
    $ids = $this->queryFactory
      ->get('cdn_prs_product')
      ->condition('field_cdn_prd_id', $id)
      ->execute();

    if (!$ids) {
      return FALSE;
    }

    $id = key($ids);
    return $this->storage->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity(\SimpleXMLElement $xmlProduct) {
    $product = json_decode(json_encode($xmlProduct), 1);

    // Check whether the date is valid.
    try {
      $dateTimeZone = new \DateTimeZone('UTC');
      /** @var \DateTime $dateTime */
      $dateTime = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $product['BeginDate'], $dateTimeZone);
    }
    catch (\Exception $e) {
      throw new SyncException(sprintf("Got invalid date format: %s.", $product['BeginDate']));
    }

    $entity = $this->storage->create();
    $entity->setName(Unicode::truncate(trim($product['ShortName']), 255));

    $popsToSave = [
      'field_cdn_prd_hash' => $this->getProductHash($xmlProduct),
      'field_cdn_prd_code' => $product['ProductCode'],
      'field_cdn_prd_id' => $product['ProductId'],
      'field_cdn_prd_object' => serialize($product),
      'field_cdn_prd_start_date' => $dateTime->format(DATETIME_DATETIME_STORAGE_FORMAT),
      'field_cdn_prd_cabin_id' => substr($product['ProductCode'], 6, 4),
      'field_cdn_prd_capacity' => abs(substr($product['ProductCode'], 11, 2)),
      'field_cdn_prd_capacity_left' => $product['Capacity'],
      'field_cdn_prd_cabin_number' => abs(substr($product['ProductCode'], 7, 4)),
      'field_cdn_prd_list_price' => $product['ListPrice'],
      'field_cdn_prd_regs' => $product['Registrations'],
    ];

    foreach ($popsToSave as $fieldName => $fieldValue) {
      $entity->set($fieldName, $fieldValue);
    }

    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductHash(\SimpleXMLElement $xmlProduct) {
    $product = json_decode(json_encode($xmlProduct), 1);
    return md5(serialize($product));
  }

  /**
   * {@inheritdoc}
   */
  public function removeProduct($id) {
    $this->storage->delete([$id]);
  }

}
