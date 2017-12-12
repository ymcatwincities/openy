<?php

namespace Drupal\ymca_cdn_product\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Camp du Nord Personify Product entities.
 */
class CdnPrsProductViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
