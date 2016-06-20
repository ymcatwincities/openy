<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Personify MindBody Cache entities.
 *
 * @ingroup personify_mindbody_sync
 */
class PersonifyMindbodyCacheListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Personify MindBody Cache ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\personify_mindbody_sync\Entity\PersonifyMindbodyCache */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.personify_mindbody_cache.edit_form', array(
          'personify_mindbody_cache' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
