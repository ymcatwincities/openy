<?php

namespace Drupal\panelizer\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctools\Access\AccessInterface;

/**
 *
 */
class PanelizerUIAccess implements AccessInterface {

  public function access(AccountInterface $account) {
    return $account->hasPermission('administer panelizer') ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
