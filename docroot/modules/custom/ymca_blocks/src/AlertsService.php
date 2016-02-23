<?php

namespace Drupal\ymca_blocks;

/**
 * Controls what Alert is active.
 */
class AlertsService {
  private $currentBlock;

  /**
   * Global Alert block object.
   *
   * @var \Drupal\block_content\Entity\BlockContent
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
   * @param \Drupal\block_content\Entity\BlockContent $block
   *   Alert Block content entity.
   */
  public function setCurrentAlertBlock(\Drupal\block_content\Entity\BlockContent $block) {
    // All pages with an alert block should get cache invalidated.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['block_content:' . $block->id()]);
    $this->currentBlock = $block;
  }

  /**
   * Returns current Alert Block.
   *
   * @return mixed
   *   An instance of \Drupal\block_content\Entity\BlockContent or null.
   */
  public function getCurrentAlertBlock() {
    return $this->currentBlock;
  }

  /**
   * Returns Global Alert block.
   *
   * @return bool|\Drupal\block_content\Entity\BlockContent|\Drupal\Core\Entity\EntityInterface
   *   Block object or FALSE if not found.
   */
  public function getGlobalAlertBlock() {

    if ($this->globalAlert != FALSE) {
      return $this->getGlobalAlertByUser();
    }
    $query = \Drupal::entityQuery('block_content')
      ->condition('type', 'alert_block')
      ->condition('field_set_as_global_alert', 1)
      ->execute();
    if (empty($query)) {
      return FALSE;
    }
    $block_id = array_shift($query);
    $this->globalAlert = \Drupal::entityManager()->getStorage('block_content')->load($block_id);
    return $this->getGlobalAlertByUser();
  }

  /**
   * @return bool|\Drupal\block_content\Entity\BlockContent
   *   FALSE if user closed Alert Block, object otherwise.
   */
  private function getGlobalAlertByUser() {

    if ($this->globalAlert != FALSE && isset($_COOKIE['alert-block-' . $this->globalAlert->id()]) && $_COOKIE['alert-block-' . $this->globalAlert->id()] == TRUE) {
      return FALSE;
    }
    return $this->globalAlert;
  }

}
