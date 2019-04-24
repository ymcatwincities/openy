<?php

namespace Drupal\groupex_form_cache\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for GroupEx Pro Form Cache entities.
 */
class GroupexFormCacheViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['groupex_form_cache']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('GroupEx Pro Form Cache'),
      'help' => $this->t('The GroupEx Pro Form Cache ID.'),
    ];

    return $data;
  }

}
