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
    // TODO: We need to improve this part, for now we have one reason to track
    // roles customization - in case Open Y openy_user hook_update user
    // customization can be overridden, so let's leave default logic for now.
    // Looks like the right decision is not use usual config import and update
    // existing configuration.
    return FALSE;
  }

}