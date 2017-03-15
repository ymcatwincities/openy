<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Spring2017CampaignController.
 */
class Spring2017CampaignController extends ControllerBase {

  /**
   * Return Spring 2017 campaign info.
   */
  public function campaignJson() {
    $settings = $this->getBonusesSettings();

    $info = [
      'bonuses_settings' => $settings['bonuses'],
      'today_insights' => $settings['insights'],
    ];

    $response = new JsonResponse($info);

    return $response;
  }

  /**
   * Return array with bonuses and articles for all dates of campaign.
   */
  private function getBonusesSettings() {
    $bonuses = [];
    $insights = [];
    $config = $this->config('ymca_retention.bonus_codes_settings');
    $general_config = $this->config('ymca_retention.general_settings');

    $date = (new \DateTime($general_config->get('date_campaign_open')))->setTime(0, 0);
    $date_end = (new \DateTime($general_config->get('date_campaign_close')))->setTime(0, 0);
    $current_date = new \DateTime();
    $current_date->setTime(0, 0, 0);

    if ($current_date < $date_end) {
      $date_end = $current_date;
    }

    $bonus_codes = $config->get('bonus_codes');

    $delta = 0;
    $day_interval = new \DateInterval('P1D');
    while ($date <= $date_end) {
      $timestamp = $date->getTimestamp();
      $bonuses[$timestamp] = [];

      if (isset($bonus_codes[$delta])) {
        $nid = $bonus_codes[$delta]['reference'];
        $title = '';
        $content = '';
        $bonus_image_url = '';
        $insight_image_url = '';
        if (!empty($nid)) {
          $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
          $images = $node->get('field_image')->getValue();
          if (!empty($images[0]['target_id'])) {
            $file = \Drupal::entityTypeManager()->getStorage('file')->load($images[0]['target_id']);
            $bonus_image_url = ImageStyle::load('2017_ymca_retention')->buildUrl($file->getFileUri());
            $insight_image_url = ImageStyle::load('2017_ymca_retention_big')->buildUrl($file->getFileUri());
          }
          $title = $node->getTitle();
          $field_content = $node->get('field_content')->getValue();
          if (isset($field_content[0]['value'])) {
            $content = $field_content[0]['value'];
          }
        }

        $bonuses[$timestamp] = [
          'bonus_code' => $bonus_codes[$delta]['code'],
          'title' => $title,
          'image' => $bonus_image_url,
          'tip' => $delta + 1,
        ];
        $insights[$timestamp] = [
          'title' => $title,
          'content' => $content,
          'image' => $insight_image_url,
          'video' => $bonus_codes[$delta]['video'],
          'tip' => $delta + 1,
        ];
      }

      $delta++;
      $date = $date->add($day_interval);
    }

    $settings = [
      'bonuses' => $bonuses,
      'insights' => $insights,
    ];

    return $settings;
  }

}
