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
    $time_slot_value = $entity->get('time_slot')->first();
    // TODO: fix tz handling.
    $row['time_slot'] = $time_slot_value->get('value')->getDateTime()->format('h:ia');
    $row['time_slot'] .= ' – ';
    $row['time_slot'] .= $time_slot_value->get('end_value')->getDateTime()->format('h:ia');
    $row['created'] = $entity->get('created')->first()->get('value')->getDateTime()->format('m/d/Y h:ia');

    return $row + parent::buildRow($entity);
  }

}
