<?php

namespace Drupal\ymca_styleguide\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for styleguide routes.
 */
class YMCAStyleGuideController extends ControllerBase {

  /**
   * Displays a styleguide.
   */
  public function overview($type = '') {
    $this->setupAlertBlock();
    $this->setupPageVariant($type);

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

  /**
   * Sets up Alert Block.
   */
  private function setupAlertBlock() {
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
  }

  /**
   * Sets up Page heading variant.
   *
   * @param string $type
   *   - Context in which styleguide should be rendered (null, page, location,
   *   camp).
   */
  private function setupPageVariant($type) {
    if (!in_array($type, array('location', 'camp'))) {
      return;
    }
    $query = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->range(0, 1);
    if ($ids = $query->execute()) {
      $site_section = \Drupal::entityTypeManager()->getStorage('node')->load(reset($ids));
      \Drupal::service('pagecontext.service')->setContext($site_section);
    }
    else {
      drupal_set_message(t('There should be at lease one node of type @type to setup header properly', array('@type' => $type)));
    }
  }

}
