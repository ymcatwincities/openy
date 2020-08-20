<?php

namespace Drupal\openy_activity_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\node\NodeInterface;
use Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend;

/**
 * Provides a 'Camp Finder' block.
 *
 * @Block(
 *   id = "camp_finder_block",
 *   admin_label = @Translation("Camp Finder Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampFinderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::service('config.factory')->get('openy_activity_finder.settings');
    $backend_service_id = $config->get('backend');
    $backend = \Drupal::service($backend_service_id);
    $node = \Drupal::routeMatch()->getParameter('node');
    $alias = '';
    if ($node instanceof NodeInterface) {
      $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());
    }

    return [
      '#theme' => 'openy_camp_finder_program_search',
      '#data' => [],
      '#ages' => $backend->getAges(),
      '#weeks' => $backend->getWeeks(),
      '#categories' => $backend->getCategoriesTopLevel(),
      '#categories_type' => $backend->getCategoriesType(),
      '#activities' => $backend->getCategories(),
      '#locations' => $backend->getLocations(),
      '#attached' => [
        'drupalSettings' => [
          'activityFinder' => [
            'alias' => $alias,
            'is_search_box_disabled' => $config->get('disable_search_box'),
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
