<?php

namespace Drupal\logger_entity\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Logger Entity entities.
 */
class LoggerEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['logger_entity']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Logger Entity'),
      'help' => $this->t('The Logger Entity ID.'),
    );

    $data['logger_entity']['data'] = array(
      'title' => t('Data'),
      'help' => t('The data in a serialized format.'),
      'field' => array(
        'id' => 'serialized',
        'click sortable' => FALSE,
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'filter' => array(
        'id' => 'string',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );

    return $data;
  }

}
