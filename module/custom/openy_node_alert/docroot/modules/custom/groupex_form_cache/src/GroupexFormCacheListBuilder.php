<?php

namespace Drupal\groupex_form_cache;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Groupex Form Cache entities.
 *
 * @ingroup groupex_form_cache
 */
class GroupexFormCacheListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Groupex Form Cache ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\groupex_form_cache\Entity\GroupexFormCache */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.groupex_form_cache.edit_form', array(
          'groupex_form_cache' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
