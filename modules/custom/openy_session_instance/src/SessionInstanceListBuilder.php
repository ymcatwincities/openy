<?php

namespace Drupal\openy_session_instance;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Session Instance entities.
 *
 * @ingroup openy_session_instance
 */
class SessionInstanceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Session Instance ID');
    $header['name'] = $this->t('Name');
    $header['session'] = $this->t('Session');
    $header['class'] = $this->t('Class');
    $header['location'] = $this->t('Location');
    $header['from'] = $this->t('Occurrence from');
    $header['to'] = $this->t('Occurrence to');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\openy_session_instance\Entity\SessionInstance */
    $row['id'] = $entity->id();
    $row['name'] = Link::fromTextAndUrl(
      $entity->label(),
      new Url(
        'entity.session_instance.edit_form', [
          'session_instance' => $entity->id(),
        ]
      )
    );
    $session = array_values($entity->session->referencedEntities())[0];
    $row['session'] = Link::fromTextAndUrl(
      $session->label(),
      Url::fromUri('internal:/node/' . $session->id())
    );
    $class = array_values($entity->class->referencedEntities())[0];
    $row['class'] = Link::fromTextAndUrl(
      $class->label(),
      Url::fromUri('internal:/node/' . $class->id())
    );
    $location = array_values($entity->location->referencedEntities())[0];
    $row['location'] = Link::fromTextAndUrl(
      $location->label(),
      Url::fromUri('internal:/node/' . $location->id())
    );
    $row['from'] = $entity->getTimestamp();
    $row['to'] = $entity->getTimestampTo();
    return $row + parent::buildRow($entity);
  }

}
