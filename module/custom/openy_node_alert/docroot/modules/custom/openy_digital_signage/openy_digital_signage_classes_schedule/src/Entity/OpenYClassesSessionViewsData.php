<?php

namespace Drupal\openy_digital_signage_classes_schedule\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Digital Signage Classes Session entities.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesSessionViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['openy_ds_classes_session']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Digital Signage Classes Session'),
      'help' => $this->t('Digital Signage Classes Session ID.'),
    ];

    return $data;
  }

}
