<?php

namespace Drupal\openy_activity_finder;

use Drupal\views\EntityViewsData;

class ProgramSearchLogViewsData extends EntityViewsData {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['program_search_log']['details_counter'] = [
      'title' => t('Availability checks'),
      'help' => t('How many times visitors clicked to check availability'),
      'field' => [
        'id' => 'program_search_log_details_counter',
      ],
    ];
    $data['program_search_log']['register_counter'] = [
      'title' => t('Register click'),
      'help' => t('How many times visitors hit Register'),
      'field' => [
        'id' => 'program_search_log_register_counter',
      ],
    ];
    return $data;
  }
}