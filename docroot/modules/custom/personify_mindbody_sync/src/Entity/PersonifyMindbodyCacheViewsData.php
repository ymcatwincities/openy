<?php

namespace Drupal\personify_mindbody_sync\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Personify MindBody Cache entities.
 */
class PersonifyMindbodyCacheViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['personify_mindbody_cache']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Personify MindBody Cache'),
      'help' => $this->t('The Personify MindBody Cache ID.'),
    );

    return $data;
  }

}
