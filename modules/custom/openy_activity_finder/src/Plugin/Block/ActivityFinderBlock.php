<?php

namespace Drupal\openy_activity_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend;

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
    $node = \Drupal::routeMatch()->getParameter('node');
    $alias = '';
    if ($node instanceof NodeInterface) {
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->id());
    }

    $locationsMapping = [];
    if ($config->get('backend' == 'openy_daxko2.openy_activity_finder_backend')) {
      $openy_daxko2_config = \Drupal::service('config.factory')->get('openy_daxko2.settings');
      if (!empty($openy_daxko2_config->get('locations'))) {
        $nids = \Drupal::entityQuery('node')
          ->condition('type', ['branch', 'camp', 'facility'], 'IN')
          ->condition('status', 1)
          ->sort('title', 'ASC')
          ->execute();
        $locations = \Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple($nids);
        $config_rows = explode("\n", $openy_daxko2_config->get('locations'));
        foreach ($config_rows as $row) {
          $line = explode(', ', $row);
          foreach ($locations as $nid => $location) {
            if (isset($line[1]) && $line[1] == $location->getTitle()) {
              $locationsMapping[$nid] = $line[0];
            }
          }
        }
      }
    }

    return [
      '#theme' => 'openy_activity_finder_program_search',
      '#data' => [],
      '#ages' => $backend->getAges(),
      '#days' => $backend->getDaysOfWeek(),
      '#categories' => $backend->getCategoriesTopLevel(),
      '#categories_type' => $backend->getCategoriesType(),
      '#activities' => $backend->getCategories(),
      '#locations' => $backend->getLocations(),
      '#expanderSectionsConfig' => $config->getRawData(),
      '#attached' => [
        'drupalSettings' => [
          'activityFinder' => [
            'alias' => $alias,
            'is_search_box_disabled' => $config->get('disable_search_box'),
            'locationsNidToDaxkoIdMapping' => $locationsMapping,
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
