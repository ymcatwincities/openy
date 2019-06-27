<?php

namespace Drupal\openy_home_branch\Plugin\HomeBranchLibrary;

use Drupal\openy_home_branch\HomeBranchLibraryBase;

/**
 * Defines the home branch library plugin for user menu block.
 *
 * @HomeBranchLibrary(
 *   id="hb_menu_selector",
 *   label = @Translation("Home Branch Menu Selector"),
 *   entity="block"
 * )
 */
class HBMenuSelector extends HomeBranchLibraryBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'openy_home_branch/menu_selector';
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedForAttaching($variables) {
    if ($variables['plugin_id'] == 'system_menu_block:account') {
      return TRUE;
    }
    return FALSE;
  }

}
