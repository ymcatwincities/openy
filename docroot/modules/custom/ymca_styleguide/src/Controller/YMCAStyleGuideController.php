<?php

/**
 * @file
 * Contains \Drupal\ymca_styleguide\Controller\YMCAStyleGuideController.
 */

namespace Drupal\ymca_styleguide\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for styleguide routes.
 */
class YMCAStyleGuideController extends ControllerBase {

  /**
   * Displays a styleguide.
   */
  public function overview() {
    // Setup alert block.
    $query = \Drupal::entityQuery('block_content')
      ->condition('type', 'alert_block')
      ->range(0, 1);
    if ($ids = $query->execute()) {
      $block = \Drupal::entityTypeManager()->getStorage('block_content')->load(reset($ids));
      \Drupal::service('alerts.service')->setCurrentAlertBlock($block);
    }
    else {
      drupal_set_message(t('To enable alert block on the styleguide page create any of it'));
    }

    $build = [
      '#markup' => file_get_contents(__DIR__ . '/../../templates/overview.html'),
      '#attached' => [
        'library' => [
          'ymca_styleguide/ymca_styleguide',
          'ymca_styleguide/lightbox2',
        ],
      ],
    ];
    return $build;
  }

}
