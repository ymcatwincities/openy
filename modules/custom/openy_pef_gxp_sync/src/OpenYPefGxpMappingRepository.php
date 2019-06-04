<?php

namespace Drupal\openy_pef_gxp_sync;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Class OpenYPefGxpMappingRepository.
 *
 * @package Drupal\openy_pef_gxp_sync
 */
class OpenYPefGxpMappingRepository {

  /**
   * Chunk size for entity removal.
   */
  const CHUNK_DELETE = 50;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * OpenYPefGxpMappingRepository constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger channel.
   * @param \Drupal\Core\Database\Connection $db
   *   Database.
   */
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelInterface $loggerChannel, Connection $db) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannel = $loggerChannel;
    $this->db = $db;
  }

  /**
   * Remove mapping entities by location & class.
   *
   * Also removes referenced classes.
   *
   * @param int $locationId
   *   Location ID.
   * @param int $classId
   *   Class ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeByLocationIdAndClassId($locationId, $classId) {
    $query = $this->db->select('openy_pef_gxp_mapping', 'm')
      ->condition('location_id', $locationId)
      ->condition('product_id', $classId)
      ->fields('m', ['id']);

    $ids = $query->execute()->fetchCol();
    if (!$ids) {
      return;
    }

    $this->removeByChunk($ids);
  }

  /**
   * Remove all mappings.
   *
   * Also removes referenced sessions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeAll() {
    $storage = $this->entityTypeManager->getStorage('openy_pef_gxp_mapping');

    $query = $storage->getQuery('openy_pef_gxp_mapping');
    $ids = $query->execute();
    if (!$ids) {
      return;
    }

    $this->removeByChunk($ids);
  }

  /**
   * Remove entities by ID.
   *
   * @param array $ids
   *   Entity IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function removeByChunk(array $ids) {
    $storage = $this->entityTypeManager->getStorage('openy_pef_gxp_mapping');
    $chunks = array_chunk($ids, self::CHUNK_DELETE);

    foreach ($chunks as $chunk) {
      $entities = $storage->loadMultiple($chunk);
      $storage->delete($entities);
      $this->loggerChannel->debug(
        'Chunk of %chunk openy_pef_gxp_mapping entities has been deleted.',
        ['%chunk' => count($chunk)]
      );
    }
  }

}
