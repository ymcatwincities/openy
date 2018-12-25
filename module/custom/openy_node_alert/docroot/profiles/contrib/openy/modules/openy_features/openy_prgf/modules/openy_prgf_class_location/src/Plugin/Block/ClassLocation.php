<?php

namespace Drupal\openy_prgf_class_location\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\openy_prgf_class_location\ClassLocationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a leader board block.
 *
 * @Block(
 *   id = "class_location",
 *   admin_label = @Translation("Class Location block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class ClassLocation extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Class Location service.
   *
   * @var \Drupal\openy_prgf_class_location\ClassLocationServiceInterface
   */
  protected $classLocationService;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new ClassLocation.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ClassLocationServiceInterface $class_location_service
   *   The Class Location service.
   * @param RouteMatchInterface $route_match
   *   The Route match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClassLocationServiceInterface $class_location_service, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->classLocationService = $class_location_service;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openy_prgf_class_location.location_handler'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Extract node from route, return empty if none.
    if (!$node = $this->routeMatch->getParameter('node')) {
      return [];
    }

    // Set cache contexts with 'url.query_args'.
    $contexts[] = 'url.query_args';

    // Set cache tags from node.
    $tags = $node->getCacheTags();

    // Get query param location.
    $request = \Drupal::request();

    if (!empty($request->query->get('location')) && filter_var($request->query->get('location'), FILTER_VALIDATE_INT) !== FALSE) {
      $location_id = $request->query->get('location');
    }
    else {
      $location_id = NULL;
    }

    /* @var NodeInterface $location */
    if (is_a($location = $this->classLocationService->getLocationNode($location_id), 'Drupal\node\Entity\Node')) {
      $location_renderable = node_view($location, 'class_location');

      // Add location cache tags.
      $tags_location = $location->getCacheTags();
      $tags = Cache::mergeTags($tags, $tags_location);
    }

    $class_location = [
      '#theme' => 'class_location',
      '#cache' => [
        'tags' => Cache::mergeTags(['class_location'], $tags),
        'contexts' => $contexts,
      ],
    ];

    if (isset($location_renderable)) {
      $class_location['#class_location'] = $location_renderable;
    }

    return $class_location;
  }

}
