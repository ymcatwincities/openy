<?php

namespace Drupal\openy_digital_signage_schedule;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of OpenY Digital Signage Schedule entities.
 *
 * @ingroup openy_digital_signage_schedule
 */
class OpenYScheduleListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Schedule ID');
    $header['name'] = $this->t('Name');
    $header['description'] = $this->t('Description');
    $header['created'] = $this->t('Created');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\openy_digital_signage_schedule\Entity\OpenYSchedule */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.openy_digital_signage_schedule.edit_form', array(
          'openy_digital_signage_schedule' => $entity->id(),
        )
      )
    );
    $description = $entity->get('description');
    $row['description'] = check_markup($description->value, $description->format);
    $row['created'] = $entity->getCreatedTime();

    return $row + parent::buildRow($entity);
  }

}
