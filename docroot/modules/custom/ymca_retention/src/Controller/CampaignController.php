<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\image\Entity\ImageStyle;
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
      'bonuses_settings' => $this->getBonusesSettings(),
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

  /**
   * Return array with bonuses and articles for all dates of campaign.
   */
  private function getBonusesSettings() {
    $bonuses_settings = [];
    $config = $this->config('ymca_retention.bonus_codes_settings');
    $general_config = $this->config('ymca_retention.general_settings');

    $date = (new \DateTime($general_config->get('date_campaign_open')))->setTime(0, 0);
    $date_end = (new \DateTime($general_config->get('date_campaign_close')))->setTime(0, 0);

    $bonus_codes = $config->get('bonus_codes');

    $delta = 0;
    $day_interval = new \DateInterval('P1D');
    while ($date <= $date_end) {
      $timestamp = $date->getTimestamp();
      $bonuses_settings[$timestamp] = [];

      if (isset($bonus_codes[$delta])) {
        $nid = $bonus_codes[$delta]['reference'];
        $title = '';
        $image_url = '';
        if (!empty($nid)) {
          $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
          $images = $node->get('field_image')->getValue();
          if (!empty($images[0]['target_id'])) {
            $file = \Drupal::entityTypeManager()->getStorage('file')->load($images[0]['target_id']);
            $image_url = ImageStyle::load('2017_ymca_retention')->buildUrl($file->getFileUri());
          }
          $title = $node->getTitle();
        }

        $bonuses_settings[$timestamp] = [
          'bonus_code' => $bonus_codes[$delta]['code'],
          'title' => $title,
          'image' => $image_url,
          'tip' => $delta + 1,
        ];
      }

      $delta++;
      $date = $date->add($day_interval);
    }

    return $bonuses_settings;
  }

}
