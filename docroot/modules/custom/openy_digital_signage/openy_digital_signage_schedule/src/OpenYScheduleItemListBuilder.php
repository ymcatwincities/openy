<?php

namespace Drupal\openy_digital_signage_schedule;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of OpenY Digital Signage Schedule item entities.
 *
 * @ingroup openy_digital_signage_schedule
 */
class OpenYScheduleItemListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Schedule Item ID');
    $header['name'] = $this->t('Name');
    $header['schedule'] = $this->t('Schedule');
    $header['time_slot'] = $this->t('Time Slot');
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
        'entity.openy_digital_signage_sch_item.edit_form', array(
          'openy_digital_signage_sch_item' => $entity->id(),
        )
      )
    );

    $row['schedule'] = '';
    if ($schedule = $entity->schedule->entity) {
      $row['schedule'] = $this->l(
        $schedule->getName(),
        new Url(
          'entity.openy_digital_signage_schedule.edit_form', array(
            'openy_digital_signage_schedule' => $schedule->id(),
          )
        )
      );
    }
    // TODO: fix tz handling.
    $from_ts = strtotime($entity->get('time_slot')->value . 'z');
    $to_ts = strtotime($entity->get('time_slot')->end_value . 'z');
    $row['time_slot'] = date('h:ia', $from_ts) . ' – ' . date('h:ia', $to_ts);
    $row['created'] = date('m/d/Y h:ia', $entity->getCreatedTime());

    return $row + parent::buildRow($entity);
  }

}
