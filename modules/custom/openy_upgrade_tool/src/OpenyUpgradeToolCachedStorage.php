<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Core\Config\CachedStorage;

/**
 * Defines the Open Y cached storage.
 *
 * Contain some customization for diff form.
 * Note: this storage used only for diff logic.
 *
 * @see \Drupal\openy_upgrade_tool\Form\OpenyUpgradeLogDiff
 */
class OpenyUpgradeToolCachedStorage extends CachedStorage {

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    $data = parent::read($name);
    // Remove _core and uuid params from diff.
    unset($data['_core'], $data['uuid']);

    return $data;
  }

}
