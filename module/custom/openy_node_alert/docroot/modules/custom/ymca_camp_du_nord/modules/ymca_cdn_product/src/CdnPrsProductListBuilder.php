<?php

namespace Drupal\ymca_cdn_product;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Camp du Nord Personify Product entities.
 *
 * @ingroup ymca_cdn_product
 */
class CdnPrsProductListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Camp du Nord Personify Product ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ymca_cdn_product\Entity\CdnPrsProduct */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.cdn_prs_product.edit_form',
      ['cdn_prs_product' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
