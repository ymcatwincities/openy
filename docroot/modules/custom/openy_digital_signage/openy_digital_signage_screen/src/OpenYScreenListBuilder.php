<?php

namespace Drupal\openy_digital_signage_screen;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of OpenY Digital Signage Screen entities.
 *
 * @ingroup openy_digital_signage_screen
 */
class OpenYScreenListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Screen ID');
    $header['name'] = $this->t('Name');
    $header['created'] = $this->t('Create');
//    $header['class'] = $this->t('Class');
//    $header['location'] = $this->t('Location');
//    $header['from'] = $this->t('Occurence from');
//    $header['to'] = $this->t('Occurence to');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\openy_digital_signage_screen\Entity\OpenYScreen */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.openy_digital_signage_screen.edit_form', array(
          'openy_digital_signage_screen' => $entity->id(),
        )
      )
    );
    $row['created'] = $entity->getCreatedTime();
//    $session = array_values($entity->session->referencedEntities())[0];
//    $row['session'] = $this->l($session->label(), Url::fromUri('internal:/node/' . $session->id()));
//    $class = array_values($entity->class->referencedEntities())[0];
//    $row['class'] = $this->l($class->label(), Url::fromUri('internal:/node/' . $class->id()));
//    $location = array_values($entity->location->referencedEntities())[0];
//    $row['location'] = $this->l($location->label(), Url::fromUri('internal:/node/' . $location->id()));
//    $row['from'] = $entity->getTimestamp();
//    $row['to'] = $entity->getTimestampTo();
    return $row + parent::buildRow($entity);
  }

}
