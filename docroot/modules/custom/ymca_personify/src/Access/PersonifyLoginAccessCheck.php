<?php

namespace Drupal\ymca_personify\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Class PersonifyLoginAccessCheck.
 */
class PersonifyLoginAccessCheck implements AccessInterface {

  /**
   * Check access.
   */
  public function access() {
    if (!isset($_SESSION['personify_token'])) {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }
    else {
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }
  }

}
