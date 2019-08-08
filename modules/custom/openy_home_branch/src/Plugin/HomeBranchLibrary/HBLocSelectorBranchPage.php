<?php

namespace Drupal\openy_home_branch\Plugin\HomeBranchLibrary;

use Drupal\Core\StringTranslation\StringTranslationTrait;
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

  use StringTranslationTrait;

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
    return ($variables['node'] && $variables['node']->getType() == self::NODE_TYPE && $variables['page']);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    return [
      'placeholderSelector' => '.openy-branch-selector',
      'selectedText' => $this->t('My Home Branch'),
      'notSelectedText' => $this->t('Set as my Home Branch'),
    ];
  }

}
