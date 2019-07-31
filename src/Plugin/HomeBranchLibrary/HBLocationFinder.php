<?php

namespace Drupal\openy_home_branch\Plugin\HomeBranchLibrary;

use Drupal\openy_home_branch\HomeBranchLibraryBase;

/**
 * Defines the home branch library plugin for location finder paragraph.
 *
 * TODO: add get js settings method.
 *
 * @HomeBranchLibrary(
 *   id="hb_location_finder",
 *   label = @Translation("Home Branch Location Finder"),
 *   entity="paragraph"
 * )
 */
class HBLocationFinder extends HomeBranchLibraryBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'openy_home_branch/location_finder';
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedForAttaching($variables) {
    if ($variables['paragraph'] && $variables['paragraph']->getType() == 'prgf_location_finder') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    return [
      'locationsList' => '.field-prgf-location-finder .locations-list .views-row__wrapper',
      'branchTeaserSelector' => '.node--type-branch.node--view-mode-teaser',
      'selectedText' => t('My Home Branch'),
      'notSelectedText' => t('Set as my Home Branch'),
    ];
  }

}
