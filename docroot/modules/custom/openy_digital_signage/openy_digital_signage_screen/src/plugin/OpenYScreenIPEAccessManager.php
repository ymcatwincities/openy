<?php

namespace Drupal\openy_digital_signage_screen\Plugin;

use Drupal\panels_ipe\Plugin\IPEAccessManager;
use Drupal\panels_ipe\Plugin\IPEAccessManagerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Overrides the default IPE Access plugin manager.
 */
class OpenYScreenIPEAccessManager extends IPEAccessManager implements IPEAccessManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function access(PanelsDisplayVariant $display) {
    if ($screen = \Drupal::routeMatch()->getParameter('openy_digital_signage_screen')) {
      return FALSE;
    }
    return parent::access($display);
  }

}
