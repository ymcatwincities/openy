<?php

namespace Drupal\openy_session_instance\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Session Instance entities.
 */
class SessionInstanceViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['session_instance']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Session Instance'),
      'help' => $this->t('The Session Instance ID.'),
    );

    return $data;
  }

}
