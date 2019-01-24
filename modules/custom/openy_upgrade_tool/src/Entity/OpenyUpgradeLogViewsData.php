<?php

namespace Drupal\openy_upgrade_tool\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Open Y upgrade log entities.
 */
class OpenyUpgradeLogViewsData extends EntityViewsData {

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
