<?php

namespace Drupal\openy_home_branch\Plugin\HomeBranchLibrary;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\openy_home_branch\HomeBranchLibraryBase;

/**
 * Defines the home branch library plugin for modal with locations.
 *
 * @HomeBranchLibrary(
 *   id="hb_loc_modal",
 *   label = @Translation("Home Branch Locations Modal"),
 *   entity="block"
 * )
 */
class HBLocModal extends HomeBranchLibraryBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'openy_home_branch/location_modal';
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedForAttaching($variables) {
    // We need same rules as HBMenuSelector.
    return ($variables['plugin_id'] == HBMenuSelector::BLOCK_ID);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    return [
      'modalTitle' => $this->t('Home branch'),
      'modalDescription' => $this->t('Would you like to set a different location as your "home branch"?'),
      'dontAskTitle' => $this->t('Don\'t ask me again'),
      // Delay until next window display 24h.
      'modalDelay' => 86400,
      'learnMoreText' => $this->t('
        <h5>Why set your home branch?</h5>
        <p>Many YMCA members primarily visit one YMCA branch. Setting your home branch customizes your experience with schedules and programs for that branch. You are still able to view information for all other branches.</p>
        <h5>Changing your home branch</h5>
        <p>You can change your home branch at any time by using the branch link at the top of the website. If we change the link in the header to go straight to the location page, we\'ll need to update the "Changing your home branch" copy – so lets wed these two tickets. </p>
      '),
    ];
  }

}
