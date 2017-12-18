<?php

namespace Drupal\openy_digital_signage_room\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Digital Signage Room entities.
 *
 * @ingroup openy_digital_signage_room
 */
class OpenYRoomViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['openy_ds_room']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Digital Signage Room'),
      'help' => $this->t('Digital Signage Room ID.'),
    ];

    return $data;
  }

}
