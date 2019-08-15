<?php

namespace Drupal\openy_myy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class MyYController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function getData(Request $request) {
    return [
      '#theme' => 'openy_myy',
    ];
  }

}
