<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Access\PageManagerPluginAccess.
 */

namespace Drupal\page_manager_ui\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctools\Access\AccessInterface;

class PageManagerPluginAccess implements AccessInterface {

  public function access(AccountInterface $account) {
    return $account->hasPermission('administer pages') ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
