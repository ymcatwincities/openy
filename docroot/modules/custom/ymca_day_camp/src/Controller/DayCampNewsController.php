<?php

namespace Drupal\ymca_day_camp\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DayCampNewsController.
 */
class DayCampNewsController extends ControllerBase {

  /**
   * Generate page content.
   *
   * @return array
   *   Return render array.
   */
  public function pageView() {
    $view = views_embed_view('day_camp_news', 'main');

    return [
      'view' => $view,
    ];
  }

}
