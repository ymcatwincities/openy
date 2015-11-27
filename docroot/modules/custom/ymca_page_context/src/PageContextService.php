<?php

/**
 * @file
 * Contains \Drupal\ymca_blocks\AlertsService.
 */

namespace Drupal\ymca_page_context;

/**
 * Controls in what context page header should be rendered.
 */
class PageContextService {
  private $context;

  /**
   * Constructs a new AlertsService.
   */
  public function __construct() {
    $this->context = NULL;
  }

  /**
   * Sets current Alert block.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Context node entity.
   */
  public function setContext(\Drupal\node\Entity\Node $node) {
    $this->context = $node;
  }

  /**
   * Returns current Alert Block.
   *
   * @return mixed
   *   An instance of \Drupal\block_content\Entity\BlockContent or null.
   */
  public function getContext() {
    return $this->context;
  }

}
