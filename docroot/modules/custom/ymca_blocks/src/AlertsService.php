<?php

/**
 * @file
 * Contains \Drupal\ymca_blocks\AlertsService.
 */

namespace Drupal\ymca_blocks;

/**
 * Controls what Alert is active.
 */
class AlertsService {
  private $currentBlock;

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

}
