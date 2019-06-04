<?php

namespace Drupal\openy_activity_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'Activity Finder' block.
 *
 * @Block(
 *   id = "activity_finder_block",
 *   admin_label = @Translation("Activity Finder Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class ActivityFinderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::service('config.factory')->get('openy_activity_finder.settings');
    $backend_service_id = $config->get('backend');
    $backend = \Drupal::service($backend_service_id);

    return [
      '#theme' => 'openy_activity_finder_program_search',
      '#data' => [],
      '#ages' => $backend->getAges(),
      '#days' => $backend->getDaysOfWeek(),
      '#categories' => $backend->getCategoriesTopLevel(),
      '#categories_type' => $backend->getCategoriesType(),
      '#locations' => $backend->getLocations(),
      '#is_search_box_disabled' => $config->get('disable_search_box'),
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
