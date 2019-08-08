<?php

namespace Drupal\openy_home_branch\Plugin\HomeBranchLibrary;

use Drupal\Core\StringTranslation\StringTranslationTrait;
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

  use StringTranslationTrait;

  const PRGF_TYPE = 'prgf_location_finder';

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
    return ($variables['paragraph'] && $variables['paragraph']->getType() == self::PRGF_TYPE);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    return [
      'locationsList' => '.field-prgf-location-finder .locations-list .views-row__wrapper',
      'branchTeaserSelector' => '.node--type-branch.node--view-mode-teaser',
      'selectedText' => $this->t('My Home Branch'),
      'notSelectedText' => $this->t('Set as my Home Branch'),
    ];
  }

}
