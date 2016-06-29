<?php

namespace Drupal\ymca_groupex_google_cache\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Groupex Google Cache entities.
 */
class GroupexGoogleCacheViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['groupex_google_cache']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Groupex Google Cache'),
      'help' => $this->t('The Groupex Google Cache ID.'),
    );

    return $data;
  }

}
