<?php

namespace Drupal\openy_digital_signage_screen\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for OpenY Digital Signage Screen entities.
 */
class OpenYScreenViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['openy_digital_signage_screen']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Digital Signage Screen'),
      'help' => $this->t('The Digital Signage Screen ID.'),
    ];

    return $data;
  }

}
