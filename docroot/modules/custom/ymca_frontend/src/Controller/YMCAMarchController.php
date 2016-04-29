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
    \Drupal::service('page_cache_kill_switch')->trigger();
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
