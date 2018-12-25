<?php

namespace Drupal\openy_digital_signage_room;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Digital Signage Room entities.
 *
 * @ingroup openy_digital_signage_room
 */
class OpenYRoomListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['name'] = $this->t('Title');
    $header['created'] = $this->t('Created');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    /* @var $entity \Drupal\openy_digital_signage_room\Entity\OpenYRoom */
    $row['name'] = $entity->getName();
    $row['created'] = $entity->getCreatedTime();

    return $row + parent::buildRow($entity);
  }

}
