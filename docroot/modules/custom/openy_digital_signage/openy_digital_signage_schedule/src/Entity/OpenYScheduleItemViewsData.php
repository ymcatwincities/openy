<?php

namespace Drupal\openy_digital_signage_schedule\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for OpenY Digital Signage Schedule item entities.
 */
class OpenYScheduleItemViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['openy_digital_signage_sch_item']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Digital Signage Schedule item'),
      'help' => $this->t('The Digital Signage Schedule item ID.'),
    );

    return $data;
  }

}
