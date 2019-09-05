<?php

namespace Drupal\openy_activity_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend;

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
      '#is_spots_available_disabled' => $config->get('disable_spots_available'),
      '#sort_options' => $backend->getSortOptions(),
      '#attached' => [
        'drupalSettings' => [
          'activityFinder' => [
            'is_search_box_disabled' => $config->get('disable_search_box'),
            'is_spots_available_disabled' => $config->get('disable_spots_available'),
          ],
        ],
      ],
      '#cache' => [
        'tags' => $this->getCacheTags(),
        'contexts' => $this->getCacheContexts(),
        'max-age' => $this->getCacheMaxAge(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [OpenyActivityFinderSolrBackend::ACTIVITY_FINDER_CACHE_TAG]);
  }

}
