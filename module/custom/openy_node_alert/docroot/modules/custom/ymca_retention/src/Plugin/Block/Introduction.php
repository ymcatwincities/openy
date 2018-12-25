<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with campaign introduction info.
 *
 * @Block(
 *   id = "retention_introduction_block",
 *   admin_label = @Translation("[YMCA Retention] Introduction"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Introduction extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get retention copy settings.
    $config = \Drupal::config('ymca_retention.copy_settings');

    $content = [
      'intro_header' => $config->get('intro_header'),
      'intro_reg_btn' => $config->get('intro_reg_btn'),
    ];

    // Ensure the 'intro_reg_btn' contains a value.
    if (empty($content['intro_reg_btn'])) {
      $content['intro_reg_btn'] = $this->t("Sign Up Now");
    }

    // Handled the formatted body text.
    $intro_body = $config->get('intro_body');
    $content['intro_body'] = [
      '#type' => 'processed_text',
      '#text' => $intro_body['value'],
      '#format' => $intro_body['format'],
    ];
    // Create 3 info blocks.
    for ($i = 1; $i < 4; $i++) {
      $name = "info_block_{$i}";
      $content['info_blocks'][$name] = [
        'header' => $config->get("{$name}_header"),
        'link_type' => $config->get("{$name}_link_type"),
        'link' => $config->get("{$name}_link"),
        'tab' => $config->get("{$name}_tab"),
      ];

      $body = $config->get("{$name}_copy");
      $content['info_blocks'][$name]['copy'] = [
        '#type' => 'processed_text',
        '#text' => $body['value'],
        '#format' => $body['format'],
      ];
      $fid = $config->get("{$name}_img")[0];
      if ($fid && $file = file_load($fid)) {
        $content['info_blocks'][$name]['img'] = $file->getFileUri();
      }
    }

    return [
      '#theme' => 'ymca_retention_introduction',
      '#content' => $content,
      '#attached' => [
        'library' => [
          'ymca_retention/introduction',
        ],
      ],
    ];
  }

}
