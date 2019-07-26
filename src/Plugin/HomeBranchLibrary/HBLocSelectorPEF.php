<?php

namespace Drupal\openy_home_branch\Plugin\HomeBranchLibrary;

use Drupal\openy_home_branch\HomeBranchLibraryBase;

/**
 * Defines the home branch library plugin for FEF locations selector.
 *
 * @HomeBranchLibrary(
 *   id="hb_loc_selector_pef",
 *   label = @Translation("Home Branch PEF Location Selector"),
 *   entity="paragraph"
 * )
 */
class HBLocSelectorPEF extends HomeBranchLibraryBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'openy_home_branch/pef_location';
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedForAttaching($variables) {
    if ($variables['paragraph'] && $variables['paragraph']->getType() == 'repeat_schedules_loc') {
      return TRUE;
    }
    return FALSE;
  }

}
