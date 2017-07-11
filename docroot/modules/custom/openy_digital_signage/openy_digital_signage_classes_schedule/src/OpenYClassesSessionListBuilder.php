<?php

namespace Drupal\openy_digital_signage_classes_schedule;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSession;

/**
 * Defines a class to build a listing of Digital Signage Classes Session entities.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesSessionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['name'] = $this->t('Title');
    $header['room_name'] = $this->t('Room name');
    $header['created'] = $this->t('Created');
    $header['source'] = $this->t('Source');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    /* @var $entity \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSession */
    $row['name'] = $entity->getName();
    $row['room_name'] = $entity->get('room_name')->value;
    $row['created'] = $entity->getCreatedTime();
    $sources = OpenYClassesSession::getSourceValues();
    $row['source'] = $sources[$entity->get('source')->value];

    return $row + parent::buildRow($entity);
  }

}
