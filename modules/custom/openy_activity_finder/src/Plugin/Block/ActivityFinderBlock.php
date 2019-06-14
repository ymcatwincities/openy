<?php

namespace Drupal\openy_activity_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

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
    if ($node instanceof NodeInterface) {
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->id());
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
      '#attached' => [
        'drupalSettings' => [
          'activityFinder' => [
            'alias' => isset($alias) ? $alias : '',
            'is_search_box_disabled' => $config->get('disable_search_box'),
          ]
        ]
      ]
    ];
  }

}
