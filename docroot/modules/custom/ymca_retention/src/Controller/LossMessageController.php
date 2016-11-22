<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LossMessageController.
 */
class LossMessageController extends ControllerBase {

  /**
   * Returns a json containing a random loss message to be displayed on wheel.
   */
  public function lossMessageJson(Request $request) {
    /** @var \Drupal\ymca_retention\InstantWin $instant_win */
    $instant_win = \Drupal::service('ymca_retention.instant_win');
    return new JsonResponse($instant_win->lossMessageLong());
  }

}
