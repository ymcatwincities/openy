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
    $build = array(
      '#type' => 'markup',
      '#markup' => t('Hello Expander World!'),
    );
    return $build;
  }

}
