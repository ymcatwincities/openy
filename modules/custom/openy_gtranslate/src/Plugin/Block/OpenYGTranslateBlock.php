<?php

namespace Drupal\openy_gtranslate\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'OpenY Google Translate' block.
 *
 * @Block(
 *   id = "openy_gtranslate_block",
 *   admin_label = @Translation("OpenY Google Translate"),
 *   category = @Translation("OpenY"),
 * )
 */
class OpenYGTranslateBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $return = [
      '#theme' => 'openy_gtranslate',
      '#cache' => ['max-age' => 0],
    ];

    return $return;
  }
}