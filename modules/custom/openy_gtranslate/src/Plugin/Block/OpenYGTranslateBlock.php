<?php

namespace Drupal\openy_gtranslate\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Open Y Google Translate' block.
 *
 * @Block(
 *   id = "openy_gtranslate_block",
 *   admin_label = @Translation("Open Y Google Translate"),
 *   category = @Translation("OpenY"),
 * )
 */
class OpenYGTranslateBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#theme' => 'openy_gtranslate',
      '#attached' => [
        'library' => ['openy_gtranslate/translate'],
      ],
    ];

    return $block;
  }

}
