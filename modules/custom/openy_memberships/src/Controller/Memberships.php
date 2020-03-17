<?php

namespace Drupal\openy_memberships\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Render Activities/Strategies pages.
 */
class Memberships extends ControllerBase {
  
  /**
   * Activity route method for proxying client request.
   */
  public function page($filepath) {
    $attachments['library'][] = 'openy_memberships/openy_memberships';
    return [
      '#theme' => 'openy_memberships',
      '#attached' => $attachments,
    ];
  }

}
