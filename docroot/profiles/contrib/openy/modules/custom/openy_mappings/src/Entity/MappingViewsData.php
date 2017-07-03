<?php

namespace Drupal\openy_mappings\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Mapping entities.
 */
class MappingViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['mapping']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Mapping'),
      'help' => $this->t('The Mapping ID.'),
    );

    return $data;
  }

}
