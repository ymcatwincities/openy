<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a tabs selector block.
 *
 * @Block(
 *   id = "retention_tabs_selector_block",
 *   admin_label = @Translation("[YMCA Retention] Tabs selector"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class TabsSelector extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('ymca_retention.general_settings');
    $current_date = new \DateTime();
    $open_date = new \DateTime($config->get('date_campaign_open'));
    $diff = $current_date->diff($open_date);

    return [
      '#theme' => 'ymca_retention_tabs_selector',
      '#content' => [],
      '#attached' => [
        'library' => [
          'ymca_retention/tabs-selector',
        ],
        'drupalSettings' => [
          'ymca_retention' => [
            'tabs_selector' => [
              'campaign_started' => $diff->invert,
            ],
          ],
        ],
      ],
    ];
  }

}
