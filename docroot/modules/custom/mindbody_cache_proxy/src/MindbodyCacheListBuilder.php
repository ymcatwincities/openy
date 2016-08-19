<?php

namespace Drupal\mindbody_cache_proxy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of MindBody Cache entities.
 *
 * @ingroup mindbody_cache_proxy
 */
class MindbodyCacheListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('MindBody Cache ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\mindbody_cache_proxy\Entity\MindbodyCache */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.mindbody_cache.edit_form', array(
          'mindbody_cache' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
