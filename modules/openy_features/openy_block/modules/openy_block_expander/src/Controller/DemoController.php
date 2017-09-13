<?php

namespace Drupal\openy_block_expander\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An example controller.
 */
class DemoController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
//    $render = [
//      '#theme' => 'page_expander',
//      'block' => [
//      ]
//    ];

    $block = \Drupal\block_content\Entity\BlockContent::load(3);
    $render = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($block);

    return $render;
  }

}
