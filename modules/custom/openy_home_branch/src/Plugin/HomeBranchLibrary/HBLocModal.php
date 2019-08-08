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
      'learnMoreText' => $this->t('
        <h5>Why set a home branch?</h5>
        <p>By setting a "home branch", we can help you find content faster. When you search for location-based information - like programs, schedules, and more - we\'ll assume this is the location you\'re searching about.</p>
        <h5>Changing your home branch</h5>
        <p>To change your "home branch" later, you can find the link in the website header. If you have not selected a branch, the link will read "My home branch". If you have set a branch, the link will appear as the branch name.</p>
      '),
    ];
  }

}
