<?php

namespace Drupal\ymca_frontend\Controller;

/**
 * Controller for "March" page.
 */
class YMCA_march_Controller {

  /**
   * Set page's content.
   */
  public function content() {
    return array(
      '#markup' => '',
    );
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return t('Up Your Game');
  }

}
