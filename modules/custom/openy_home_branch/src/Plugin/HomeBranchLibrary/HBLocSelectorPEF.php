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

  const PRGF_TYPE = 'repeat_schedules_loc';

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
    return ($variables['paragraph'] && $variables['paragraph']->getType() == self::PRGF_TYPE);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    return [
      'locationsWrapper' => '.schedule-locations__wrapper',
      'inputSelector' => '.paragraph--type--repeat-schedules-loc',
      'linkSelector' => '.field-prgf-repeat-lschedules-prf a',
    ];
  }

}
