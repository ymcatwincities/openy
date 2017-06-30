<?php

namespace Drupal\openy_digital_signage_classes_schedule;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of OpenY Digital Signage Schedule entities.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesSessionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Title');
    $header['source'] = $this->t('Source');
    $header['room_name'] = $this->t('Room name');
    $header['created'] = $this->t('Created');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSession */
    $row['id'] = $entity->id();
    $row['name'] = $entity->getName();
    $row['name'] = $entity->get('room_name');
    $row['created'] = $entity->getCreatedTime();

    return $row + parent::buildRow($entity);
  }

}
