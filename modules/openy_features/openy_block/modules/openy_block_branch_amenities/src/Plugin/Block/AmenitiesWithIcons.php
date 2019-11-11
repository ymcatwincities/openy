<?php

namespace Drupal\openy_block_branch_amenities\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Node Amenities' block.
 *
 * @Block(
 *   id = "branch_amenities_icons",
 *   admin_label = @Translation("Branch Amenities with icons Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class AmenitiesWithIcons extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * RouteMatch service instance
   */
  protected $routeMatch;

  /**
   * @param array $configuration
   *   Plugin config
   * @param string $plugin_id
   *   Plugin id
   * @param mixed $plugin_definition
   *   Plugin definition
   * @param $routeMatch
   *   RouteMatch service instance
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $render_array = [];

    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface && $node->hasField('field_location_amenities')) {
      return $node->get('field_location_amenities')->view([
        'type' => 'entity_reference_entity_view',
        'settings' => [
          'view_mode' => 'icon'
        ]
      ]);
    }

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = $this->routeMatch->getParameter('node')) {
      //if there is node add its cachetag
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      //Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

}
