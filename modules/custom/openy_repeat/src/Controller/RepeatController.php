<?php

namespace Drupal\openy_repeat\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * {@inheritdoc}
 */
class RepeatController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function dashboard() {
    return [
      '#theme' => 'openy_repeat_schedule_dashboard',
      '#current_date' => date('d Y'),
      // @todo change in future for real locations.
      '#locations' => ['New York', 'Texas', 'Boston', 'Dallas'],
    ];
  }


  public function ajaxScheduler() {


    return [];
  }

}
