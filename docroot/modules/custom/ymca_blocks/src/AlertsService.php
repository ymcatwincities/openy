<?php

namespace Drupal\ymca_blocks;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controls what Alert is active.
 */
class AlertsService {
  private $currentBlock;

  /**
   * Global Alert block object.
   *
   * @var BlockContent
   */
  private $globalAlert = FALSE;

  /**
   * Constructs a new AlertsService.
   */
  public function __construct() {
    $this->currentBlock = NULL;
  }

  /**
   * Sets current Alert block.
   *
   * @param BlockContent $block
   *   Alert Block content entity.
   */
  public function setCurrentAlertBlock(BlockContent $block) {
    if (!is_null($this->currentBlock)) {
      // All pages with an old alert block should get cache invalidated.
      \Drupal::service('cache_tags.invalidator')->invalidateTags(['block_content:' . $this->currentBlock->id()]);
    }
    // All pages with an alert block should get cache invalidated as well.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['block_content:' . $block->id()]);
    $this->currentBlock = $block;
  }

  /**
   * Returns current Alert Block.
   *
   * @return mixed
   *   An instance of BlockContent or null.
   */
  public function getCurrentAlertBlock() {
    return $this->currentBlock;
  }

  /**
   * Returns Global Alert block.
   *
   * @return bool|BlockContent|EntityInterface
   *   Block object or FALSE if not found.
   */
  public function getGlobalAlertBlock() {

    if ($this->globalAlert != FALSE) {
      return $this->getGlobalAlertByUser();
    }

    try {
      $query = \Drupal::entityQuery('block_content')
        ->condition('type', 'alert_block')
        ->condition('field_global_alert', 1)
        ->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('AlertsService', $e);
      return FALSE;
    }

    if (empty($query)) {
      return FALSE;
    }
    $block_id = array_shift($query);
    $this->globalAlert = \Drupal::entityManager()->getStorage('block_content')->load($block_id);
    return $this->getGlobalAlertByUser();
  }

  /**
   * Get AlertBlock dependent on cookies.
   *
   * @return bool|BlockContent
   *   FALSE if user closed Alert Block, object otherwise.
   */
  private function getGlobalAlertByUser() {

    if ($this->globalAlert != FALSE && isset($_COOKIE['alert-block-' . $this->globalAlert->id()]) && $_COOKIE['alert-block-' . $this->globalAlert->id()] == TRUE) {
      return FALSE;
    }
    return $this->globalAlert;
  }

}
