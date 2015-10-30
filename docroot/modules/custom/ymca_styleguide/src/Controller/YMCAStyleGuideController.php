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
