<?php

namespace Drupal\groupex_form_cache\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Groupex Form Cache entities.
 */
class GroupexFormCacheViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['groupex_form_cache']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Groupex Form Cache'),
      'help' => $this->t('The Groupex Form Cache ID.'),
    );

    return $data;
  }

}
