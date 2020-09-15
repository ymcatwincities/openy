<?php

namespace Drupal\openy_activity_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Activity Finder' block.
 *
 * @Block(
 *   id = "activity_finder_block",
 *   admin_label = @Translation("Activity Finder Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class ActivityFinderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity query factory.
   *
   * @var QueryFactory
   */
  protected $entityQuery;

  /**
   * The alias manager that caches alias lookups based on the request.
   *
   * @var AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The route match.
   *
   * @var RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    QueryFactory $entity_query,
    AliasManagerInterface $alias_manager,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityQuery = $entity_query;
    $this->aliasManager = $alias_manager;
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
      $container->get('config.factory'),
      $container->get('entity.query'),
      $container->get('path_alias.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('openy_activity_finder.settings');
    $backend_service_id = $config->get('backend');
    $backend = \Drupal::service($backend_service_id);
    $node = $this->routeMatch->getParameter('node');
    $alias = '';
    if ($node instanceof NodeInterface) {
      $alias = $this->aliasManager->getAliasByPath('/node/' . $node->id());
    }

    $locationsMapping = [];
    if ($backend_service_id == 'openy_daxko2.openy_activity_finder_backend') {
      $openy_daxko2_config = $this->configFactory->get('openy_daxko2.settings');
      if (!empty($openy_daxko2_config->get('locations'))) {
        $nids = $this->entityQuery
          ->get('node')
          ->condition('type', ['branch', 'camp', 'facility'], 'IN')
          ->condition('status', 1)
          ->sort('title', 'ASC')
          ->execute();
        $locations = Node::loadMultiple($nids);
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
