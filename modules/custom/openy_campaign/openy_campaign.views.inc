<?php

/**
 * @file
 */

/**
 * Implements hook_views_data_alter().
 */
function openy_campaign_views_data_alter(array &$data) {
  $data['openy_campaign_member_campaign']['member_campaign_checkins'] = [
    'title' => t('Member\'s checkins to the facilities.'),
    'field' => [
      'title' => t('Member\'s checkins to the facilities.'),
      'help' => t('Output number of the checkins during the current campaign.'),
      'id' => 'member_campaign_checkins',
    ],
  ];
}
