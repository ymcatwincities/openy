<?php

namespace Drupal\openy_user\Plugin\ConfigEventIgnore;

use Drupal\openy_upgrade_tool\ConfigEventIgnoreBase;

/**
 * Provides config event ignore rules for role config type.
 *
 * @ConfigEventIgnore(
 *   id="roles_ignore",
 *   label = @Translation("Roles"),
 *   type="user_role",
 *   weight=0
 * )
 */
class Role extends ConfigEventIgnoreBase {

  /**
   * {@inheritdoc}
   */
  public function fullIgnore() {
    // No need to track roles config customizations, on each website
    // they can be different.
    return TRUE;
  }

}