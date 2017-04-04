<?php

namespace Drupal\panelizer_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * A test block used in the Panelizer functional tests.
 *
 * @Block(
 *   id = "panelizer_test",
 *   admin_label = @Translation("Panelizer test block")
 * )
 */
class PanelizerTestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => 'Abracadabra',
    ];
  }

}