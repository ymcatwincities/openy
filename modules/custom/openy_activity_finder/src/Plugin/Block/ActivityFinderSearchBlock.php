<?php

namespace Drupal\openy_activity_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'PEF Programs' block.
 *
 * @Block(
 *   id = "activity_finder_search_block",
 *   admin_label = @Translation("Activity Finder Search Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class ActivityFinderSearchBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::service('config.factory')->get('openy_activity_finder.settings');
    $backend_service_id = $config->get('backend');
    $backend = \Drupal::service($backend_service_id);

    return [
      '#theme' => 'openy_activity_finder_program_search_page',
      '#locations' => $backend->getLocations(),
      '#categories' => $backend->getCategories(),
      '#categories_type' => $backend->getCategoriesType(),
      '#ages' => $backend->getAges(),
      '#days' => $backend->getDaysOfWeek(),
      '#is_search_box_disabled' => $config->get('disable_search_box'),
      '#sort_options' => $backend->getSortOptions(),
      '#attached' => [
        'drupalSettings' => [
          'activityFinder' => [
            'categories' => $backend->getCategories(),
          ]
        ]
      ]
    ];
  }

}
