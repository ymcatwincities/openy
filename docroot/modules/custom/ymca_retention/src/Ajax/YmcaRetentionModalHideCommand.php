<?php

namespace Drupal\ymca_retention\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to hide YMCA Retention modal.
 *
 * @ingroup ajax
 */
class YmcaRetentionModalHideCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return array(
      'command' => 'ymcaRetentionModalHide',
    );
  }

}
