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
      'dates' => $this->getDates(),
    ];

    $response = new JsonResponse($info);

    return $response;
  }

  /**
   * Return array with all dates of campaign.
   */
  private function getDates() {
    $config = \Drupal::config('ymca_retention.general_settings');
    $current_date = new \DateTime();
    $current_date->setTime(0, 0, 0);
    $open_date = new \DateTime($config->get('date_campaign_open'));
    $close_date = new \DateTime($config->get('date_campaign_close'));

    // Calculate number of days to show.
    $date_interval = $open_date->diff($close_date);
    $days = $date_interval->days;
    if ($date_interval->h > 0 || $date_interval->i > 0) {
      $days++;
    }

    // Prepare dates data.
    $dates = [];
    $date = $open_date->setTime(0, 0, 0);
    $day_interval = new \DateInterval('P1D');
    for ($i = 0; $i < $days; $i++) {
      $timestamp = $date->getTimestamp();
      $date_diff_now = $date->diff($current_date);
      $dates[] = [
        'index' => $i,
        'label' => $date->format('l n/j'),
        'weekday' => $date->format('D'),
        'month_day' => $date->format('j'),
        'month' => $date->format('M'),
        'timestamp' => $timestamp,
        'past' => !(bool) $date_diff_now->invert,
        'future' => (bool) $date_diff_now->invert,
        'today' => $date == $current_date,
      ];

      $date = $date->add($day_interval);
    }

    return $dates;
  }

}
