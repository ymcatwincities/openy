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
  public function myy(Request $request) {
    $config = \Drupal::service('config.factory')->get('openy_myy.settings');
    return [
      '#theme' => 'openy_myy',
      '#attached' => [
        'drupalSettings' => [
          'myy' => [
            'childcare_purchase_link_title' => $config->get('childcare_purchase_link_title'),
            'childcare_purchase_link_url' => $config->get('childcare_purchase_link_url'),
          ]
        ]
      ]
    ];
  }

}
