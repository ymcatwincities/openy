<?php

namespace Drupal\ymca_frontend\Controller;

/**
 * Controller for "March" page.
 */
class YMCAMarchController {

  /**
   * Set page's content.
   */
  public function content() {
    return [
      '#markup' => '',
    ];
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return t('Up Your Game');
  }

}
