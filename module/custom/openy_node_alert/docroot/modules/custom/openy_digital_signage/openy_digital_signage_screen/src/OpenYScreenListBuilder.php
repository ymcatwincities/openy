<?php

namespace Drupal\openy_digital_signage_screen;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of OpenY Digital Signage Screen entities.
 *
 * @ingroup openy_digital_signage_screen
 */
class OpenYScreenListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Screen ID');
    $header['name'] = $this->t('Name');
    $header['machine_name'] = $this->t('Machine name');
    $header['created'] = $this->t('Created');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\openy_digital_signage_screen\Entity\OpenYScreen */
    $row['id'] = $entity->id();
    $row['name'] = new Link($entity->label(), new Url(
      'entity.openy_digital_signage_screen.canonical', array(
        'openy_digital_signage_screen' => $entity->id(),
      )
    ));
    $row['machine_name'] = $entity->get('machine_name')->value;
    $row['created'] = $entity->getCreatedTime();

    return $row + parent::buildRow($entity);
  }

}
