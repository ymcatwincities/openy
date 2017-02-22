<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class CampaignController.
 */
class CampaignController extends ControllerBase {

  /**
   * Return campaign info.
   */
  public function campaignJson() {
    $config = \Drupal::config('ymca_retention.general_settings');
    $current_date = new \DateTime();
    $open_date = new \DateTime($config->get('date_campaign_open'));
    $diff = $current_date->diff($open_date);

    $info = [
      'started' => $diff->invert,
      'days_left' => $diff->days + 1,
    ];

    $response = new JsonResponse($info);

    return $response;
  }

}
