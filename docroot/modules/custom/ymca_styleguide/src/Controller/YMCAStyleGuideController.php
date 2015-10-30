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
    $base_tag = [
      '#type' => 'html_tag',
      '#tag' => 'base',
      '#attributes' => [
        'href' => $GLOBALS['base_url'] . $GLOBALS['base_path'],
      ],
    ];

    $build = [
      '#markup' => file_get_contents(__DIR__ . '/../../templates/overview.html'),
      '#attached' => [
        'library' => [
          'ymca_styleguide/ymca_styleguide',
          'ymca_styleguide/lightbox2',
        ],
        'html_head' => [[$base_tag, 'base_tag']],
      ],
    ];
    return $build;
  }

}
