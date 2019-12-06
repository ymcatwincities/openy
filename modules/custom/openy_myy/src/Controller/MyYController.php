<?php

namespace Drupal\openy_myy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    if (!$personifyId = \Drupal::service('myy_personify_user_helper')->personifyGetId()) {
      // Redirect to login page.
      return new RedirectResponse(Url::fromRoute('openy_myy.login')->toString());
    }
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
