<?php

namespace Drupal\openy_home_branch\Plugin\HomeBranchLibrary;

use Drupal\openy_home_branch\HomeBranchLibraryBase;

/**
 * Defines the home branch library plugin for ActivityFinder locations selector.
 *
 * @HomeBranchLibrary(
 *   id="hb_loc_selector_activity_finder",
 *   label = @Translation("Home Branch Activity Finder Location Selector"),
 *   entity="paragraph"
 * )
 */
class HBLocSelectorActivityFinder extends HomeBranchLibraryBase {

  const PRGF_TYPE = 'activity_finder';

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'openy_home_branch/activity_finder_location';
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedForAttaching($variables) {
    return (isset($variables['paragraph']) && $variables['paragraph']->getType() == self::PRGF_TYPE);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    return [
      'locationStep' => '3',
      'selector' => '.paragraph--type--activity-finder',
    ];
  }

}
