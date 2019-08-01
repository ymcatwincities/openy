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

  const NODE_TYPE = 'branch';

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
    return ($variables['node'] && $variables['node']->getType() == self::NODE_TYPE);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    return [
      'placeholderSelector' => '.openy-branch-selector',
      'selectedText' => t('My Home Branch'),
      'notSelectedText' => t('Set as my Home Branch'),
    ];
  }

}
