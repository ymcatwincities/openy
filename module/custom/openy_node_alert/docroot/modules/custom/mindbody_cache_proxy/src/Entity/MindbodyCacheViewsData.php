<?php

namespace Drupal\mindbody_cache_proxy\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for MindBody Cache entities.
 */
class MindbodyCacheViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['mindbody_cache']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('MindBody Cache'),
      'help' => $this->t('The MindBody Cache ID.'),
    );

    return $data;
  }

}
