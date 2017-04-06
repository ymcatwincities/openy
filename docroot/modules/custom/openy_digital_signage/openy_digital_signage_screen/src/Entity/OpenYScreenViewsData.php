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

    $data['openy_digital_signage_screen']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('OpenY Digital Signage Screen'),
      'help' => $this->t('The OpenY Digital Signage Screen ID.'),
    );

    return $data;
  }

}
