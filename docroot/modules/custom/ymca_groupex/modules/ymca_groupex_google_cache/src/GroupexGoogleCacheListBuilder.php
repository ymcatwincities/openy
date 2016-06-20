<?php

namespace Drupal\ymca_groupex_google_cache;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Groupex Google Cache entities.
 *
 * @ingroup ymca_groupex_google_cache
 */
class GroupexGoogleCacheListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Groupex Google Cache ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ymca_groupex_google_cache\Entity\GroupexGoogleCache */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.groupex_google_cache.edit_form', array(
          'groupex_google_cache' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
