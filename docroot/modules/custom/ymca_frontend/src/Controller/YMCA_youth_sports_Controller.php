<?php

namespace Drupal\ymca_frontend\Controller;

/**
 * Controller for "Youth Sports" page.
 */
class YMCA_youth_sports_Controller {

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
    return t('Youth Sports');
  }

}
