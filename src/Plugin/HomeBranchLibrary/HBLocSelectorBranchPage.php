<?php

namespace Drupal\openy_home_branch\Plugin\HomeBranchLibrary;

use Drupal\openy_home_branch\HomeBranchLibraryBase;

/**
 * Defines the home branch library plugin for branch page.
 *
 * @HomeBranchLibrary(
 *   id="hb_loc_selector_branch_page",
 *   label = @Translation("Home Branch Location Selector on the Branch page"),
 *   entity="node"
 * )
 */
class HBLocSelectorBranchPage extends HomeBranchLibraryBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'openy_home_branch/loc_selector_branch_page';
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedForAttaching($variables) {
    if ($variables['node'] && $variables['node']->getType() == 'branch') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    return [
      // TODO: double check that it works in OpenY.
      'placeholderSelector' => '.openy-branch-selector',
    ];
  }

}
