<?php

namespace Drupal\ymca_frontend\Controller;

/**
 * Controller for "2014 Annual Report" page.
 */
class YMCA2014AnnualReportController {

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
    return t('2014 Annual Report');
  }

}
